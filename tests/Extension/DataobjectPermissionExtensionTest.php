<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\DataobjectPermissionExtension;
use SilverStripe\Dev\SapphireTest;
use Dynamic\SiteTools\Tests\TestOnly\Model\TestContentAuthorObject;
use SilverStripe\Security\Member;

/**
 * Class ContentAuthorPermissionManagerTest.
 */
class DataobjectPermissionExtensionTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $fixture_file = array(
        '../fixtures.yml',
    );

    /**
     * @var array
     */
    protected static $extra_data_objects = array(
        TestContentAuthorObject::class,
    );

    /**
     * @var array
     */
    protected static $required_extensions = [
        TestContentAuthorObject::class => [
            DataobjectPermissionExtension::class,
        ]
    ];

    /**
     *
     */
    public function testCanView()
    {
        $object = new TestContentAuthorObject();

        $admin = $this->objFromFixture(
            Member::class,
            'admin'
        );
        $this->assertTrue($object->canView($admin));

        $member = $this->objFromFixture(
            Member::class,
            'default'
        );
        $this->assertTrue($object->canView($member));
    }

    /**
     *
     */
    public function testCanEdit()
    {
        $object = new TestContentAuthorObject();

        $admin = $this->objFromFixture(
            Member::class,
            'admin'
        );
        $this->assertTrue($object->canEdit($admin));

        $member = $this->objFromFixture(
            Member::class,
            'default'
        );
        $this->assertTrue($object->canEdit($member));
    }

    /**
     *
     */
    public function testCanDelete()
    {
        $object = new TestContentAuthorObject();

        $admin = $this->objFromFixture(
            Member::class,
            'admin'
        );
        $this->assertTrue($object->canDelete($admin));

        $member = $this->objFromFixture(
            Member::class,
            'default'
        );
        $this->assertTrue($object->canDelete($member));
    }

    /**
     *
     */
    public function testCanCreate()
    {
        $object = new TestContentAuthorObject();

        $admin = $this->objFromFixture(
            Member::class,
            'admin'
        );
        $this->assertTrue($object->canCreate($admin));

        $member = $this->objFromFixture(
            Member::class,
            'default'
        );
        $this->assertTrue($object->canCreate($member));
    }
}
