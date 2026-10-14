<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\PrevNextExtension;
use Dynamic\SiteTools\Test\TestOnly\DeprecationNoticeCapture;
use SilverStripe\Dev\SapphireTest;

/**
 * Class PrevNextExtensionTest
 *
 * Covers the 6.1.0 deprecation notice as well as the behaviour the notice wraps: the extension still
 * returns the neighbouring page, and still returns false for both a bad mode and an empty result.
 */
class PrevNextExtensionTest extends SapphireTest
{
    use DeprecationNoticeCapture;

    /**
     * @var array
     */
    protected static $required_extensions = [
        \Page::class => [
            PrevNextExtension::class,
        ],
    ];

    /**
     * Build a parent with two children in the same section, ordered A then B.
     *
     * @return array List of ["parent" => Page, "first" => Page, "last" => Page]
     */
    private function createSiblingPages(): array
    {
        $parent = \Page::create(['Title' => 'Section']);
        $parent->write();
        $first = \Page::create(['Title' => 'First', 'ParentID' => $parent->ID, 'Sort' => 1]);
        $first->write();
        $last = \Page::create(['Title' => 'Last', 'ParentID' => $parent->ID, 'Sort' => 2]);
        $last->write();
        return ['parent' => $parent, 'first' => $first, 'last' => $last];
    }

    /**
     * Deprecation::notice() de-duplicates by message, so each notice assertion gets its own call.
     */
    public function testPrevNextRaisesDeprecationNotice(): void
    {
        $pages = $this->createSiblingPages();
        $notices = $this->withCapturedNotices(function () use ($pages) {
            return $pages['first']->PrevNext('next');
        });
        $this->assertCount(1, $notices);
        $this->assertStringContainsString('PrevNextExtension::PrevNext() is deprecated.', $notices[0]);
        $this->assertStringContainsString(
            'Will be removed without equivalent functionality to replace it in a future major release.',
            $notices[0]
        );
    }

    /**
     * The notice wraps the method without changing what it returns: "next" gives the following sibling.
     */
    public function testPrevNextReturnsNextSibling(): void
    {
        $pages = $this->createSiblingPages();
        $this->assertSame($pages['last']->ID, $pages['first']->PrevNext('next')->ID);
    }

    /**
     * "prev" gives the preceding sibling.
     */
    public function testPrevNextReturnsPreviousSibling(): void
    {
        $pages = $this->createSiblingPages();
        $this->assertSame($pages['first']->ID, $pages['last']->PrevNext('prev')->ID);
    }

    /**
     * An unrecognised mode is a clean false, not an exception, and the notice still fires.
     */
    public function testPrevNextBadModeReturnsFalse(): void
    {
        $pages = $this->createSiblingPages();
        $notices = $this->withCapturedNotices(function () use ($pages) {
            return $pages['first']->PrevNext('sideways');
        });
        $this->assertFalse($pages['first']->PrevNext('sideways'));
        $this->assertCount(1, $notices);
    }

    /**
     * No matching sibling is an empty result, not an error: the last page has no "next".
     */
    public function testPrevNextNoMatchReturnsFalse(): void
    {
        $pages = $this->createSiblingPages();
        $this->assertFalse($pages['last']->PrevNext('next'));
        $this->assertFalse($pages['first']->PrevNext('prev'));
    }
}
