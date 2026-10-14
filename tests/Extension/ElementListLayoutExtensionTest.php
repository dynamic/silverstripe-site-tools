<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\ElementListLayoutExtension;
use Dynamic\SiteTools\Test\TestOnly\DeprecationNoticeCapture;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Class ElementListLayoutExtensionTest
 *
 * Covers the 6.1.0 deprecation notice and the field the extension adds, which the notice does not change.
 */
class ElementListLayoutExtensionTest extends SapphireTest
{
    use DeprecationNoticeCapture;

    /**
     * @var array
     */
    protected static $required_extensions = [
        \Page::class => [
            ElementListLayoutExtension::class,
        ],
    ];

    /**
     * getCMSFields() raises the notice and still adds the Columns dropdown it has always added.
     */
    public function testUpdateCMSFieldsRaisesDeprecationNotice(): void
    {
        $notices = $this->withCapturedNotices(function () {
            return Injector::inst()->create(\Page::class)->getCMSFields();
        });
        $this->assertNotEmpty($notices);
        $this->assertStringContainsString(
            'ElementListLayoutExtension is deprecated.',
            implode("\n", $notices)
        );
        $this->assertStringContainsString(
            'Will be removed without equivalent functionality to replace it in a future major release.',
            implode("\n", $notices)
        );
    }

    /**
     * The notice wraps the hook without changing what it produces.
     */
    public function testUpdateCMSFieldsAddsColumnsField(): void
    {
        $fields = Injector::inst()->create(\Page::class)->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('Columns'));
    }
}
