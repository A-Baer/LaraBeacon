<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\InspectsCode;
use BaerSoftware\LaraBeacon\Inspection\Inspector;
use BaerSoftware\LaraBeacon\Inspection\QueryBuilder;

class EnvCallAnalyzer extends PerformanceAnalyzer
{
    use InspectsCode;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not contain env function calls outside of your config files.';

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
    public $timeToFix = 10;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Your application contains env function calls outside of your config files. You must ensure that '
            .'you are calling the "env" function from within your configuration files. Once the configuration '
            .'has been cached, the .env file will not be loaded and all calls to the "env" function would '
            .'return null. This means that your code will not work when your configuration is cached.';
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\Inspection\Inspector $inspector
     * @return void
     */
    public function handle(Inspector $inspector)
    {
        $builder = (new QueryBuilder)->doesntHaveFunctionCall('env');

        $this->inspectCode($inspector, $builder);
    }
}
