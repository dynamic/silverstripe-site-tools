<?php

namespace Dynamic\SiteTools\Tests\Extension;

use Dynamic\SiteTools\Extension\PrevNextExtension;
use Dynamic\SiteTools\Test\TestOnly\DeprecationNoticeCapture;
use SilverStripe\Dev\Deprecation;
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
     * Every test in this class write()s pages, and this class has no fixture file, so the database has to be
     * declared explicitly: FixtureTestState::testNeedsDB() only answers true for an explicit $usesDatabase or a
     * fixture file, and until it does the per-test transaction rollback never runs. The writes reach the temp
     * DB either way (ExtensionTestState resets the schema for $required_extensions), which is why the tests pass
     * without it - but the rows pile up until the class finishes instead of rolling back per test.
     *
     * @var bool
     */
    protected $usesDatabase = true;

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
     * Deprecation::notice() de-duplicates by message, so each notice assertion gets its own call. Global scope
     * means the message comes back exactly as the extension writes it, with no caller name prepended, which is
     * what the assertion matches on.
     */
    public function testPrevNextRaisesDeprecationNotice(): void
    {
        $pages = $this->createSiblingPages();
        $notices = $this->withCapturedNotices(function () use ($pages) {
            return $pages['first']->PrevNext('next');
        });
        $this->assertDeprecationNoticeRaised($notices, 'PrevNextExtension::PrevNext() is deprecated.');
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
        $this->assertDeprecationNoticeRaised($notices, 'PrevNextExtension::PrevNext() is deprecated.');
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

    /**
     * Pins why assertDeprecationNoticeRaised() matches notices by message instead of counting the whole captured
     * list: withCapturedNotices() flushes Deprecation's entire buffer, and that buffer is process-wide, so a
     * notice some earlier code in the same run had already buffered arrives in the list too. A bare count of the
     * list is therefore order-dependent - the sibling job on CI has held a second notice where a local DDEV run
     * holds none, which failed a count-based assertion there while it passed here. This reproduces the skew
     * in-process, so a later "simplification" back to assertCount(1, $notices) cannot slip through on a machine
     * that happens to have nothing else buffered.
     */
    public function testForeignBufferedNoticeDoesNotSkewTheNoticeAssertions(): void
    {
        $pages = $this->createSiblingPages();
        $foreign = 'ForeignDeprecation::someMethod() is deprecated.';
        $notices = $this->withCapturedNotices(function () use ($pages, $foreign) {
            // Stands in for whatever else a CI job buffers before this class runs. Global scope for the same two
            // reasons as the notice under test: the message comes back exactly as written, and the
            // supported-module filter cannot see a caller to judge.
            Deprecation::notice('6.1.0', $foreign, Deprecation::SCOPE_GLOBAL);
            return $pages['first']->PrevNext('next');
        });

        // Precondition: the foreign notice really did land in the same captured list. Without this the test would
        // pass on a run where no second notice was ever captured, i.e. without testing the skew at all.
        $this->assertCount(
            1,
            array_values(array_filter($notices, function (string $notice) use ($foreign): bool {
                return str_contains($notice, $foreign);
            })),
            'Precondition failed: the unrelated notice was not captured alongside the one under test, got: '
            . print_r($notices, true)
        );

        // And the assertion helper still sees exactly one notice for the method under test, despite the list
        // holding at least two.
        $this->assertDeprecationNoticeRaised($notices, 'PrevNextExtension::PrevNext() is deprecated.');
    }

    /**
     * The capture helper must leave Deprecation as it found it, or every later test in the same process inherits
     * enabled notices and the shutdown handler that prints them. This is what pinning SS_DEPRECATION_ENABLED
     * could silently break: while that variable is set, Deprecation::isEnabled() stops reporting the static
     * flag, so a "was it enabled before?" read taken inside the pinned window is always true and nothing is ever
     * put back.
     */
    public function testDeprecationEnabledStateIsRestoredAfterCapture(): void
    {
        $pages = $this->createSiblingPages();
        $before = Deprecation::isEnabled();
        $this->withCapturedNotices(function () use ($pages) {
            return $pages['first']->PrevNext('next');
        });
        $this->assertSame($before, Deprecation::isEnabled());
    }
}
