<?php

namespace CharosEMR\Infrastructure\Logging;

use CharosEMR\Application\Shared\Interfaces\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Level;

class CentralizedLogger implements LoggerInterface
{
    private Logger $monolog;

    public function __construct(string $channel = 'charos-emr')
    {
        $this->monolog = new Logger($channel);

        $this->setupHandlers();
        $this->setupProcessors();
    }

    private function setupHandlers(): void
    {
        $logPath = __DIR__ . '/../../../storage/logs';
        $this->ensureLogDirectory($logPath);

        $isProduction = $this->isProduction();

        if ($isProduction) {
            $this->setupProductionHandlers($logPath);
        } else {
            $this->setupDevelopmentHandlers($logPath);
        }
    }

    private function setupProductionHandlers(string $logPath): void
    {
        $fileHandler = new RotatingFileHandler(
            $logPath . '/app.log',
            30,
            Level::Info
        );
        $fileHandler->setFormatter(new JsonFormatter());
        $this->monolog->pushHandler($fileHandler);

        $errorHandler = new RotatingFileHandler(
            $logPath . '/error.log',
            30,
            Level::Error
        );
        $errorHandler->setFormatter(new JsonFormatter());
        $this->monolog->pushHandler($errorHandler);
    }

    private function setupDevelopmentHandlers(string $logPath): void
    {
        $fileHandler = new StreamHandler(
            $logPath . '/dev.log',
            Level::Debug
        );

        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] [%channel%] [%level_name%] %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat, true, true);
        $fileHandler->setFormatter($formatter);
        $this->monolog->pushHandler($fileHandler);

        $stdoutHandler = new StreamHandler('php://stdout', Level::Debug);
        $stdoutHandler->setFormatter($formatter);
        $this->monolog->pushHandler($stdoutHandler);
    }

    private function setupProcessors(): void
    {
        if (php_sapi_name() !== 'cli') {
            $this->monolog->pushProcessor(new WebProcessor());
        }

        $this->monolog->pushProcessor(new IntrospectionProcessor(Level::Error));
        $this->monolog->pushProcessor(new MemoryUsageProcessor());

        $this->monolog->pushProcessor(function ($record) {
            $record['extra']['environment'] = $this->getEnvironment();
            $record['extra']['request_id'] = $this->getRequestId();
            return $record;
        });
    }

    private function ensureLogDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function isProduction(): bool
    {
        $env = getenv('APP_ENV') ?: 'development';
        return $env === 'production';
    }

    private function getEnvironment(): string
    {
        return getenv('APP_ENV') ?: 'development';
    }

    private function getRequestId(): string
    {
        if (php_sapi_name() === 'cli') {
            return 'cli-' . getmypid();
        }

        if (!isset($_SERVER['HTTP_X_REQUEST_ID'])) {
            $_SERVER['HTTP_X_REQUEST_ID'] = uniqid('req-', true);
        }

        return $_SERVER['HTTP_X_REQUEST_ID'];
    }

    public function info(string $message, array $context = []): void
    {
        $this->monolog->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->monolog->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->monolog->warning($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->monolog->debug($message, $context);
    }
}
