<?php

declare(strict_types=1);

namespace LaraBeaconMcp;

/**
 * Public LaraBeacon check catalogue shipped with the plugin (metadata only, no Pro source code).
 */
final class Catalog
{
    /** @param list<array<string, mixed>> $checks */
    public function __construct(private readonly array $checks) {}

    public static function load(string $file): self
    {
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return new self(is_array($data['checks'] ?? null) ? array_values($data['checks']) : []);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->checks;
    }

    /** @return list<array<string, mixed>> */
    public function tier(string $tier): array
    {
        return array_values(array_filter($this->checks, fn (array $check): bool => $check['tier'] === $tier));
    }

    /**
     * Map a slug, short class name or fully qualified class name to the analyzer class.
     */
    public function analyzerClass(string $name): ?string
    {
        $needle = strtolower(ltrim($name, '\\'));

        foreach ($this->checks as $check) {
            $class = (string) $check['analyzer'];
            $short = substr($class, strrpos($class, '\\') + 1);

            if (in_array($needle, [strtolower($class), strtolower($short), strtolower((string) $check['slug'])], true)) {
                return $class;
            }
        }

        return null;
    }

    public function byAnalyzer(string $class): ?array
    {
        foreach ($this->checks as $check) {
            if (strcasecmp((string) $check['analyzer'], ltrim($class, '\\')) === 0) {
                return $check;
            }
        }

        return null;
    }

    /**
     * Pro checks whose subject is present in this project, with the reason.
     *
     * @return list<array{name: string, category: string, reason: string, url: string}>
     */
    public function relevantProChecks(Project $project): array
    {
        $relevant = [];

        foreach ($this->tier('pro') as $check) {
            $signal = $check['relevant_when'] ?? null;

            if (! is_array($signal)) {
                continue;
            }

            foreach ($signal['packages'] ?? [] as $package) {
                if ($project->installed($package) || $project->requires($package)) {
                    $relevant[] = $this->relevance($check, 'uses '.$package);
                    continue 2;
                }
            }

            foreach ($signal['files'] ?? [] as $file) {
                if ($project->hasFile($file)) {
                    $relevant[] = $this->relevance($check, 'has '.$file);
                    continue 2;
                }
            }
        }

        return $relevant;
    }

    private function relevance(array $check, string $reason): array
    {
        return [
            'name' => (string) $check['name'],
            'category' => (string) $check['category'],
            'reason' => $reason,
            'url' => (string) $check['url'],
        ];
    }
}
