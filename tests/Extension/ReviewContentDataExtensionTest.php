<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\ReviewContentDataExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class ReviewContentDataExtensionTest.
 */
class ReviewContentDataExtensionTest extends SapphireTest
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
        SiteConfig::class => [
            ReviewContentDataExtension::class,
        ]
    ];

    /**
     *
     */
    public function testUpdateCMSFields()
    {
        $siteConfig = Injector::inst()->create(SiteConfig::class);
        $fields = $siteConfig->getCMSFields();

        $this->assertInstanceOf(FieldList::class, $fields);

        $advancedTab = $fields->fieldByName('Root.Advanced');
        $this->assertInstanceOf(Tab::class, $advancedTab);

        $reviewContent = $fields->dataFieldByName('ReviewContent');
        $this->assertInstanceOf(CheckboxField::class, $reviewContent);
        $this->assertEquals('Show Review Content', $reviewContent->Title());
        $this->assertEquals(
            'Display placeholder text and images. Useful during website build.',
            $reviewContent->getDescription()
        );
    }
}
