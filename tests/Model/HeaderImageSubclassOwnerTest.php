<?php

namespace Dynamic\SiteTools\Test\Model;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * Class HeaderImageSubclassOwnerTest
 *
 * Covers the half of HeaderImage::getOwnerPages() that skips a page class whose ancestor already
 * carries the HeaderImage column: the skip is only sound because a class list is scoped to its own
 * class plus its descendants, so the carrier has to find an image owned by a page below it.
 * HeaderImageTest deliberately does not extend Page, and so cannot reach this case.
 */
class HeaderImageSubclassOwnerTest extends SapphireTest
{
    /**
     * Page is the class that carries the column here; VirtualPage extends it and inherits the
     * relation without having a HeaderImageID column of its own.
     *
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
     * An image owned by a page below the class that carries the column is still governed by that
     * page, even though the owner walk never queries the descendant's class.
     */
    public function testOwnerBelowTheCarryingClassIsFound(): void
    {
        $member = Member::get()->byID($this->logInWithPermission('CMS_ACCESS_CMSMain'));
        $this->assertFalse(
            Permission::checkMember($member, 'SITETREE_EDIT_ALL'),
            'The member must not hold the global edit permission, or every page check short circuits.'
        );

        $editors = Group::create(['Title' => 'Editors of the virtual owner']);
        $editors->write();

        $owner = VirtualPage::create([
            'Title' => 'Virtual restricted owner',
            'CanEditType' => 'OnlyTheseUsers',
        ]);
        $owner->write();
        $owner->EditorGroups()->add($editors);

        $image = HeaderImage::create(['Title' => 'Owned by a virtual page']);
        $image->write();

        $owner->HeaderImageID = $image->ID;
        $owner->write();

        $this->assertSame(
            $image->ID,
            (int)VirtualPage::get()->byID($owner->ID)->HeaderImageID,
            'The pointer to the image is written on the descendant page.'
        );

        $this->assertFalse(
            $image->canEdit($member),
            'The restricted descendant page governs the image even though it does not carry the column.'
        );
    }
}
