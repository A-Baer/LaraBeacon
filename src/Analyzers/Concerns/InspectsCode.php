<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Concerns;

use BaerSoftware\LaraBeacon\Inspection\Inspector;
use BaerSoftware\LaraBeacon\Inspection\QueryBuilder;

trait InspectsCode
{
    /**
     * Inspect the code, record the errors in the inspector and determine if the code passes the analysis.
     *
     * @param \BaerSoftware\LaraBeacon\Inspection\Inspector $inspector
     * @param \BaerSoftware\LaraBeacon\Inspection\QueryBuilder $builder
     * @return bool
     */
    protected function passesCodeInspection(Inspector $inspector, QueryBuilder $builder, ?callable $pathFilter = null)
    {
        $inspector->inspect($builder, $pathFilter);

        return $inspector->passed();
    }

    /**
     * Inspect the code and record error traces if the inspection fails.
     *
     * @param \BaerSoftware\LaraBeacon\Inspection\Inspector $inspector
     * @param \BaerSoftware\LaraBeacon\Inspection\QueryBuilder $builder
     */
    protected function inspectCode(Inspector $inspector, QueryBuilder $builder, ?callable $pathFilter = null)
    {
        if (! $this->passesCodeInspection($inspector, $builder, $pathFilter)) {
            collect($inspector->getLastErrors())->each(function ($trace) {
                $this->pushTrace($trace);
            });

            // Although adding traces would also mark it as failed, but there may be no traces
            // at all, yet should still be failed.
            if (empty($inspector->getLastErrors())) {
                $this->markFailed();
            }
        }
    }
}
