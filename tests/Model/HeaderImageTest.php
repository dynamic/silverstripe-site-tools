<?php

namespace Dynamic\SiteTools\Test\Model;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use Dynamic\SiteTools\Test\TestOnly\Page\BaseTestPage;
use Dynamic\SiteTools\Test\TestOnly\Page\DeepTestPage;
use Dynamic\SiteTools\Test\TestOnly\Page\MiddleTestPage;
use Dynamic\SiteTools\Test\TestOnly\UnqueryableOwnerClassHeaderImage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ErrorPage\ErrorPage;
use ReflectionMethod;
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
     * page classes and check the owner is found through either of them, and to a page class that
     * has page classes of its own below it, which is the shape that makes the owner walk skip
     * classes it cannot use.
     *
     * @var array<string, array<int, string>>
     */
    protected static $required_extensions = [
        RedirectorPage::class => [HeaderImageExtension::class],
        ErrorPage::class => [HeaderImageExtension::class],
        BaseTestPage::class => [HeaderImageExtension::class],
    ];

    /**
     * @var string[]
     */
    protected static $fixture_file = 'HeaderImageTest.yml';

    /**
     * The test page classes below are TestOnly, and the temp database skips TestOnly classes
     * unless they are listed here, which is why the page tables were missing without it.
     *
     * @var string[]
     */
    protected static $extra_dataobjects = [
        BaseTestPage::class,
        MiddleTestPage::class,
        DeepTestPage::class,
    ];

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
            'page inheriting the header image column' => [
                MiddleTestPage::class, 'middle', 'middle',
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
     * An image owned by a page below a class the owner walk skips - the concrete one whose
     * inherited column was already searched higher up - is still resolved through its owner.
     * Getting the skipping wrong finds no owner at all, which falls back to site-wide page
     * permissions and would grant this member. The other kind of skip, a page class that cannot
     * be queried at all, is covered by testDescendantsOfAnUnqueryableOwnerClassAreStillSearched().
     *
     * @return void
     */
    public function testOwnersBelowSkippedPageClassesAreFound(): void
    {
        $cmsAuthor = $this->objFromFixture(Member::class, 'cmsauthor');
        $page = $this->objFromFixture(DeepTestPage::class, 'deep');
        $image = $this->objFromFixture(HeaderImage::class, 'deep');

        $this->assertFalse(
            $page->canEdit($cmsAuthor),
            'Sanity check: the owning page is closed to a plain CMS author'
        );
        $this->assertFalse(
            $image->canEdit($cmsAuthor),
            'An image owned by a deep page must not fall back to site-wide page permissions'
        );
        $this->assertFalse($image->canView($cmsAuthor));
    }

    /**
     * Every owner is returned once. A class that inherits the HeaderImageID column finds the same
     * owners as the class declaring it, and a class skipped for that reason must not let its own
     * subclasses be queried either - the deduplication has to look at every ancestor, not just at
     * the class a page extends.
     *
     * @return void
     */
    public function testEachOwnerIsReturnedOnce(): void
    {
        $image = $this->objFromFixture(HeaderImage::class, 'deep');
        $page = $this->objFromFixture(DeepTestPage::class, 'deep');

        $method = new ReflectionMethod(HeaderImage::class, 'getOwnerPages');
        $method->setAccessible(true);

        /** @var SiteTree[] $owners */
        $owners = $method->invoke($image);
        $ids = array_map(function (SiteTree $owner): int {
            return (int)$owner->ID;
        }, $owners);

        $this->assertSame(
            [$page->ID],
            $ids,
            'The owner walk must query one table per inherited column, not one per page class'
        );
    }

    /**
     * The page class that declares the HeaderImage relation is skipped when it cannot be queried,
     * and being skipped must not count as having searched the page classes below it. That is the
     * shape a site takes when HeaderImageExtension is applied to an abstract page base class.
     *
     * The skip is driven through {@see UnqueryableOwnerClassHeaderImage} rather than by declaring
     * an abstract page class, because an abstract DataObject class cannot be in the class
     * manifest while the test database is built - see that class for the mechanism.
     *
     * @return void
     */
    public function testDescendantsOfAnUnqueryableOwnerClassAreStillSearched(): void
    {
        $cmsAuthor = $this->objFromFixture(Member::class, 'cmsauthor');
        $image = $this->objFromFixture(HeaderImage::class, 'deep');
        $owner = $this->objFromFixture(DeepTestPage::class, 'deep');

        $this->assertFalse(
            $owner->canEdit($cmsAuthor),
            'Sanity check: the owning page is closed to a plain CMS author'
        );

        $probe = UnqueryableOwnerClassHeaderImage::create();
        $probe->ID = (int)$image->ID;
        $probe->unqueryableClasses = [BaseTestPage::class];

        // Granting here would mean no owner was found at all and the check fell back to site-wide
        // page permissions - the outcome a skipped class used to hand to the classes below it.
        $this->assertFalse(
            $probe->canEdit($cmsAuthor),
            'A page class the owner walk skips must not silence the classes below it'
        );

        $method = new ReflectionMethod(HeaderImage::class, 'getOwnerPages');
        $method->setAccessible(true);

        /** @var SiteTree[] $owners */
        $owners = $method->invoke($probe);
        $ids = array_map(function (SiteTree $page): int {
            return (int)$page->ID;
        }, $owners);

        $this->assertSame(
            [$owner->ID],
            $ids,
            'The classes below a skipped page class must be searched, and searched once each'
        );
    }

    /**
     * A member the restricted owner itself lists is granted, on the same images the plain CMS
     * author is denied. Without this the deny assertions above would only show the new code does
     * not wrongly grant: the empty SiteTree the old code asked denies everyone, so every
     * assertFalse there passes against the old code as well.
     *
     * @return void
     */
    public function testRestrictedOwnerGrantsItsOwnEditors(): void
    {
        $pageEditor = $this->objFromFixture(Member::class, 'pageeditor');

        $owners = [
            'restricted RedirectorPage' => [
                RedirectorPage::class, 'restricted', 'restrictedredirector',
            ],
            'restricted ErrorPage' => [
                ErrorPage::class, 'restricted', 'restrictederror',
            ],
            'page two levels below the declaring class' => [
                DeepTestPage::class, 'deep', 'deep',
            ],
        ];

        foreach ($owners as $label => $spec) {
            list($class, $pageKey, $imageKey) = $spec;
            $page = $this->objFromFixture($class, $pageKey);
            $image = $this->objFromFixture(HeaderImage::class, $imageKey);

            $this->assertTrue(
                $page->canEdit($pageEditor),
                "Sanity check: {$label} is open to the member it lists"
            );
            $this->assertTrue(
                $image->canEdit($pageEditor),
                "{$label}'s header image must be editable by that member"
            );
            $this->assertTrue(
                $image->canView($pageEditor),
                "{$label}'s header image must be viewable by that member"
            );
        }
    }

    /**
     * An image pointed at by more than one page is only as open as its most restricted owner.
     * The add-existing autocompleter on the HeaderImage field links any image to a page without
     * checking who else already owns it, so granting on one permissive owner would let a member
     * who can edit an open page edit the image shown on a page they cannot - and since HeaderImage
     * is not versioned, that change would go live immediately.
     *
     * @return void
     */
    public function testSharedImageFollowsItsMostRestrictedOwner(): void
    {
        $cmsAuthor = $this->objFromFixture(Member::class, 'cmsauthor');
        $pageEditor = $this->objFromFixture(Member::class, 'pageeditor');
        $image = $this->objFromFixture(HeaderImage::class, 'shared');

        $open = $this->objFromFixture(RedirectorPage::class, 'sharedopen');
        $restricted = $this->objFromFixture(ErrorPage::class, 'sharedrestricted');

        $this->assertTrue(
            $open->canEdit($cmsAuthor),
            'Sanity check: one owner is open to a plain CMS author'
        );
        $this->assertFalse(
            $restricted->canEdit($cmsAuthor),
            'Sanity check: the other owner is not'
        );

        $this->assertFalse(
            $image->canEdit($cmsAuthor),
            'A permissive owner must not unlock an image a restricted page also owns'
        );
        $this->assertFalse($image->canView($cmsAuthor), 'The same member must not see it either');
        $this->assertFalse($image->canDelete($cmsAuthor), 'nor delete it');

        // Both owners list this member, so the record stays reachable for them.
        $this->assertTrue($open->canEdit($pageEditor), 'Sanity check: both owners list this member');
        $this->assertTrue($restricted->canEdit($pageEditor));
        $this->assertTrue($image->canEdit($pageEditor), 'A member every owner lists is still granted');
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
