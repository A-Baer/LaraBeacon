<?php

namespace BaerSoftware\LaraBeacon\Findings;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

final class FixPreview
{
    public static function build(array $report): array
    {
        $previews = [];
        $configPath = rtrim(config('larabeacon.config_path', config_path()), DIRECTORY_SEPARATOR);
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $mappings = [];
        foreach (glob($configPath.'/*.php') ?: [] as $path) {
            try {
                $nodes = $parser->parse(file_get_contents($path));
            } catch (\PhpParser\Error $error) {
                continue;
            }
            foreach ($nodes ?? [] as $node) {
                if (! $node instanceof Node\Stmt\Return_ || ! $node->expr instanceof Node\Expr\Array_) {
                    continue;
                }
                foreach ($node->expr->items as $item) {
                    if ($item?->key instanceof Node\Scalar\String_ && self::environmentCall($item->value)) {
                        $env = $item->value->args[0]->value->value;
                        $mappings[$env][] = basename($path, '.php').'.'.$item->key->value;
                    }
                }
            }
        }
        $files = [];
        foreach ($report['findings'] as $finding) {
            if ($finding['status'] !== 'failed') {
                continue;
            }
            $name = class_basename($finding['analyzer']);
            $path = $name === 'AppDebugAnalyzer' ? $configPath.'/app.php' : base_path($finding['location']['path'] ?? '');
            $real = realpath($path);
            // Never inspect or propose patches outside this application checkout.
            if ($real === false || ! str_starts_with($real, rtrim(realpath(base_path()), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) || ! is_file($real)) {
                continue;
            }
            if (! in_array($name, ['AppDebugAnalyzer', 'EnvCallAnalyzer'], true)) {
                continue;
            }
            $source = file_get_contents($real);

            try {
                $nodes = $parser->parse($source);
            } catch (\PhpParser\Error $error) {
                continue;
            }
            $changes = [];
            if ($name === 'AppDebugAnalyzer') {
                foreach ($nodes ?? [] as $node) {
                    if (! $node instanceof Node\Stmt\Return_ || ! $node->expr instanceof Node\Expr\Array_) {
                        continue;
                    }
                    foreach ($node->expr->items as $item) {
                        if ($item?->key instanceof Node\Scalar\String_ && $item->key->value === 'debug'
                            && $item->value instanceof Node\Expr\ConstFetch && strtolower($item->value->name->toString()) === 'true') {
                            $changes[] = [$item->value, 'false'];
                        }
                    }
                }
            } else {
                $calls = (new NodeFinder)->find($nodes ?? [], fn (Node $node) => self::environmentCall($node));
                foreach ($calls as $call) {
                    $env = $call->args[0]->value->value;
                    if (count($call->args) === 1 && count($mappings[$env] ?? []) === 1
                        && $call->getStartLine() === ($finding['location']['lineNumber'] ?? null)) {
                        $changes[] = [$call, 'config('.var_export($mappings[$env][0], true).')'];
                    }
                }
            }
            foreach ($changes as [$node, $replacement]) {
                $files[$real]['source'] = $source;
                $files[$real]['changes'][$node->getStartFilePos()] = [$node, $replacement];
            }
        }
        foreach ($files as $path => $file) {
            $source = $file['source'];
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen(rtrim(realpath(base_path()), DIRECTORY_SEPARATOR)) + 1));
            $lines = explode("\n", $source);
            $newLines = $lines;
            krsort($file['changes']);
            $touched = [];
            foreach ($file['changes'] as [$node, $replacement]) {
                // Preview only single-line expressions, keeping minimal reviewable hunks.
                if ($node->getStartLine() !== $node->getEndLine()) {
                    continue;
                }
                $line = $node->getStartLine();
                $previousNewline = strrpos(substr($source, 0, $node->getStartFilePos()), "\n");
                $offset = $node->getStartFilePos() - ($previousNewline === false ? 0 : $previousNewline + 1);
                $newLines[$line - 1] = substr_replace($newLines[$line - 1], $replacement, $offset, $node->getEndFilePos() - $node->getStartFilePos() + 1);
                $touched[$line] = true;
            }
            ksort($touched);
            if ($touched === []) {
                continue;
            }
            $diff = "--- a/{$relative}\n+++ b/{$relative}\n";
            foreach (array_keys($touched) as $line) {
                $diff .= "@@ -{$line},1 +{$line},1 @@\n-{$lines[$line - 1]}\n+{$newLines[$line - 1]}\n";
            }
            $previews[] = ['path' => $relative, 'source_sha256' => hash('sha256', $source), 'diff' => $diff];
        }

        return $previews;
    }

    private static function environmentCall(Node $node): bool
    {
        return $node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name
            && strtolower($node->name->toString()) === 'env'
            && isset($node->args[0]) && $node->args[0] instanceof Node\Arg && $node->args[0]->name === null && ! $node->args[0]->unpack
            && $node->args[0]->value instanceof Node\Scalar\String_;
    }
}
