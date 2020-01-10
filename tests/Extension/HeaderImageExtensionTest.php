<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use Dynamic\SiteTools\Tests\TestOnly\Page\TestPage;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;

/**
 * Class HeaderImageDataExtensionTest.
 */
class HeaderImageExtensionTest extends SapphireTest
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
    protected static $required_extensions = [
        \Page::class => [
            HeaderImageExtension::class,
        ]
    ];

    /**
     *
     */
    public function testUpdateCMSFields()
    {
        $object = Injector::inst()->create(\Page::class);
        $fields = $object->getCMSFields();

        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('HeaderImage'));
    }

    /**
     *
     */
    public function testGetPageHeaderImage()
    {
        $page = Injector::inst()->create(\Page::class);
        $subpage = Injector::inst()->create(\Page::class);
        $subpage->ParentID = $page->ID;
        $image = $this->objFromFixture(HeaderImage::class, 'header');

        $this->assertNull($subpage->getPageHeaderImage());

        $page->HeaderImageID = $image->ID;
        $page->write();

        $this->assertInstanceOf(HeaderImage::class, $page->getPageHeaderImage());

        $subpage->HeaderImageID = $image->ID;
        $subpage->write();
        $this->assertInstanceOf(HeaderImage::class, $subpage->getPageHeaderImage());
    }
}
