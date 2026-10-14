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
 */
trait DeprecationNoticeCapture
{
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
        set_error_handler(function (int $errno, string $errstr) use (&$notices): bool {
            if ($errno === E_USER_DEPRECATED) {
                $notices[] = $errstr;
                return true;
            }
            return false;
        });
        try {
            $callback();
            Deprecation::outputNotices();
        } finally {
            restore_error_handler();
            if (!$wasEnabled) {
                Deprecation::disable();
            }
        }
        return $notices;
    }
}
