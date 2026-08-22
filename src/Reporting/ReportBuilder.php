<?php

namespace BaerSoftware\LaraBeacon\Reporting;

interface ReportBuilder
{
    /**
     * @param array $analyzerResults
     * @param array $analyzerStats
     * @param array $additionalData
     * @return string
     */
    public function buildReport(array $analyzerResults, array $analyzerStats, array $additionalData = []);
}
