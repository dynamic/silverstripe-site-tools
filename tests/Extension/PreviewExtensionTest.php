<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Tests\TestOnly\Page\TestPage;
use SilverStripe\Assets\Image;
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
