<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;

/**
 * Class HeaderImageLinkValidationTest
 *
 * Covers HeaderImageExtension::updateValidate(). HeaderImage grants a permission only when every
 * page pointing at it grants it, so linking an image to a page makes that page one more owner the
 * image's existing editors have to satisfy. The add-existing autocompleter writes the pointer
 * without checking the image, so the page write itself has to refuse an image the member may not
 * edit, or a member who can edit one page could lock another page's editors out of its image.
 */
class HeaderImageLinkValidationTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $required_extensions = [
        \Page::class => [
            HeaderImageExtension::class,
        ],
    ];

    /**
     * Every test writes records and this class has no fixture file, so the database has to be
     * declared explicitly for the per-test transaction rollback to run.
     *
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * The group allowed to edit the restricted page.
     *
     * @var Group
     */
    private $restrictedEditors;

    /**
     * A page only the restricted editors may edit, which owns the image.
     *
     * @var \Page
     */
    private $restrictedPage;

    /**
     * A page every logged-in member may edit.
     *
     * @var \Page
     */
    private $openPage;

    /**
     * The header image the restricted page points at.
     *
     * @var HeaderImage
     */
    private $image;

    protected function setUp(): void
    {
        parent::setUp();

        // Built with nobody logged in, as a fixture load or a task would.
        $this->logOut();

        $this->restrictedEditors = Group::create(['Title' => 'Editors of the restricted page']);
        $this->restrictedEditors->write();

        $this->restrictedPage = \Page::create([
            'Title' => 'Restricted page',
            'CanViewType' => 'Anyone',
            'CanEditType' => 'OnlyTheseUsers',
        ]);
        $this->restrictedPage->write();
        $this->restrictedPage->EditorGroups()->add($this->restrictedEditors);

        $this->openPage = \Page::create([
            'Title' => 'Open page',
            'CanViewType' => 'Anyone',
            'CanEditType' => 'LoggedInUsers',
        ]);
        $this->openPage->write();

        $this->image = HeaderImage::create(['Title' => 'Restricted page image']);
        $this->image->write();

        $this->restrictedPage->HeaderImageID = $this->image->ID;
        $this->restrictedPage->write();
    }

    /**
     * Log in a member with CMS page access only, optionally in the restricted page's editor group.
     *
     * @param bool $restrictedEditor Whether the member may also edit the restricted page.
     * @return Member
     */
    private function logInAsCmsMember(bool $restrictedEditor = false): Member
    {
        $member = Member::get()->byID($this->logInWithPermission('CMS_ACCESS_CMSMain'));

        $this->assertFalse(
            Permission::checkMember($member, 'SITETREE_EDIT_ALL'),
            'The member must not hold the global edit permission, or every page check short circuits.'
        );

        if ($restrictedEditor) {
            $member->Groups()->add($this->restrictedEditors);
        }

        return $member;
    }

    /**
     * The HeaderImageID a page holds in the database on the draft stage.
     *
     * @param SiteTree $page
     * @return int
     */
    private function storedImageID(SiteTree $page): int
    {
        return (int)Versioned::get_by_stage(\Page::class, Versioned::DRAFT)->byID($page->ID)->HeaderImageID;
    }

    /**
     * A member who can edit only the open page cannot link the restricted page's image to it, so
     * the restricted page's editors keep their access to the image.
     */
    public function testMemberCannotLinkAnImageOwnedByAPageTheyCannotEdit(): void
    {
        $member = $this->logInAsCmsMember();

        $this->assertTrue($this->openPage->canEdit($member), 'The member can edit the open page.');
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the restricted image.');

        $this->openPage->HeaderImageID = $this->image->ID;
        $this->assertFalse($this->openPage->validate()->isValid(), 'The link fails validation.');

        try {
            $this->openPage->write();
            $this->fail('Linking an image the member may not edit must not be written.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('permission to use this header image', $e->getMessage());
        }

        $this->assertSame(0, $this->storedImageID($this->openPage), 'The open page still has no image.');

        $editor = Member::create(['FirstName' => 'Restricted', 'Surname' => 'Editor']);
        $editor->write();
        $editor->Groups()->add($this->restrictedEditors);
        Permission::grant($this->restrictedEditors->ID, 'CMS_ACCESS_CMSMain');

        $this->assertTrue(
            $this->image->canEdit($editor),
            'An editor of the restricted page keeps edit rights on its image.'
        );
    }

    /**
     * A member who can edit both pages may share the image between them.
     */
    public function testMemberWhoCanEditBothPagesMayLink(): void
    {
        $member = $this->logInAsCmsMember(true);

        $this->assertTrue($this->image->canEdit($member), 'The member can edit the restricted image.');

        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();

        $this->assertSame($this->image->ID, $this->storedImageID($this->openPage), 'The link is written.');
        $this->assertTrue($this->image->canEdit($member), 'The member still can edit the shared image.');
    }

    /**
     * An image nothing points at yet can be linked by any member who may edit pages.
     */
    public function testOwnerlessImageCanBeLinked(): void
    {
        $member = $this->logInAsCmsMember();

        $ownerless = HeaderImage::create(['Title' => 'Nobody owns this yet']);
        $ownerless->write();

        $this->openPage->HeaderImageID = $ownerless->ID;
        $this->openPage->write();

        $this->assertSame($ownerless->ID, $this->storedImageID($this->openPage), 'The link is written.');
        $this->assertTrue($ownerless->canEdit($member), 'The member can edit the image they linked.');
    }

    /**
     * A page that already points at an image the member may not edit - shared before this check
     * existed, or by a write with no member - can still be saved and published by its editors, as
     * long as the pointer itself does not change.
     */
    public function testResavingWithUnchangedImageIsUnaffected(): void
    {
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();

        $member = $this->logInAsCmsMember();
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the shared image.');

        $page = \Page::get()->byID($this->openPage->ID);
        $page->Title = 'Open page, renamed';
        // A form submission sets the unchanged ID back as a string.
        $page->HeaderImageID = (string)$this->image->ID;
        $page->write();

        // Publishing forces every field to look changed; that alone is not a new link.
        $page->publishSingle();

        $this->assertSame($this->image->ID, $this->storedImageID($page), 'The pointer is unchanged.');
        $this->assertSame(
            'Open page, renamed',
            Versioned::get_by_stage(\Page::class, Versioned::LIVE)->byID($page->ID)->Title,
            'The page is published.'
        );
    }

    /**
     * Rolling a page back to a version that pointed at an image is a new link like any other.
     * Versioned loads the old version with an empty change list, so the check cannot rely on it.
     */
    public function testRollbackToAVersionWithARestrictedImageIsRefused(): void
    {
        // The open page once pointed at the image, then moved off it.
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();
        $linkedVersion = (int)$this->openPage->Version;

        $this->openPage->HeaderImageID = 0;
        $this->openPage->write();

        $member = $this->logInAsCmsMember();
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the restricted image.');

        $page = \Page::get()->byID($this->openPage->ID);

        try {
            $page->rollbackRecursive($linkedVersion);
            $this->fail('Rolling back onto an image the member may not edit must not be written.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('permission to use this header image', $e->getMessage());
        }

        $this->assertSame(0, $this->storedImageID($page), 'The draft still points at no image.');
    }

    /**
     * A rollback a member is allowed to make still goes through: the check refuses the image, not
     * the rollback.
     */
    public function testRollbackByAnEditorOfBothPagesIsAllowed(): void
    {
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();
        $linkedVersion = (int)$this->openPage->Version;

        $this->openPage->HeaderImageID = 0;
        $this->openPage->write();

        $this->logInAsCmsMember(true);

        $page = \Page::get()->byID($this->openPage->ID);
        $page->rollbackRecursive($linkedVersion);

        $this->assertSame($this->image->ID, $this->storedImageID($page), 'The draft points at the image again.');
    }

    /**
     * Restoring an archived page that pointed at an image is a new link too: the page has no draft
     * row to compare against while it is archived.
     */
    public function testRestoreFromArchiveWithARestrictedImageIsRefused(): void
    {
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();
        $pageID = (int)$this->openPage->ID;
        $this->openPage->doArchive();

        $member = $this->logInAsCmsMember();
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the restricted image.');

        $archived = Versioned::get_latest_version(\Page::class, $pageID);
        $this->assertNotNull($archived, 'The archived page is still in the version history.');

        try {
            $archived->doRestoreToStage();
            $this->fail('Restoring onto an image the member may not edit must not be written.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('permission to use this header image', $e->getMessage());
        }

        $this->assertNull(
            Versioned::get_by_stage(\Page::class, Versioned::DRAFT)->byID($pageID),
            'The page stays archived.'
        );
    }

    /**
     * Changing a page's type keeps the image it already points at. The stored row is still filed
     * under the old class while the new one is being written, so it has to be found through the
     * base class or the unchanged image looks like a new link.
     */
    public function testChangingPageTypeWithUnchangedImageIsAllowed(): void
    {
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();

        $member = $this->logInAsCmsMember();
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the shared image.');

        $page = \Page::get()->byID($this->openPage->ID);
        $changed = $page->newClassInstance(RedirectorPage::class);
        $changed->write();

        $stored = Versioned::get_by_stage(SiteTree::class, Versioned::DRAFT)->byID($page->ID);
        $this->assertInstanceOf(RedirectorPage::class, $stored, 'The page type changed.');
        $this->assertSame($this->image->ID, (int)$stored->HeaderImageID, 'The pointer is unchanged.');
    }

    /**
     * Reverting a page's draft to Live brings back the image its Live row already shows. The page
     * already owns that image through its Live row, so no owner is added and nothing is refused.
     */
    public function testRevertToLiveWithTheLiveImageIsAllowed(): void
    {
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();
        $this->openPage->publishSingle();

        $this->openPage->HeaderImageID = 0;
        $this->openPage->write();

        $member = $this->logInAsCmsMember();
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the shared image.');

        $page = \Page::get()->byID($this->openPage->ID);
        $page->doRevertToLive();

        $this->assertSame($this->image->ID, $this->storedImageID($page), 'The draft points at the Live image.');
    }

    /**
     * Duplicating a page copies its header image rather than sharing it, so a member who cannot
     * edit the original's co-owner can still duplicate the page.
     */
    public function testDuplicateCopiesTheImageAndIsAllowed(): void
    {
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();

        $member = $this->logInAsCmsMember();
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the shared image.');

        $copy = \Page::get()->byID($this->openPage->ID)->duplicate();

        $this->assertNotSame(0, $this->storedImageID($copy), 'The copy has a header image.');
        $this->assertNotSame($this->image->ID, $this->storedImageID($copy), 'It is a copy, not the original.');
    }

    /**
     * Letting go of an image takes nothing from anyone, so it is allowed even for an image the
     * member may not edit.
     */
    public function testUnsettingIsAlwaysAllowed(): void
    {
        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();

        $member = $this->logInAsCmsMember();
        $this->assertFalse($this->image->canEdit($member), 'The member cannot edit the shared image.');

        $page = \Page::get()->byID($this->openPage->ID);
        $page->HeaderImageID = 0;
        $page->write();

        $this->assertSame(0, $this->storedImageID($page), 'The pointer is cleared.');
    }

    /**
     * A write with no member logged in is not checked, as with every other ORM write: fixture
     * loads, dev/build and CLI tasks have no member to ask about.
     */
    public function testWriteWithNoMemberIsNotChecked(): void
    {
        $this->logOut();

        $this->openPage->HeaderImageID = $this->image->ID;
        $this->openPage->write();

        $this->assertSame($this->image->ID, $this->storedImageID($this->openPage), 'The link is written.');
    }
}
