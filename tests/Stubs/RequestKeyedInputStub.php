<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

use Illuminate\Http\Request;

class RequestKeyedInputStub
{
    public function store(Request $request): void
    {
        $this->applyOptions((array) $request->input('options', []));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function applyOptions(array $options): void
    {
    }
}
