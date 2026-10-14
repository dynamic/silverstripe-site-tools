<?php

namespace Dynamic\SiteTools\Tests\Traits;

use Dynamic\SiteTools\Test\TestOnly\Traits\TraitSubject;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class OutputYieldTraitTest
 *
 * Covers the 6.1.0 deprecation notices and the behaviour they wrap: both yield traits still produce the same
 * key/value sequences (including for an empty list), stay lazy about starting their argument, and OutputTrait
 * still echoes - or stays silent when enable_output is off.
 *
 * The notice-capturing helper below duplicates Dynamic\SiteTools\Test\TestOnly\DeprecationNoticeCapture from
 * dynamic/silverstripe-site-tools#51, which is still open and so is not on this branch's base. Whichever of the
 * two merges second should switch to the shared trait.
 */
class OutputYieldTraitTest extends SapphireTest
{
    /**
     * The error handler withCapturedNotices() replaced, kept on the object so the closure below can reach it
     * without a two-parameter use() clause (phpcs requires one parameter per line in a multi-line one).
     *
     * @var callable|null
     */
    private $replacedNoticeHandler = null;

    /**
     * Run $callback with deprecation notices turned on and captured, then restore the previous Deprecation
     * state so later tests in the same process are unaffected.
     *
     * Deprecation buffers notices and emits them from a shutdown function, so they have to be flushed
     * (outputNotices()) while the handler below is still in place to be observable inside a test.
     *
     * @param callable $callback Work to run. It is passed the running list of captured notices, which it may
     *                           take by reference to see what has been raised so far.
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
            $callback($notices);
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

    /**
     * Run $callback with PHP errors captured instead of reported, so a warning can be asserted on.
     *
     * @param callable $callback Work to run
     * @return array List of ["errno" => int, "errstr" => string] for every error raised
     */
    private function withCapturedErrors(callable $callback): array
    {
        $errors = [];
        $handler = function (int $errno, string $errstr) use (&$errors): bool {
            $errors[] = ['errno' => $errno, 'errstr' => $errstr];
            return true;
        };
        set_error_handler($handler);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }
        return $errors;
    }

    /**
     * Assert that exactly one of the captured notices is the deprecation this test is about.
     *
     * Matching is by message rather than by counting everything captured, because outputNotices() flushes
     * whatever Deprecation had already buffered - so an unrelated deprecation raised earlier in the process
     * lands in this list too. CI has been observed to buffer one that the local DDEV run does not, which made
     * a bare count of the whole list environment-dependent. Filtering still pins the thing that matters: that
     * this call raised its own notice exactly once, naming itself.
     *
     * @param array $notices Messages captured by withCapturedNotices()
     * @param string $needle The "<Trait>::<method>() is deprecated." text the notice must contain
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

    /**
     * Deprecation::notice() de-duplicates by message within one flush, so each notice assertion gets its
     * own call. Global scope is what makes the notice visible at all from a trait method, and it means the
     * message names the trait and method itself rather than being prefixed with a caller.
     */
    public function testOutputRaisesDeprecationNotice(): void
    {
        $subject = new TraitSubject();
        $notices = $this->withCapturedNotices(function () use ($subject) {
            ob_start();
            $subject->output('hello');
            ob_end_clean();
        });
        $this->assertDeprecationNoticeRaised($notices, 'OutputTrait::output() is deprecated.');
    }

    /**
     * The notice wraps the method without changing what it does: with output enabled the string is still
     * echoed, with the CLI line ending appended.
     *
     * enable_output is set explicitly rather than relied on from its declaration: the trait's
     * "private static $enable_output = true" is not picked up once SilverStripe's config manifest is cached
     * (the echo silently stops happening after a flushed dev/build), so a test that assumed the declared
     * default would only pass against a cold manifest. Filed as a follow-up issue; see the PR body.
     */
    public function testOutputStillEchoesStringWithLineEnding(): void
    {
        Config::modify()->set(TraitSubject::class, 'enable_output', true);
        $subject = new TraitSubject();
        $this->withCapturedNotices(function () use ($subject) {
            ob_start();
            $subject->output('hello');
            $output = ob_get_clean();
            $this->assertSame('hello' . PHP_EOL, $output);
        });
    }

    /**
     * The enable_output config still suppresses the echo - and disabling it is a clean no-op, not an error.
     */
    public function testOutputDoesNotEchoWhenOutputDisabled(): void
    {
        Config::modify()->set(TraitSubject::class, 'enable_output', false);
        $subject = new TraitSubject();
        $this->withCapturedNotices(function () use ($subject) {
            ob_start();
            $subject->output('hello');
            $output = ob_get_clean();
            $this->assertSame('', $output);
        });
    }

    /**
     * Characterisation test for the assumption the two tests above make by setting enable_output explicitly:
     * what output() echoes is exactly what the resolved config says, and the declared default
     * ("private static $enable_output = true" in the trait) resolves differently depending on whether
     * SilverStripe's config manifest is cached - it is true on a cold manifest and null after a flushed
     * dev/build, so it is not a stable fixture for an assertion. This asserts the echo/config coupling rather
     * than a fixed default, so it holds in either manifest state and fails if output() ever stops honouring
     * the flag.
     */
    public function testDeclaredEnableOutputDefaultIsNotStableAcrossManifestStates(): void
    {
        $subject = new TraitSubject();
        $resolved = $subject->config()->get('enable_output');
        $output = null;
        $this->withCapturedNotices(function () use ($subject, &$output) {
            ob_start();
            $subject->output('hello');
            $output = ob_get_clean();
        });
        if ($resolved) {
            $this->assertSame('hello' . PHP_EOL, $output);
        } else {
            $this->assertSame('', $output);
        }
    }

    /**
     * @return array One row per yield method: the method name and the notice text it must raise
     */
    public static function deprecatedMethodProvider(): array
    {
        return [
            'yieldSingle' => ['yieldSingle', 'YieldTrait::yieldSingle() is deprecated.'],
            'yieldKeyVal' => ['yieldKeyVal', 'YieldTrait::yieldKeyVal() is deprecated.'],
        ];
    }

    /**
     * Both yield methods raise their own notice, naming themselves, when called.
     */
    #[DataProvider('deprecatedMethodProvider')]
    public function testYieldMethodsRaiseDeprecationNotice(string $method, string $expected): void
    {
        $subject = new TraitSubject();
        $notices = $this->withCapturedNotices(function () use ($subject, $method) {
            $subject->$method(['a' => 'b']);
        });
        $this->assertDeprecationNoticeRaised($notices, $expected);
    }

    /**
     * The notice is raised when the method is called, not when the returned generator is first iterated, and
     * the argument is still not started until iteration begins.
     */
    public function testNoticeFiresOnCallAndArgumentStaysLazy(): void
    {
        $subject = new TraitSubject();
        $list = new class (['a', 'b']) extends \ArrayIterator {
            /**
             * @var bool
             */
            public $started = false;

            public function rewind(): void
            {
                $this->started = true;
                parent::rewind();
            }
        };

        $raisedBeforeIteration = [];
        $notices = $this->withCapturedNotices(function (&$captured) use ($subject, $list, &$raisedBeforeIteration) {
            $generator = $subject->yieldSingle($list);
            $this->assertInstanceOf(\Generator::class, $generator);
            $this->assertFalse($list->started, 'The argument must not be iterated when the generator is created.');
            // Flush inside the capture window while only the call has happened: anything raised lazily, from
            // inside the generator body, cannot be in $captured at this point because nothing has iterated yet.
            Deprecation::outputNotices();
            $raisedBeforeIteration = $captured;
            $this->assertSame(['a', 'b'], iterator_to_array($generator));
        });

        // The call alone had already raised its notice, and iterating added nothing further to the count.
        $this->assertDeprecationNoticeRaised($raisedBeforeIteration, 'YieldTrait::yieldSingle() is deprecated.');
        $this->assertDeprecationNoticeRaised($notices, 'YieldTrait::yieldSingle() is deprecated.');
    }

    /**
     * yieldSingle() still yields the same values, in the same order.
     */
    public function testYieldSingleStillYieldsValues(): void
    {
        $subject = new TraitSubject();
        $result = [];
        $this->withCapturedNotices(function () use ($subject, &$result) {
            $result = iterator_to_array($subject->yieldSingle(['first', 'second', 'third']));
        });
        $this->assertSame(['first', 'second', 'third'], $result);
    }

    /**
     * yieldKeyVal() still yields the same keys and values, in the same order.
     */
    public function testYieldKeyValStillYieldsKeyValues(): void
    {
        $subject = new TraitSubject();
        $result = [];
        $this->withCapturedNotices(function () use ($subject, &$result) {
            $result = iterator_to_array($subject->yieldKeyVal(['one' => 1, 'two' => 2]));
        });
        $this->assertSame(['one' => 1, 'two' => 2], $result);
    }

    /**
     * An empty list is an empty generator for both methods, not an error.
     */
    public function testYieldMethodsOnEmptyListYieldNothing(): void
    {
        $subject = new TraitSubject();
        $single = null;
        $keyVal = null;
        $this->withCapturedNotices(function () use ($subject, &$single, &$keyVal) {
            $single = iterator_to_array($subject->yieldSingle([]));
            $keyVal = iterator_to_array($subject->yieldKeyVal([]));
        });
        $this->assertSame([], $single);
        $this->assertSame([], $keyVal);
    }

    /**
     * A non-iterable argument behaves exactly as it did before the notice was added: creating the generator
     * is silent, and iterating it raises the single PHP warning that foreach() always raises for an int,
     * rather than a new exception type. The outer notice capture only exists so the notices these calls raise
     * are consumed here instead of being flushed by a later test.
     */
    public function testYieldMethodsOnNonIterableWarnOnlyOnIteration(): void
    {
        $subject = new TraitSubject();
        $this->withCapturedNotices(function () use ($subject) {
            foreach (['yieldSingle', 'yieldKeyVal'] as $method) {
                $creationErrors = $this->withCapturedErrors(function () use ($subject, $method) {
                    $this->assertInstanceOf(\Generator::class, $subject->$method(1));
                });
                $this->assertSame([], $creationErrors, $method . ' must not warn when the generator is created.');

                $iterationErrors = $this->withCapturedErrors(function () use ($subject, $method) {
                    $this->assertSame([], iterator_to_array($subject->$method(1)));
                });
                $this->assertCount(1, $iterationErrors);
                $this->assertSame(E_WARNING, $iterationErrors[0]['errno']);
                $this->assertStringContainsString(
                    'argument must be of type array|object',
                    $iterationErrors[0]['errstr']
                );
            }
        });
    }
}
