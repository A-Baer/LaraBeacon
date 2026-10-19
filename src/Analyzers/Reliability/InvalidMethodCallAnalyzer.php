<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\ParsesPHPStanAnalysis;
use BaerSoftware\LaraBeacon\Analyzers\Trace;
use BaerSoftware\LaraBeacon\PHPStan;
use Illuminate\Support\Str;

class InvalidMethodCallAnalyzer extends ReliabilityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application does not contain invalid method calls.";

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MAJOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 5;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application seems to contain invalid method calls to methods that either do not exist or "
            ."do not match the method signature or scope.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\PHPStan $PHPStan
     * @return void
     */
    public function handle(PHPStan $PHPStan)
    {
        // Function calls are reported by InvalidFunctionCallAnalyzer; the broad patterns below would report them twice.
        $functionMessages = ['Function *', 'Parameter * of function *'];

        collect($PHPStan->match([
            'Method * invoked with *', 'Parameter * of method * is passed by reference, so *',
            'Unable to resolve the template *', 'Missing parameter * in call to *',
            'Unknown parameter * in call to *', 'Call to method * on an unknown class *',
            'Cannot call method * on *', 'Call to private method * of parent class *',
            'Call to an undefined method *', 'Call to * method * of class *',
            'Call to an undefined static method *', 'Static call to instance method *',
            'Calling *::* outside of class scope*', '*::* calls parent::* but *',
            'Call to static method * on an unknown class *', 'Cannot call static method * on *',
            'Cannot call abstract* method *::*', '* invoked with * parameter* required*',
            'Parameter * of * expects * given*', 'Result of * (void) is used*',
            'Result of method *',
        ]))->reject(function (Trace $trace) use ($functionMessages) {
            return Str::is($functionMessages, $trace->details);
        })->each(function (Trace $trace) {
            $this->addTrace($trace->path, $trace->lineNumber, $trace->details);
        });
    }
}
