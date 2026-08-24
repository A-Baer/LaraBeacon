<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Reporting\API;
use BaerSoftware\LaraBeacon\Reporting\Client;
use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

class ReportingClientTest extends TestCase
{
    #[Test]
    public function it_accepts_https_endpoints_and_restricts_redirects_to_https()
    {
        $client = new Client('project', 'secret', 'https://cloud.larabeacon.test/api/');
        $property = new ReflectionProperty(Client::class, 'client');
        $guzzle = $property->getValue($client);

        $this->assertInstanceOf(GuzzleClient::class, $guzzle);
        $this->assertSame(['protocols' => ['https']], $guzzle->getConfig('allow_redirects'));
    }

    #[Test]
    #[DataProvider('insecureOrInvalidEndpoints')]
    public function it_rejects_insecure_or_invalid_endpoints(string $endpoint)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('absolute HTTPS URL');

        new Client('project', 'secret', $endpoint);
    }

    #[Test]
    public function the_service_provider_rejects_an_insecure_configured_endpoint()
    {
        config()->set('larabeacon.cloud.endpoint', 'http://cloud.larabeacon.test/api/');
        config()->set('larabeacon.cloud.username', 'project');
        config()->set('larabeacon.cloud.api_token', 'secret');

        $this->expectException(\InvalidArgumentException::class);

        $this->app->make(API::class);
    }

    public static function insecureOrInvalidEndpoints(): array
    {
        return [
            'plain HTTP' => ['http://cloud.larabeacon.test/api/'],
            'relative URL' => ['/api/'],
            'missing host' => ['https:///api/'],
            'non-HTTP scheme' => ['ftp://cloud.larabeacon.test/api/'],
        ];
    }
}
