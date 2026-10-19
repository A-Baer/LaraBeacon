<?php

declare(strict_types=1);

namespace LaraBeaconMcp;

/**
 * Runs `php artisan` in the project as a separate process without a shell.
 */
final class Artisan
{
    public function __construct(
        private readonly Project $project,
        private readonly string $php,
        private readonly int $timeout,
    ) {}

    /**
     * @param list<string> $arguments
     * @return array{exit: int, stdout: string, stderr: string, timed_out: bool}
     */
    public function run(array $arguments): array
    {
        return $this->process([$this->php, 'artisan', ...$arguments, '--no-interaction', '--no-ansi']);
    }

    /**
     * Run any command in the project root as a separate process without a shell.
     *
     * @param list<string> $command
     * @return array{exit: int, stdout: string, stderr: string, timed_out: bool}
     */
    public function process(array $command): array
    {
        $stdout = tmpfile();
        $stderr = tmpfile();

        if ($stdout === false || $stderr === false) {
            throw new ToolError('Could not create temporary files for the Artisan process.');
        }

        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => $stdout, 2 => $stderr], $pipes, $this->project->root);

        if (! is_resource($process)) {
            throw new ToolError('Could not start '.basename($command[0]).' in '.$this->project->root.'.');
        }

        fclose($pipes[0]);
        $deadline = microtime(true) + $this->timeout;
        $timedOut = false;

        while (($status = proc_get_status($process))['running']) {
            if (microtime(true) > $deadline) {
                proc_terminate($process, 9);
                $timedOut = true;
                break;
            }

            usleep(100_000);
        }

        $exit = proc_close($process);
        // proc_close() returns -1 once proc_get_status() has observed the exit.
        $exit = $timedOut ? 124 : ($status['running'] ? $exit : (int) $status['exitcode']);

        return [
            'exit' => $exit,
            'stdout' => $this->read($stdout),
            'stderr' => $this->read($stderr),
            'timed_out' => $timedOut,
        ];
    }

    /**
     * Run an Artisan command whose only stdout is a JSON document.
     *
     * @param list<string> $arguments
     */
    public function json(array $arguments): array
    {
        $result = $this->run($arguments);
        $data = json_decode(trim($result['stdout']), true);

        if (! is_array($data)) {
            throw new ToolError($this->failureMessage($result));
        }

        return $data;
    }

    public function failureMessage(array $result): string
    {
        if ($result['timed_out']) {
            return 'The command did not finish within '.$this->timeout.' seconds. Set LARABEACON_MCP_TIMEOUT to allow longer runs.';
        }

        $output = trim($result['stderr']."\n".$result['stdout']);

        return 'The command exited with code '.$result['exit'].($output === '' ? '.' : ': '.mb_substr($output, 0, 2000));
    }

    /** @param resource $handle */
    private function read($handle): string
    {
        rewind($handle);
        $contents = stream_get_contents($handle, 2_000_000);
        fclose($handle);

        return is_string($contents) ? $contents : '';
    }
}
