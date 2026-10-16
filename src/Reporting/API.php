<?php

namespace BaerSoftware\LaraBeacon\Reporting;

class API
{
    /**
     * @var \BaerSoftware\LaraBeacon\Reporting\Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $report
     * @return string|null
     */
    public function sendReport(array $report)
    {
        $response = $this->client->post('report', $report);

        if (isset($response['url'])) {
            return $response['url'];
        }

        return null;
    }

    /**
     * List the most recent Cloud reports of the configured project.
     *
     * @param int $limit
     * @return array
     */
    public function reports(int $limit = 10): array
    {
        $response = $this->client->get('reports', ['limit' => max(1, min($limit, 50))]);

        return is_array($response) ? $response : [];
    }

    /**
     * Fetch one Cloud report with its open, new and resolved findings.
     *
     * @param string $number A report number or "latest".
     * @return array
     */
    public function report(string $number = 'latest'): array
    {
        if ($number !== 'latest' && ! ctype_digit($number)) {
            throw new \InvalidArgumentException('A Cloud report is identified by its number or "latest".');
        }

        $response = $this->client->get('reports/'.$number);

        return is_array($response) ? $response : [];
    }
}
