<?php

namespace App\Services\Lottery\Solvers\Glpk\lib;

use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use Throwable;

/**
 * Handles file operations for GLPK solver.
 *
 * Manages creation, writing, and cleanup of temporary files
 * used during GLPK optimization process.
 */
class Files
{
    /** @var array<string, string> Maps filenames to their contents for audit trail */
    protected array $artifacts = [];

    public function __construct(
        protected string $tempDir,
    ) {
        // ...
    }

    /**
     * Write content to temporary file.
     *
     * Creates a temporary file with the specified prefix and suffix (extension),
     * writes the provided content, and returns the file path.
     *
     * @param  string  $prefix  Filename prefix (e.g., 'phase1_', 'data_')
     * @param  string  $suffix  Filename suffix/extension (e.g., '.mod', '.dat')
     * @param  string  $content  Content to write to file
     * @return string Path to created file
     *
     * @throws GlpkException if file cannot be created or written
     */
    public function write(string $prefix, string $suffix, string $content): string
    {
        try {
            // Create temp file with prefix
            $tempFile = tempnam($this->tempDir, $prefix);

            if ($tempFile === false) {
                throw new GlpkException("Failed to create temporary file in {$this->tempDir}");
            }

            // Delete the tempnam file and create new one with suffix
            unlink($tempFile);
            $file = $tempFile . $suffix;

            $result = file_put_contents($file, $content);

            if ($result === false) {
                throw new GlpkException("Failed to write content to file {$file}");
            }

            // Store content for audit trail
            $this->artifacts[basename($file)] = $content;

            return $file;
        } catch (GlpkException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GlpkException("Error creating temporary file: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Reserve a unique path for GLPK solution (.sol) output.
     *
     * @return string Path to store the solution file in
     *
     * @throws GlpkException if temporary path cannot be reserved
     */
    public function reserveSolutionPath(): string
    {
        $tempFile = tempnam($this->tempDir, 'mtav_sol_');

        if ($tempFile === false) {
            throw new GlpkException("Failed to create temporary solution file in {$this->tempDir}");
        }

        unlink($tempFile);

        return $tempFile . '.sol';
    }

    /**
     * Ensure file exists, has content, and is ready for reading.
     *
     * Validates the file and ensures all buffers are flushed to prevent
     * race conditions where the process has exited but file I/O is incomplete.
     *
     * @param  string  $filepath  Path to file to validate
     *
     * @throws GlpkException if file doesn't exist or is empty
     */
    public function ensureReadable(string $filepath): void
    {
        if (! file_exists($filepath) || filesize($filepath) === 0) {
            throw new GlpkException('GLPK did not generate a solution file.');
        }

        // Ensure all kernel buffers are flushed to disk
        $handle = fopen($filepath, 'r');

        if ($handle !== false) {
            fsync($handle);
            fclose($handle);
        }

        clearstatcache(true, $filepath);

        // Store solution file content for audit trail
        if (file_exists($filepath)) {
            $this->artifacts[basename($filepath)] = file_get_contents($filepath);
        }
    }

    /**
     * Get all artifacts (file contents) captured during execution.
     *
     * @return array<string, string> Map of filename => content
     */
    public function getArtifacts(): array
    {
        return $this->artifacts;
    }

    /**
     * Clear stored artifacts (useful for cleanup between executions).
     */
    public function clearArtifacts(): void
    {
        $this->artifacts = [];
    }

    /**
     * Deletes the specified temporary files. Failures are silently ignored
     * since cleanup is best-effort and shouldn't fail the main operation.
     *
     * @param  array<string|null>  $files  Array of file paths to delete
     */
    public function cleanup(array $files): void
    {
        foreach (array_filter($files) as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
