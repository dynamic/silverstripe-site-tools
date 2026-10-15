<?php

namespace Dynamic\SiteTools\Tests\View;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Dynamic\SiteTools\Extension\ReviewContentDataExtension;
use Dynamic\SiteTools\Model\HeaderImage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class PageHeaderImageTemplateTest
 *
 * Covers templates/Includes/PageHeaderImage.ss: the call-to-action must render the URL the
 * silverstripe/linkfield Link actually exposes (Link::getURL(), there is no LinkURL), and the
 * hard-coded placeholder paragraph must respect the module's own "Display placeholder text"
 * switch (SiteConfig.ReviewContent) instead of showing whenever a header has no content.
 */
class PageHeaderImageTemplateTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $required_extensions = [
        \Page::class => [
            HeaderImageExtension::class,
        ],
        SiteConfig::class => [
            ReviewContentDataExtension::class,
        ],
    ];

    /**
     * Every test write()s records and this class has no fixture file, so the database has to be
     * declared explicitly for the per-test transaction rollback to run.
     *
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * Render the include for a page carrying the given header image.
     *
     * @param HeaderImage $headerImage The header image attached to the page.
     * @return string The rendered template.
     */
    private function renderHeader(HeaderImage $headerImage): string
    {
        $page = \Page::create(['Title' => 'Landing page']);
        $page->write();

        $headerImage->PageID = $page->ID;
        $headerImage->write();

        $page->HeaderImageID = $headerImage->ID;
        $page->write();

        return $page->renderWith('Includes/PageHeaderImage');
    }

    /**
     * Build a header image with a title, an external call-to-action and no content.
     *
     * @param string $url The external link URL.
     * @return HeaderImage
     */
    private function createHeaderWithLink(string $url): HeaderImage
    {
        $link = ExternalLink::create();
        $link->ExternalUrl = $url;
        $link->write();

        $headerImage = HeaderImage::create(['Title' => 'Talk to us']);
        $headerImage->HeaderLinkID = $link->ID;
        $headerImage->write();

        return $headerImage;
    }

    /**
     * The call-to-action renders the link's own URL, not the removed gorriecoe LinkURL API.
     */
    public function testCallToActionRendersTheLinkUrl(): void
    {
        $output = $this->renderHeader($this->createHeaderWithLink('https://example.com/landing'));

        $this->assertStringContainsString(
            'href="https://example.com/landing"',
            $output,
            'The call-to-action anchors on the link URL returned by Link::getURL().'
        );
        $this->assertStringNotContainsString(
            'href=""',
            $output,
            'The call-to-action does not render an empty href.'
        );
        $this->assertStringNotContainsString(
            'Go to the',
            $output,
            'The title attribute built from the never-existing SiteTree relation is gone.'
        );
    }

    /**
     * The Lorem ipsum placeholder only shows while the "Display placeholder text" switch is on.
     */
    public function testPlaceholderTextOnlyShowsWhenReviewContentIsOn(): void
    {
        $siteConfig = SiteConfig::current_site_config();

        $siteConfig->ReviewContent = false;
        $siteConfig->write();

        $output = $this->renderHeader($this->createHeaderWithLink('https://example.com/landing'));
        $this->assertStringNotContainsString(
            'Lorem ipsum',
            $output,
            'Placeholder text is hidden while ReviewContent is off.'
        );
        $this->assertStringNotContainsString(
            'typography missing',
            $output,
            'The placeholder block is not rendered at all while ReviewContent is off.'
        );

        $siteConfig->ReviewContent = true;
        $siteConfig->write();

        $output = $this->renderHeader($this->createHeaderWithLink('https://example.com/landing'));
        $this->assertStringContainsString(
            'Lorem ipsum',
            $output,
            'Placeholder text still shows while ReviewContent is on.'
        );
    }

    /**
     * Real content wins over the placeholder in both ReviewContent states, and a header without a
     * link renders no anchor at all.
     */
    public function testContentSuppressesPlaceholderAndMissingLinkRendersNoAnchor(): void
    {
        $siteConfig = SiteConfig::current_site_config();
        $siteConfig->ReviewContent = true;
        $siteConfig->write();

        $headerImage = $this->createHeaderWithLink('https://example.com/landing');
        $headerImage->Content = '<p>Real intro copy.</p>';
        $headerImage->write();

        $output = $this->renderHeader($headerImage);
        $this->assertStringContainsString('Real intro copy.', $output);
        $this->assertStringNotContainsString(
            'Lorem ipsum',
            $output,
            'A header with its own content never falls back to placeholder text.'
        );

        $headerless = HeaderImage::create(['Title' => 'No call to action']);
        $headerless->write();

        $output = $this->renderHeader($headerless);
        $this->assertStringNotContainsString('<a href=', $output);
        $this->assertStringContainsString('No call to action', $output);
    }
}
