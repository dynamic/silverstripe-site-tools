<?php

namespace Dynamic\SiteTools\Test\TestOnly;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\Deprecation;

/**
 * Capture SilverStripe deprecation notices raised by the code under test.
 *
 * Deprecation buffers notices and emits them from a shutdown function, so they have to be flushed
 * (outputNotices()) while the handler below is still in place to be observable inside a test. Any other
 * E_USER_DEPRECATED notice buffered at that moment is captured by this handler too, and so is not reported
 * again at process shutdown - which only affects the test process, as notices are off by default. Because that
 * flush is process-wide, assertions must match notices by message rather than count the whole captured list:
 * see assertDeprecationNoticeRaised().
 *
 * This is the shared helper dynamic/silverstripe-site-tools#56 inlines a private copy of. Whichever of the two
 * branches merges second should drop its copy and use this trait.
 *
 * Known limitation: Deprecation::enable() also resets Deprecation::$showNoReplacementNotices to false, and
 * there is no accessor for that flag, so a caller that had enabled it is not restored by the finally block.
 * Restoring it would mean writing to framework private statics by reflection.
 */
trait DeprecationNoticeCapture
{
    /**
     * The error handler withCapturedNotices() replaced, kept on the object so the closure below can reach it
     * without a two-parameter use() clause (phpcs requires one parameter per line in a multi-line one).
     *
     * @var callable|null
     */
    private $replacedNoticeHandler = null;

    /**
     * Pin SS_DEPRECATION_ENABLED to a truthy value for the duration of $callback, then restore the environment.
     *
     * Deprecation::isEnabled() lets that variable override Deprecation::enable() - with it set falsy, enable()
     * is silently ignored and no notice is ever emitted - so a host exporting SS_DEPRECATION_ENABLED=false, or a
     * project's .env setting it so, would turn every notice assertion into a failure that has nothing to do with
     * the code under test. Environment::getEnv() reads its own static store before $_ENV, $_SERVER and
     * getenv(), so pinning through Environment::setEnv() covers the process environment and a .env file alike;
     * getVariables()/setVariables() is that store's own backup-and-restore pair and puts it back afterwards.
     *
     * @param callable $callback Work to run with the variable pinned
     * @return mixed The callback's return value
     */
    private function withDeprecationEnvPinned(callable $callback): mixed
    {
        $saved = Environment::getVariables();
        Environment::setEnv('SS_DEPRECATION_ENABLED', '1');
        try {
            return $callback();
        } finally {
            Environment::setVariables($saved);
        }
    }

    /**
     * Run $callback with deprecation notices turned on and captured, then restore the previous
     * Deprecation state so later tests in the same process are unaffected.
     *
     * One corner is not restored: where the host already exports a truthy SS_DEPRECATION_ENABLED, isEnabled()
     * reports that variable rather than Deprecation::$currentlyEnabled, so $wasEnabled below is true whatever the
     * flag was and the disable() is skipped, leaving the flag true. Nothing reads it while the variable stands,
     * since the variable takes precedence for every later isEnabled() call in the process; it would only surface
     * if something cleared the variable afterwards.
     *
     * @param callable $callback Work to run; its return value is discarded
     * @return array List of captured E_USER_DEPRECATED messages
     */
    private function withCapturedNotices(callable $callback): array
    {
        $notices = [];
        // Read the prior state before the env pin: Deprecation::isEnabled() reports the pinned environment
        // rather than Deprecation::$currentlyEnabled once SS_DEPRECATION_ENABLED is set, so a read taken inside
        // the pinned window would always answer "was already enabled" and the disable() below would never run.
        $wasEnabled = Deprecation::isEnabled();
        return $this->withDeprecationEnvPinned(function () use ($callback, &$notices, $wasEnabled): array {
            Deprecation::enable();
            $handler = function (int $errno, string $errstr, string $errfile, int $errline) use (&$notices): bool {
                if ($errno === E_USER_DEPRECATED) {
                    $notices[] = $errstr;
                    return true;
                }
                // Hand anything else back to the handler this one replaced (PHPUnit's). Returning false instead
                // would route it to PHP's built-in handler, so a warning raised inside $callback would only be
                // printed rather than recorded against the test.
                if (is_callable($this->replacedNoticeHandler)) {
                    $previous = $this->replacedNoticeHandler;
                    return (bool) $previous($errno, $errstr, $errfile, $errline);
                }
                return false;
            };
            $this->replacedNoticeHandler = set_error_handler($handler);
            try {
                $callback();
                Deprecation::outputNotices();
            } finally {
                restore_error_handler();
                $this->replacedNoticeHandler = null;
                if (!$wasEnabled) {
                    Deprecation::disable();
                }
            }
            return $notices;
        });
    }

    /**
     * Assert that exactly one of the captured notices is the deprecation this test is about.
     *
     * Matching is by message rather than by counting everything captured, because outputNotices() flushes
     * whatever Deprecation had already buffered - and that buffer is process-wide, and is not cleared while
     * Deprecation is disabled - so an unrelated deprecation raised earlier in the run arrives in this list too.
     * A bare count of the list is therefore order- and environment-dependent: CI has been observed to hold a
     * second notice that a local run does not, which failed a count-based assertion there while it passed here.
     * Filtering still pins the thing that matters: that this call raised its own notice exactly once, naming
     * itself.
     *
     * @param array $notices Messages captured by withCapturedNotices()
     * @param string $needle The "<Class>::<method>() is deprecated." text the notice must contain
     * @return void
     */
    private function assertDeprecationNoticeRaised(array $notices, string $needle): void
    {
        $matching = array_values(array_filter($notices, function (string $notice) use ($needle): bool {
            return str_contains($notice, $needle);
        }));
        $this->assertCount(
            1,
            $matching,
            'Expected exactly one notice containing "' . $needle . '", got: ' . print_r($notices, true)
        );
        $this->assertStringContainsString(
            'Will be removed without equivalent functionality to replace it in a future major release.',
            $matching[0]
        );
    }
}
