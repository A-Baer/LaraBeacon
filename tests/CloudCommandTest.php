<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Analyzers\Security\AppDebugAnalyzer;
use BaerSoftware\LaraBeacon\Reporting\API;
use BaerSoftware\LaraBeacon\Reporting\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

class CloudCommandTest extends TestCase
{
    #[Test]
    public function status_reports_configuration_without_exposing_credentials()
    {
        config()->set('larabeacon.cloud.endpoint', 'https://larabeacon.test/api/');
        config()->set('larabeacon.cloud.username', 'project-user');
        config()->set('larabeacon.cloud.api_token', 'very-secret-token');

        $this->assertSame(0, Artisan::call('larabeacon:cloud', ['action' => 'status']));
        $output = Artisan::output();
        $status = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        $this->assertTrue($status['cloud']['configured']);
        $this->assertSame('larabeacon.test', $status['cloud']['endpoint_host']);
        $this->assertFalse($status['pro_installed']);
        $this->assertStringNotContainsString('very-secret-token', $output);
        $this->assertStringNotContainsString('project-user', $output);
    }

    #[Test]
    public function status_reports_missing_configuration()
    {
        config()->set('larabeacon.cloud.endpoint', null);

        $this->assertSame(0, Artisan::call('larabeacon:cloud'));
        $status = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertFalse($status['cloud']['configured']);
        $this->assertNull($status['cloud']['endpoint_host']);
    }

    #[Test]
    public function reports_fail_with_a_json_error_when_cloud_is_not_configured()
    {
        config()->set('larabeacon.cloud.endpoint', null);

        $this->assertSame(1, Artisan::call('larabeacon:cloud', ['action' => 'reports']));
        $this->assertStringContainsString('LARABEACON_CLOUD_ENDPOINT', json_decode(Artisan::output(), true)['error']);
    }

    #[Test]
    public function it_lists_and_reads_reports_with_get_query_parameters()
    {
        $history = [];
        $api = $this->fakeApi([
            new Response(200, [], json_encode(['reports' => [['number' => 7]]])),
            new Response(200, [], json_encode(['number' => 7, 'open_findings' => []])),
        ], $history);
        $this->app->instance(API::class, $api);

        $this->assertSame(0, Artisan::call('larabeacon:cloud', ['action' => 'reports', '--limit' => 500]));
        $this->assertSame([['number' => 7]], json_decode(Artisan::output(), true)['reports']);
        $this->assertSame(0, Artisan::call('larabeacon:cloud', ['action' => 'report', 'report' => '7']));
        $this->assertSame(7, json_decode(Artisan::output(), true)['number']);

        $this->assertSame('GET', $history[0]['request']->getMethod());
        $this->assertSame('/api/reports', $history[0]['request']->getUri()->getPath());
        $this->assertSame('limit=50', $history[0]['request']->getUri()->getQuery());
        $this->assertSame('', (string) $history[0]['request']->getBody());
        $this->assertSame('/api/reports/7', $history[1]['request']->getUri()->getPath());
    }

    #[Test]
    public function it_rejects_invalid_report_identifiers_before_any_request()
    {
        $history = [];
        $this->app->instance(API::class, $this->fakeApi([], $history));

        $this->assertSame(2, Artisan::call('larabeacon:cloud', ['action' => 'report', 'report' => '../teams']));
        $this->assertSame([], $history);
    }

    #[Test]
    public function rejected_credentials_produce_a_generic_error()
    {
        $history = [];
        $this->app->instance(API::class, $this->fakeApi([new Response(401, [], 'token abc is invalid')], $history));

        $this->assertSame(1, Artisan::call('larabeacon:cloud', ['action' => 'reports']));
        $output = Artisan::output();
        $this->assertStringContainsString('rejected the project credentials', $output);
        $this->assertStringNotContainsString('token abc', $output);
    }

    #[Test]
    public function a_file_report_upload_prints_the_cloud_link_on_stdout()
    {
        $history = [];
        $this->app->instance(API::class, $this->fakeApi([
            new Response(200, [], json_encode(['url' => 'https://larabeacon.test/app/team/projects/1/reports/2'])),
        ], $history));
        config()->set('larabeacon.analyzers', AppDebugAnalyzer::class);
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        $path = tempnam(sys_get_temp_dir(), 'larabeacon');

        try {
            $this->assertSame(0, Artisan::call('larabeacon', ['--format' => 'json', '--output' => $path, '--report' => true]));
            $this->assertSame("LaraBeacon Cloud report: https://larabeacon.test/app/team/projects/1/reports/2\n", Artisan::output());
            $this->assertArrayHasKey('findings', json_decode(file_get_contents($path), true));
            $this->assertSame('POST', $history[0]['request']->getMethod());
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function unknown_actions_are_rejected()
    {
        $this->assertSame(2, Artisan::call('larabeacon:cloud', ['action' => 'delete']));
    }

    private function fakeApi(array $responses, array &$history): API
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        $client = new Client('project', 'secret', 'https://larabeacon.test/api/');
        (new ReflectionProperty(Client::class, 'client'))->setValue($client, new GuzzleClient([
            'base_uri' => 'https://larabeacon.test/api/',
            'handler' => $stack,
            'http_errors' => false,
        ]));

        return new API($client);
    }
}
