<?php

namespace BaerSoftware\LaraBeacon\Tests;

use LaraBeaconMcp\Artisan;
use LaraBeaconMcp\Catalog;
use LaraBeaconMcp\Project;
use LaraBeaconMcp\Server;
use LaraBeaconMcp\Tools;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as BaseTestCase;

class ClaudePluginTest extends BaseTestCase
{
    private string $plugin;

    private string $project;

    protected function setUp(): void
    {
        $this->plugin = dirname(__DIR__).'/claude-plugin';

        foreach (['Server', 'Project', 'Artisan', 'Catalog', 'Tools'] as $file) {
            require_once $this->plugin.'/server/src/'.$file.'.php';
        }

        $this->project = sys_get_temp_dir().'/larabeacon-plugin-'.bin2hex(random_bytes(6));
        mkdir($this->project.'/vendor/composer', 0777, true);
        mkdir($this->project.'/app/Http', 0777, true);
        file_put_contents($this->project.'/artisan', "<?php\n");
        file_put_contents($this->project.'/composer.json', json_encode(['require' => ['laravel/framework' => '^13.0', 'laravel/horizon' => '^5.0']]));
        file_put_contents($this->project.'/vendor/autoload.php', "<?php\n");
        file_put_contents($this->project.'/vendor/composer/installed.json', json_encode(['packages' => [
            ['name' => 'laravel/framework', 'version' => 'v13.1.0'],
            ['name' => 'laravel/horizon', 'version' => 'v5.30.0'],
        ]]));
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->project));
    }

    #[Test]
    public function the_manifests_and_skills_are_well_formed()
    {
        $plugin = json_decode(file_get_contents($this->plugin.'/.claude-plugin/plugin.json'), true, flags: JSON_THROW_ON_ERROR);
        $marketplace = json_decode(file_get_contents(dirname(__DIR__).'/.claude-plugin/marketplace.json'), true, flags: JSON_THROW_ON_ERROR);
        $mcp = json_decode(file_get_contents($this->plugin.'/.mcp.json'), true, flags: JSON_THROW_ON_ERROR);
        json_decode(file_get_contents($this->plugin.'/hooks/hooks.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('larabeacon', $plugin['name']);
        $this->assertSame(Server::VERSION, $plugin['version']);
        $this->assertSame([['name' => 'larabeacon', 'source' => './claude-plugin']], array_map(fn ($entry) => array_intersect_key($entry, ['name' => 1, 'source' => 1]), $marketplace['plugins']));
        $this->assertSame('${CLAUDE_PLUGIN_ROOT}/server/larabeacon-mcp.php', $mcp['mcpServers']['larabeacon']['args'][0]);

        foreach (['scan', 'setup', 'cloud'] as $skill) {
            $this->assertMatchesRegularExpression("/\\A---\nname: {$skill}\ndescription: .+\n/", file_get_contents($this->plugin."/skills/{$skill}/SKILL.md"));
        }
    }

    #[Test]
    public function the_catalog_lists_core_and_pro_checks_with_existing_core_classes()
    {
        $catalog = Catalog::load($this->plugin.'/data/checks.json');

        $this->assertNotEmpty($catalog->tier('free'));
        $this->assertNotEmpty($catalog->tier('pro'));

        foreach ($catalog->tier('free') as $check) {
            $this->assertTrue(class_exists($check['analyzer']), $check['analyzer']);
        }

        $this->assertSame('BaerSoftware\\LaraBeacon\\Analyzers\\Security\\AppDebugAnalyzer', $catalog->analyzerClass('app-debug'));
        $this->assertSame('BaerSoftware\\LaraBeacon\\Analyzers\\Security\\AppDebugAnalyzer', $catalog->analyzerClass('AppDebugAnalyzer'));
        $this->assertNull($catalog->analyzerClass('does-not-exist'));
    }

    #[Test]
    public function the_server_speaks_json_rpc_and_lists_tools()
    {
        $server = $this->server();

        $init = $server->handleLine('{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26"}}');
        $this->assertSame('2025-03-26', $init['result']['protocolVersion']);
        $this->assertSame('larabeacon', $init['result']['serverInfo']['name']);
        $this->assertNull($server->handleLine('{"jsonrpc":"2.0","method":"notifications/initialized"}'));
        $this->assertSame(-32700, $server->handleLine('{oops')['error']['code']);
        $this->assertSame(-32601, $server->handleLine('{"jsonrpc":"2.0","id":2,"method":"sampling/createMessage"}')['error']['code']);
        $this->assertSame(-32602, $server->handleLine('{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"rm"}}')['error']['code']);

        $tools = array_column($server->handleLine('{"jsonrpc":"2.0","id":4,"method":"tools/list"}')['result']['tools'], null, 'name');

        $this->assertTrue($tools['larabeacon_scan']['annotations']['readOnlyHint']);
        $this->assertFalse($tools['larabeacon_cloud_upload']['annotations']['readOnlyHint']);
        $this->assertTrue($tools['larabeacon_cloud_upload']['annotations']['openWorldHint']);
        $this->assertSame(['reason'], $tools['larabeacon_baseline']['inputSchema']['required']);
    }

    #[Test]
    public function status_guides_installation_when_larabeacon_is_missing()
    {
        $status = $this->call('larabeacon_status');

        $this->assertTrue($status['laravel_project']);
        $this->assertSame('v13.1.0', $status['laravel_version']);
        $this->assertNull($status['larabeacon']['version']);
        $this->assertStringContainsString('composer require --dev baer-software/larabeacon', $status['next_steps'][0]);

        $scan = $this->tools()->call('larabeacon_scan', []);
        $this->assertTrue($scan['isError']);
        $this->assertStringContainsString('not installed', $scan['content'][0]['text']);
    }

    #[Test]
    public function pro_information_names_checks_relevant_to_the_project_with_tracked_links()
    {
        $this->installCore('v3.2.0');
        $tools = $this->tools();
        $checks = $tools->call('larabeacon_checks', ['tier' => 'pro', 'query' => 'horizon'])['structuredContent'];

        $this->assertGreaterThan(0, $checks['total']);
        $this->assertFalse($checks['checks'][0]['available_in_this_project']);
        $this->assertStringContainsString('utm_source=claude-code', $checks['for_the_user']['pro']['learn_more']);
        $this->assertContains('uses laravel/horizon', array_column($checks['for_the_user']['pro']['relevant_to_this_project'], 'reason'));

        // Shown once per session: later results of the same server process omit it.
        $this->assertArrayNotHasKey('for_the_user', $tools->call('larabeacon_checks', ['tier' => 'pro'])['structuredContent']);
        $this->assertArrayNotHasKey('for_the_user', $tools->call('larabeacon_status', [])['structuredContent']);
    }

    #[Test]
    public function status_still_reports_next_steps_when_the_application_cannot_boot()
    {
        $this->installCore('v3.3.0');

        $status = $this->call('larabeacon_status');

        $this->assertNull($status['cloud']);
        $this->assertStringContainsString('larabeacon:cloud status failed', $status['next_steps'][0]);
        $this->assertArrayHasKey('pro', $status['for_the_user']);
        $this->assertArrayNotHasKey('cloud', $status['for_the_user']);
    }

    #[Test]
    public function wrongly_typed_arguments_keep_stdout_pure_json_rpc()
    {
        $lines = '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"larabeacon_checks","arguments":{"query":["a"]}}}'."\n"
            .'{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"larabeacon_scan","arguments":{"analyzers":[["x"]]}}}'."\n";
        $process = proc_open([PHP_BINARY, $this->plugin.'/server/larabeacon-mcp.php', '--project='.$this->project], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        fwrite($pipes[0], $lines);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        proc_close($process);

        $responses = array_map(fn ($line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), array_filter(explode("\n", $stdout)));

        $this->assertCount(2, $responses);
        $this->assertTrue($responses[0]['result']['isError']);
        $this->assertTrue($responses[1]['result']['isError']);
    }

    #[Test]
    public function inputs_are_validated_before_any_artisan_process_starts()
    {
        $this->installCore('v3.3.0');
        $tools = $this->tools();

        $cases = [
            ['larabeacon_scan', ['profile' => 'staging'], 'profile must be'],
            ['larabeacon_scan', ['baseline' => '../../etc/passwd'], 'without ".."'],
            ['larabeacon_scan', ['baseline' => '/etc/passwd'], 'without ".."'],
            ['larabeacon_scan', ['only_new' => true], 'requires a baseline'],
            ['larabeacon_scan', ['analyzers' => ['--report']], 'Unknown check'],
            ['larabeacon_scan', ['analyzers' => ['sql-injection']], 'Pro check'],
            ['larabeacon_baseline', ['reason' => 'Accepted', 'output' => 'baseline.txt'], 'The baseline must be a .json file.'],
            ['larabeacon_baseline', ['reason' => ' '], 'needs a reason'],
            ['larabeacon_cloud_report', ['report' => '1; rm'], 'number or "latest"'],
        ];

        foreach ($cases as [$tool, $arguments, $message]) {
            $result = $tools->call($tool, $arguments);
            $this->assertTrue($result['isError'] ?? false, $tool.' '.json_encode($arguments));
            $this->assertStringContainsString($message, $result['content'][0]['text']);
        }
    }

    #[Test]
    public function cloud_tools_require_a_compatible_core_version()
    {
        $this->installCore('v3.2.0');

        $result = $this->tools()->call('larabeacon_cloud_reports', []);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('3.3.0 or newer', $result['content'][0]['text']);
    }

    #[Test]
    public function the_project_root_skips_unexpanded_placeholders_and_walks_up_from_subdirectories()
    {
        $this->assertSame(realpath($this->project), Project::resolve('${CLAUDE_PROJECT_DIR}', null, $this->project.'/app/Http')->root);
        $this->assertSame(realpath($this->project), Project::resolve('', $this->project, '/')->root);
    }

    #[Test]
    public function the_session_hook_is_silent_outside_laravel_projects()
    {
        $hook = escapeshellarg($this->plugin.'/hooks/session-start.php');

        $this->assertSame('', (string) shell_exec('cd '.escapeshellarg(sys_get_temp_dir()).' && CLAUDE_PROJECT_DIR= php '.$hook));
        $this->assertStringContainsString('/larabeacon:setup', shell_exec('CLAUDE_PROJECT_DIR='.escapeshellarg($this->project).' php '.$hook));
    }

    private function installCore(string $version): void
    {
        $installed = json_decode(file_get_contents($this->project.'/vendor/composer/installed.json'), true);
        $installed['packages'][] = ['name' => 'baer-software/larabeacon', 'version' => $version];
        file_put_contents($this->project.'/vendor/composer/installed.json', json_encode($installed));
    }

    private function tools(): Tools
    {
        $project = new Project(realpath($this->project));

        return new Tools($project, new Artisan($project, PHP_BINARY, 30), Catalog::load($this->plugin.'/data/checks.json'));
    }

    private function server(): Server
    {
        return new Server($this->tools());
    }

    private function call(string $tool, array $arguments = []): array
    {
        $result = $this->tools()->call($tool, $arguments);
        $this->assertArrayNotHasKey('isError', $result, $result['content'][0]['text'] ?? '');

        return $result['structuredContent'];
    }
}
