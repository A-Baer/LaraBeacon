<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\CodeCorrection\ConfigManipulator;
use Illuminate\Filesystem\Filesystem;

class ConfigManipulatorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_baseline_configuration_with_php_parser_five()
    {
        $files = new Filesystem();
        $path = tempnam(sys_get_temp_dir(), 'larabeacon-config-');
        $files->put($path, "<?php\n\nreturn ['dont_report' => [], 'ignore_errors' => []];\n");

        try {
            (new ConfigManipulator())->replace($path, [
                'dont_report' => ['ExampleAnalyzer'],
                'ignore_errors' => ['ExampleAnalyzer' => [['path' => 'app/Test.php']]],
            ]);

            $config = require $path;

            $this->assertSame(['ExampleAnalyzer'], $config['dont_report']);
            $this->assertSame(
                ['ExampleAnalyzer' => [['path' => 'app/Test.php']]],
                $config['ignore_errors']
            );
        } finally {
            $files->delete($path);
        }
    }
}
