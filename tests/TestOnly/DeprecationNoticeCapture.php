<?php

namespace Dynamic\SiteTools\Test\TestOnly;

use SilverStripe\Dev\Deprecation;

/**
 * Capture SilverStripe deprecation notices raised by the code under test.
 *
 * Deprecation buffers notices and emits them from a shutdown function, so they have to be flushed
 * (outputNotices()) while the handler below is still in place to be observable inside a test. Any other
 * E_USER_DEPRECATED notice buffered at that moment is captured by this handler too, and so is not reported
 * again at process shutdown - which only affects the test process, as notices are off by default.
 *
 * Known limitation: Deprecation::enable() also resets Deprecation::$showNoReplacementNotices to false, and
 * there is no accessor for that flag, so a caller that had enabled it is not restored by the finally block.
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
     * Run $callback with deprecation notices turned on and captured, then restore the previous
     * Deprecation state so later tests in the same process are unaffected.
     *
     * @param callable $callback Work to run; its return value is discarded
     * @return array List of captured E_USER_DEPRECATED messages
     */
    private function withCapturedNotices(callable $callback): array
    {
        $notices = [];
        $wasEnabled = Deprecation::isEnabled();
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
    }
}
