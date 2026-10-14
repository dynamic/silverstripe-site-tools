<?php

namespace Dynamic\SiteTools\Test\Model;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * Permission tests for {@see HeaderImage}.
 *
 * The owner of a header image is whichever page's HeaderImage relation points at it - never the
 * Page has_one, which nothing in this module ever populates and which therefore always resolved
 * to an empty SiteTree.
 */
class HeaderImageTest extends SapphireTest
{
    /**
     * HeaderImageExtension is applied to no page class by default, so apply it to two sibling
     * page classes and check the owner is found through either of them.
     *
     * @var array<string, array<int, string>>
     */
    protected static $required_extensions = [
        RedirectorPage::class => [HeaderImageExtension::class],
        ErrorPage::class => [HeaderImageExtension::class],
    ];

    /**
     * @var string[]
     */
    protected static $fixture_file = 'HeaderImageTest.yml';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // The permission cache outlives the fixture database between tests.
        Permission::reset();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Permission::reset();

        parent::tearDown();
    }

    /**
     * A header image without an owner falls back to "may this member edit pages at all", for
     * both the add form of a new record and for an image nothing points at.
     *
     * @return void
     */
    public function testOwnerlessImageFallsBackToPagePermissions(): void
    {
        $cmsAuthor = $this->objFromFixture(Member::class, 'cmsauthor');
        $noAccess = $this->objFromFixture(Member::class, 'nobody');

        // Unsaved, i.e. the record the GridField add form works on.
        $image = HeaderImage::create();
        $this->assertTrue($image->canCreate($cmsAuthor));
        $this->assertTrue($image->canView($cmsAuthor));
        $this->assertFalse($image->canCreate($noAccess));
        $this->assertFalse($image->canView($noAccess));

        // Saved, but no page points at it.
        $image->write();
        $this->assertTrue($image->canEdit($cmsAuthor));
        $this->assertTrue($image->canDelete($cmsAuthor));
        $this->assertFalse($image->canEdit($noAccess));
        $this->assertFalse($image->canDelete($noAccess));
    }

    /**
     * An image owned by a page the member cannot edit is closed to them, whichever extended page
     * class owns it.
     *
     * @return void
     */
    public function testOwnerEditPermissionsAreFollowed(): void
    {
        $cmsAuthor = $this->objFromFixture(Member::class, 'cmsauthor');

        $owners = [
            'restricted RedirectorPage' => [
                RedirectorPage::class, 'restricted', 'restrictedredirector',
            ],
            'restricted ErrorPage' => [
                ErrorPage::class, 'restricted', 'restrictederror',
            ],
        ];

        foreach ($owners as $label => $spec) {
            list($class, $pageKey, $imageKey) = $spec;
            $page = $this->objFromFixture($class, $pageKey);
            $image = $this->objFromFixture(HeaderImage::class, $imageKey);

            $this->assertFalse(
                $page->canEdit($cmsAuthor),
                "Sanity check: {$label} is closed to a plain CMS author"
            );
            $this->assertFalse(
                $image->canEdit($cmsAuthor),
                "{$label} must not be editable by a member who cannot edit its owner"
            );
            $this->assertFalse(
                $image->canView($cmsAuthor),
                "{$label} must not be viewable by a member who cannot edit its owner"
            );
            $this->assertFalse(
                $image->canCreate($cmsAuthor),
                "{$label} must not be creatable by a member who cannot edit its owner"
            );
        }
    }

    /**
     * An image owned by a page the member can edit stays open to them, through either class.
     *
     * @return void
     */
    public function testEditableOwnerGrantsAccess(): void
    {
        $cmsAuthor = $this->objFromFixture(Member::class, 'cmsauthor');

        $owners = [
            'open RedirectorPage' => [
                RedirectorPage::class, 'open', 'openredirector',
            ],
            'open ErrorPage' => [
                ErrorPage::class, 'open', 'openerror',
            ],
        ];

        foreach ($owners as $label => $spec) {
            list($class, $pageKey, $imageKey) = $spec;
            $page = $this->objFromFixture($class, $pageKey);
            $image = $this->objFromFixture(HeaderImage::class, $imageKey);

            $this->assertTrue(
                $page->canEdit($cmsAuthor),
                "Sanity check: {$label} is open to a plain CMS author"
            );
            $this->assertTrue($image->canEdit($cmsAuthor), "{$label} must be editable");
            $this->assertTrue($image->canView($cmsAuthor), "{$label} must be viewable");
            $this->assertTrue($image->canCreate($cmsAuthor), "{$label} must be creatable");
        }
    }

    /**
     * Deleting is taken from the owner's canDelete(), not from its canEdit().
     *
     * @return void
     */
    public function testOwnerDeletePermissionsAreFollowed(): void
    {
        $cmsAuthor = $this->objFromFixture(Member::class, 'cmsauthor');
        $image = $this->objFromFixture(HeaderImage::class, 'openerror');
        $page = $this->objFromFixture(ErrorPage::class, 'open');

        $this->assertTrue($page->canDelete($cmsAuthor), 'Sanity check: the owner is deletable');
        $this->assertTrue($image->canDelete($cmsAuthor));

        $restrictedImage = $this->objFromFixture(HeaderImage::class, 'restrictederror');
        $restrictedPage = $this->objFromFixture(ErrorPage::class, 'restricted');
        $this->assertFalse($restrictedPage->canDelete($cmsAuthor), 'Sanity check: owner is not deletable');
        $this->assertFalse($restrictedImage->canDelete($cmsAuthor));
    }
}
