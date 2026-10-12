<?php

namespace BaerSoftware\LaraBeacon\Analyzers;

use Illuminate\Support\Str;
use JsonSerializable;
use RuntimeException;

class Trace implements JsonSerializable
{
    /**
     * @var int
     */
    public $lineNumber;

    /**
     * @var string|null
     */
    public $details;

    /**
     * @var string|null
     */
    public $path;

    /**
     * @var array
     */
    public $codeSnippet = [];

    /**
     * @var int
     */
    private $snippetLineCount = 30;

    public function __construct($path, $lineNumber, $details = null)
    {
        $this->path = $path;
        $this->lineNumber = $lineNumber;
        $this->details = $details;
    }

    /** A hash of the source line distinguishes otherwise identical locations without exporting source. */
    public function evidenceKey(): ?string
    {
        if ($this->details !== null || $this->lineNumber < 1 || ! is_file($this->absolutePath())) {
            return null;
        }
        $file = new \SplFileObject($this->absolutePath());
        $file->seek($this->lineNumber - 1);
        $line = $file->current();
        if (! is_string($line) || trim($line) === '') {
            return null;
        }
        $tokens = token_get_all('<?php '.$line);
        $normalized = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (! in_array($token[0], [T_WHITESPACE, T_OPEN_TAG, T_COMMENT, T_DOC_COMMENT], true)) {
                    $normalized .= $token[1];
                }
            } else {
                $normalized .= $token;
            }
        }

        return hash('sha256', $normalized);
    }

    /**
     * @return array
     */
    public function codeSnippet()
    {
        if (! file_exists($path = $this->absolutePath())) {
            return [];
        }

        if (! empty($this->codeSnippet)) {
            // Return cached value if already computed.
            return $this->codeSnippet;
        }

        try {
            $file = new File($path);

            [$startLineNumber, $endLineNumber] = $this->getBounds($file->numberOfLines());

            $codeSnippet = [];

            $line = $file->getLine($startLineNumber);

            $currentLineNumber = $startLineNumber;

            while ($currentLineNumber <= $endLineNumber) {
                $codeSnippet[$currentLineNumber] = rtrim(substr($line, 0, 250));

                $line = $file->getNextLine();
                $currentLineNumber++;
            }

            $this->codeSnippet = $codeSnippet;

            return $this->codeSnippet;
        } catch (RuntimeException $exception) {
            return [];
        }
    }

    /**
     * @return string|null
     */
    public function relativePath()
    {
        return trim(Str::contains($this->path, base_path()) ? Str::after($this->path, base_path()) : $this->path, '/');
    }

    /**
     * @return string|null
     */
    public function absolutePath()
    {
        if (! file_exists($this->path)) {
            return base_path(trim($this->path, '/'));
        }

        return $this->path;
    }

    /**
     * @param int $snippetLineCount
     * @return $this
     */
    public function setSnippetLineCount(int $snippetLineCount)
    {
        $this->snippetLineCount = $snippetLineCount;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return [
            'path' => $this->relativePath(),
            'lineNumber' => $this->lineNumber,
            'details' => $this->details,
            'codeSnippet' => $this->codeSnippet(),
        ];
    }

    /**
     * @param int $totalLines
     * @return array
     */
    private function getBounds(int $totalLines)
    {
        $startLine = max($this->lineNumber - floor($this->snippetLineCount / 2), 1);

        $endLine = $startLine + ($this->snippetLineCount - 1);

        if ($endLine > $totalLines) {
            $endLine = $totalLines;
            $startLine = max($endLine - ($this->snippetLineCount - 1), 1);
        }

        return [$startLine, $endLine];
    }
}
