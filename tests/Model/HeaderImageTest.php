<?php

namespace Dynamic\SiteTools\Test\Model;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class HeaderImageTest
 *
 * Covers HeaderImage::can*(), which has to resolve permissions from the pages whose HeaderImage
 * relation points at the record. The Page has_one these checks used to consult is never written by
 * anything in the module, and a has_one getter returns an empty record rather than null, so the
 * page-based branch used to run against an empty SiteTree and let every CMS member through.
 */
class HeaderImageTest extends SapphireTest
{
    /**
     * The HeaderImageID column only exists on the tables of the classes HeaderImageExtension is
     * applied to. Two sibling page classes are extended so the owner walk is proven to look at more
     * than the one class it first finds.
     *
     * @var array
     */
    protected static $required_extensions = [
        RedirectorPage::class => [
            HeaderImageExtension::class,
        ],
        ErrorPage::class => [
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
     * Page classes to run the owner-permission cases through.
     *
     * @return array<string, array<string>>
     */
    public static function ownerClassProvider(): array
    {
        return [
            'redirector page' => [RedirectorPage::class],
            'error page' => [ErrorPage::class],
        ];
    }

    /**
     * Create a page of the given class, written to the draft stage.
     *
     * @param string $class A SiteTree subclass carrying the HeaderImage relation.
     * @param array $values Field overrides merged into the record.
     * @return SiteTree
     */
    private function createPage(string $class, array $values = []): SiteTree
    {
        $defaults = ['ClassName' => $class, 'CanViewType' => 'Anyone'];
        $page = $class::create(array_merge($defaults, $values));
        $page->write();

        return $page;
    }

    /**
     * Create a header image, optionally owned by the given page.
     *
     * @param string $title The title to give the record.
     * @param SiteTree|null $owner The page whose HeaderImage relation points at this record.
     * @return HeaderImage
     */
    private function createHeaderImage(string $title, ?SiteTree $owner = null): HeaderImage
    {
        $image = HeaderImage::create(['Title' => $title]);
        $image->write();

        if ($owner) {
            $owner->HeaderImageID = $image->ID;
            $owner->write();
        }

        return $image;
    }

    /**
     * A member carrying only CMS page access, which is what the module grants a content editor.
     *
     * @return Member
     */
    private function logInAsCmsMember(): Member
    {
        $member = Member::get()->byID($this->logInWithPermission('CMS_ACCESS_CMSMain'));

        $this->assertFalse(
            Permission::checkMember($member, 'SITETREE_EDIT_ALL'),
            'The member must not hold the global edit permission, or every page check short circuits.'
        );

        return $member;
    }

    /**
     * A page the given member may not edit, because its edit rights are limited to another group.
     *
     * @param string $class A SiteTree subclass carrying the HeaderImage relation.
     * @param string $name A name to keep titles unique.
     * @return SiteTree
     */
    private function createRestrictedPage(string $class, string $name): SiteTree
    {
        $editors = Group::create(['Title' => "Editors of $name"]);
        $editors->write();

        $page = $this->createPage($class, [
            'Title' => $name,
            'CanEditType' => 'OnlyTheseUsers',
        ]);
        $page->EditorGroups()->add($editors);

        return $page;
    }

    /**
     * A new record is creatable and viewable for a member who has CMS page access, which is the
     * path the GridField add form takes.
     */
    public function testUnsavedHeaderImageIsCreatableAndViewableByCmsMember(): void
    {
        $this->logInAsCmsMember();

        $image = HeaderImage::create(['Title' => 'Not written yet']);

        $this->assertTrue($image->canCreate(), 'A CMS member can add a header image.');
        $this->assertTrue($image->canView(), 'A CMS member can view a header image not yet written.');
    }

    /**
     * Permissions follow the owning page: an editor of the page may edit the image, a member the
     * page excludes may not.
     */
    #[DataProvider('ownerClassProvider')]
    public function testPermissionsFollowTheOwningPage(string $class): void
    {
        $member = $this->logInAsCmsMember();

        $restricted = $this->createRestrictedPage($class, "Restricted $class");
        $restrictedImage = $this->createHeaderImage("Restricted image $class", $restricted);

        $open = $this->createPage($class, [
            'Title' => "Open $class",
            'CanEditType' => 'LoggedInUsers',
        ]);
        $openImage = $this->createHeaderImage("Open image $class", $open);

        $this->assertFalse($restrictedImage->canEdit($member), 'The excluded member cannot edit the image.');
        $this->assertFalse($restrictedImage->canView($member), 'The excluded member cannot view the image.');
        $this->assertFalse($restrictedImage->canDelete($member), 'The excluded member cannot delete the image.');

        $this->assertTrue($openImage->canEdit($member), 'A CMS member can edit the image of an open page.');
        $this->assertTrue($openImage->canView($member), 'A CMS member can view the image of an open page.');
        $this->assertTrue($openImage->canDelete($member), 'A CMS member can delete the image of an open page.');
    }

    /**
     * One image can be pointed at by more than one page, through the add-existing autocompleter.
     * The most restricted owner wins, otherwise a member who can edit any open page could reach the
     * image shown on a page they cannot.
     */
    public function testSharedImageFollowsItsMostRestrictedOwner(): void
    {
        $member = $this->logInAsCmsMember();

        $image = $this->createHeaderImage('Shared between two pages');

        $restricted = $this->createRestrictedPage(RedirectorPage::class, 'Shared restricted owner');
        $restricted->HeaderImageID = $image->ID;
        $restricted->write();

        $open = $this->createPage(ErrorPage::class, [
            'Title' => 'Shared open owner',
            'CanEditType' => 'LoggedInUsers',
            'ErrorCode' => 404,
        ]);
        $open->HeaderImageID = $image->ID;
        $open->write();

        $this->assertFalse($image->canEdit($member), 'An open co-owner does not unlock a restricted one.');
        $this->assertFalse($image->canDelete($member), 'An open co-owner does not unlock deletion.');

        // And once the restricted owner lets go of it, the remaining owner governs on its own.
        $restricted->HeaderImageID = 0;
        $restricted->write();

        $this->assertTrue($image->canEdit($member), 'With only the open owner left, a CMS member can edit it.');
    }

    /**
     * A record nothing points at - the add form case, or an orphan - follows the site-wide page
     * edit rights rather than DataObject::can*(), which is ADMIN-only.
     */
    public function testOwnerlessHeaderImageFollowsSiteConfig(): void
    {
        $cmsMember = $this->logInAsCmsMember();
        $image = $this->createHeaderImage('Nobody owns this');

        $this->assertTrue($image->canEdit($cmsMember), 'A CMS member can edit an ownerless image.');
        $this->assertTrue($image->canCreate($cmsMember), 'A CMS member can create an ownerless image.');
        $this->assertTrue($image->canView($cmsMember), 'A CMS member can view an ownerless image.');
        $this->assertTrue($image->canDelete($cmsMember), 'A CMS member can delete an ownerless image.');

        $outsider = Member::create(['FirstName' => 'No', 'Surname' => 'CmsAccess']);
        $outsider->write();
        $this->logInAs($outsider);

        $this->assertFalse($image->canEdit($outsider), 'A member without CMS access cannot edit it.');
        $this->assertFalse($image->canCreate($outsider), 'A member without CMS access cannot create one.');
        $this->assertFalse($image->canView($outsider), 'A member without CMS access cannot view it.');
        $this->assertFalse($image->canDelete($outsider), 'A member without CMS access cannot delete it.');
    }

    /**
     * An owner that exists only on Live still governs access. Clearing the draft pointer must not
     * turn an image that is live on a restricted page into something every CMS editor may change.
     */
    public function testLiveOnlyOwnerIsFound(): void
    {
        $member = $this->logInAsCmsMember();

        $page = $this->createRestrictedPage(RedirectorPage::class, 'Live only owner');
        $image = $this->createHeaderImage('Live only image', $page);
        $page->publishRecursive();

        $this->assertNotNull(
            Versioned::get_by_stage(RedirectorPage::class, Versioned::LIVE)->byID($page->ID),
            'The restricted page is published, with the image still attached.'
        );

        $page->HeaderImageID = 0;
        $page->write();

        $draftOnly = Versioned::get_by_stage(RedirectorPage::class, Versioned::DRAFT)->byID($page->ID);
        $this->assertNotNull($draftOnly, 'The draft row is still there...');
        $this->assertEquals(0, (int)$draftOnly->HeaderImageID, '...with its pointer to the image cleared.');

        $this->assertFalse(
            $image->canEdit($member),
            'The page that still shows the image on Live keeps governing who may edit it.'
        );
        $this->assertFalse(
            $image->canDelete($member),
            'The live-only owner keeps governing deletion too.'
        );
    }

    /**
     * With no logged-in member and none passed in, every check denies rather than erroring.
     */
    public function testNoMemberIsDenied(): void
    {
        $this->logOut();

        $image = $this->createHeaderImage('Anonymous check');

        $this->assertFalse($image->canView(null), 'An anonymous caller cannot view it.');
        $this->assertFalse($image->canEdit(null), 'An anonymous caller cannot edit it.');
        $this->assertFalse($image->canCreate(null), 'An anonymous caller cannot create one.');
        $this->assertFalse($image->canDelete(null), 'An anonymous caller cannot delete it.');
    }
}
