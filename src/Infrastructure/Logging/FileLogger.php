<?php

namespace CharosEMR\Infrastructure\Logging;

use CharosEMR\Application\Shared\Interfaces\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = __DIR__ . '/../../../storage/logs';
        $this->ensureLogDirectory();
        $this->rotateLogs();
    }

    /**
     * Rotate log files older than 30 days
     */
    private function rotateLogs(): void
    {
        $files = glob($this->logPath . '/*.log');
        $cutoff = time() - (30 * 24 * 60 * 60); // 30 days

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (!unlink($file)) {
                    error_log("Failed to delete old log file: " . $file);
                }
            }
        }
    }

    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Write log entry to daily log file
     * @param string $level Log level (INFO, ERROR, WARNING, DEBUG)
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function writeLog(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d');
        $file = $this->logPath . '/' . $date . '.log';

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        $result = file_put_contents($file, $logLine, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            error_log("Failed to write to log file: " . $file);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->writeLog('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->writeLog('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->writeLog('WARNING', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->writeLog('DEBUG', $message, $context);
    }
}
