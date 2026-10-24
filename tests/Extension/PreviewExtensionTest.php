<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Tests\TestOnly\Page\TestPage;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use Dynamic\SiteTools\Extension\PreviewExtension;
use SilverStripe\Forms\FieldList;

/**
 * Class PreviewExtensionTest.
 */
class PreviewExtensionTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = array(
        '../fixtures.yml',
    );

    /**
     * @var array
     */
    protected static $required_extensions = [
        \Page::class => [
            PreviewExtension::class,
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
        $this->assertNotNull($fields->dataFieldByName('PreviewTitle'));
        $this->assertNotNull($fields->dataFieldByName('Abstract'));
        $this->assertNotNull($fields->dataFieldByName('PreviewImage'));
    }

    /**
     * Tests the preview thumbnail field description in each configuration case.
     */
    public function testPreviewThumbnailDescription()
    {
        // no description config set: the default description applies
        $object = Injector::inst()->create(\Page::class);
        $fields = $object->getCMSFields();
        $thumbnail = $fields->dataFieldByName('PreviewImage');
        $this->assertEquals('optional, small image displayed with preview', $thumbnail->getDescription());

        // only customThumbnailTitle set: its value becomes the description
        Config::modify()->set(\Page::class, 'customThumbnailTitle', 'custom thumbnail title');
        $object = Injector::inst()->create(\Page::class);
        $fields = $object->getCMSFields();
        $thumbnail = $fields->dataFieldByName('PreviewImage');
        $this->assertEquals('custom thumbnail title', $thumbnail->getDescription());

        // preview_thumbnail_field_description set: its value becomes the description
        Config::modify()->set(\Page::class, 'preview_thumbnail_field_description', 'custom description');
        $object = Injector::inst()->create(\Page::class);
        $fields = $object->getCMSFields();
        $thumbnail = $fields->dataFieldByName('PreviewImage');
        $this->assertEquals('custom description', $thumbnail->getDescription());
    }

    /**
     * Tests that a configured field description wins over customThumbnailTitle.
     */
    public function testPreviewThumbnailDescriptionPrecedence()
    {
        Config::modify()->set(\Page::class, 'customThumbnailTitle', 'custom thumbnail title');
        Config::modify()->set(\Page::class, 'preview_thumbnail_field_description', 'custom description');
        $object = Injector::inst()->create(\Page::class);
        $fields = $object->getCMSFields();
        $thumbnail = $fields->dataFieldByName('PreviewImage');
        $this->assertEquals('custom description', $thumbnail->getDescription());
    }

    /**
     *
     */
    public function testGetPreviewHeadline()
    {
        $object = Injector::inst()->create(\Page::class);
        $this->assertEquals($object->getPreviewHeadline(), $object->Title);

        $object->PreviewTitle = 'Preview';
        $this->assertEquals($object->getPreviewHeadline(), $object->PreviewTitle);
    }

    /**
     *
     */
    public function testGetPreviewThumb()
    {
        $object = Injector::inst()->create(\Page::class);
        $image = $this->objFromFixture(Image::class, 'preview');
        $this->assertFalse($object->getPreviewThumb());

        $object->PreviewImageID = $image->ID;
        $this->assertInstanceOf(Image::class, $object->getPreviewThumb());
    }

    /**
     *
     */
    public function testGetPreviewAbstract()
    {
        $object = Injector::inst()->create(\Page::class);
        $this->assertEquals($object->getPreviewAbstract(), $object->obj('Content')->FirstParagraph());

        $object->Abstract = 'Preview';
        $this->assertEquals($object->getPreviewAbstract(), $object->Abstract);
    }
}
