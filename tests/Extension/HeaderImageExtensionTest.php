<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use Dynamic\SiteTools\Tests\TestOnly\Page\TestPage;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\Versioned\Versioned;

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

    /**
     * A header link is versioned, so it only renders on a published page once the header
     * image has published it alongside itself.
     */
    public function testHeaderLinkIsPublishedWithItsHeaderImage()
    {
        $link = ExternalLink::create();
        $link->ExternalUrl = 'https://example.com';
        $link->write();

        $headerImage = HeaderImage::create();
        $headerImage->Title = 'Linked header';
        $headerImage->HeaderLinkID = $link->ID;
        $headerImage->write();
        $headerImage->publishRecursive();

        $this->assertNotNull(
            Versioned::get_by_stage(Link::class, Versioned::DRAFT)->byID($link->ID),
            'The link is still available on the draft stage.'
        );

        $this->assertNotNull(
            Versioned::get_by_stage(Link::class, Versioned::LIVE)->byID($link->ID),
            'Publishing the header image publishes the link it points at.'
        );

        Versioned::withVersionedMode(function () use ($headerImage) {
            Versioned::set_stage(Versioned::LIVE);

            $liveHeaderImage = HeaderImage::get()->byID($headerImage->ID);
            $this->assertNotNull($liveHeaderImage);
            $this->assertTrue(
                $liveHeaderImage->HeaderLink()->exists(),
                'The live header image resolves its header link.'
            );
        });
    }
}
