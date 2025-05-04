<?php /** @noinspection ForgottenDebugOutputInspection */

namespace Ventura;

use Exception;
use JsonException;

use RuntimeException;
use Throwable;
use Ventura\Storage\RedBeanStorageProvider;

use Ventura\Storage\StorageProvider;

if (!defined('VENTURA_DATA_DIR')) {
    define('VENTURA_DATA_DIR', __DIR__ . '/data');
}

function _get_error_types(?int $int = null): array|string
{
    $exceptions = [
        E_ERROR => "E_ERROR",
        E_WARNING => "E_WARNING",
        E_PARSE => "E_PARSE",
        E_NOTICE => "E_NOTICE",
        E_CORE_ERROR => "E_CORE_ERROR",
        E_CORE_WARNING => "E_CORE_WARNING",
        E_COMPILE_ERROR => "E_COMPILE_ERROR",
        E_COMPILE_WARNING => "E_COMPILE_WARNING",
        E_USER_ERROR => "E_USER_ERROR",
        E_USER_WARNING => "E_USER_WARNING",
        E_USER_NOTICE => "E_USER_NOTICE",
        E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
        E_DEPRECATED => "E_DEPRECATED",
        E_USER_DEPRECATED => "E_USER_DEPRECATED",
        E_ALL => "E_ALL",
    ];

    if ($int !== null) {
        return $exceptions[$int] ?? 'UNKNOWN_ERROR';
    }

    return $exceptions;
}

function _error_handler(int $code, string $message, string $file, int $line): void
{
    // Get backtrace
    try {
        $e = new Exception();
        $trace = $e->getTrace();
    } catch (Exception) {
        // Silent
    }

    $data = [
        'code' => $code,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'type' => _get_error_types($code),
        'trace' => $trace ?? null,
    ];

    $context = _get_context();

    if (ini_get('display_errors')) {
        $errorMessage = "Error [$code]: $message in $file on line $line";
        echo $errorMessage . PHP_EOL;
    }
    _save_error($data, $context);
}

/**
 * Saves error details and context using the configured StorageProvider.
 * Calculates a hash for grouping identical errors.
 *
 * @param array $data Error details (must contain 'code', 'message', 'file', 'line', 'type', 'trace').
 * @param array $context Contextual information associated with the error.
 *
 * @return bool True if the error and context were stored successfully, false otherwise.
 */
function _save_error(array $data, array $context): bool
{
    global $ventura_storage_provider;

    if (!isset($ventura_storage_provider) || !$ventura_storage_provider instanceof StorageProvider) {
        error_log('Ventura Storage Provider not initialized.');

        return false;
    }

    // Create a hash based on core identifying error data to group similar errors.
    // This logic remains here as it's about *identifying* the error, not storing it.
    $identifyingData = [
        'code' => $data['code'],
        'message' => $data['message'],
        'file' => $data['file'],
        'line' => $data['line'],
        'type' => $data['type'],
    ];
    try {
        $hash = hash('sha256', json_encode($identifyingData, JSON_THROW_ON_ERROR));
    } catch (JsonException $e) {
        /** @noinspection ForgottenDebugOutputInspection */
        error_log(sprintf('Failed to encode error data for hashing: %s', $e->getMessage()));

        return false;
    }

    return $ventura_storage_provider->saveError($hash, $data, $context);
}

function _get_context(): array
{
    return $GLOBALS;
}

function _exception_handler(Throwable $exception): void
{
    $data = [
        'code' => $exception->getCode(),
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTrace(),
        'type' => get_class($exception),
    ];

    $context = _get_context();

    if (ini_get('display_errors')) {
        $errorMessage = "Uncaught " . get_class($exception) . ": " . $exception->getMessage() .
            " in " . $exception->getFile() .
            " on line " . $exception->getLine();
        echo $errorMessage . PHP_EOL;
    }

    _save_error($data, $context);
}

/** @var $ventura_storage_provider StorageProvider|null Global variable to hold the storage provider instance (simple approach) */
global $ventura_storage_provider;
$ventura_storage_provider = null;

function bootstrap(): void
{
    global $ventura_storage_provider;

    // Read configuration from environment variables, with defaults
    $providerClass = getenv('VENTURA_STORAGE_PROVIDER_CLASS') ?: RedBeanStorageProvider::class;
    $dataDir = getenv('VENTURA_STORAGE_DATA_DIR') ?: VENTURA_DATA_DIR;

    if (!class_exists($providerClass) || !is_subclass_of($providerClass, StorageProvider::class)) {
        error_log("Invalid or missing storage provider class configured: " . $providerClass);
        $ventura_storage_provider = null;

        return;
    }

    try {
        if ($providerClass === RedBeanStorageProvider::class) {
            $ventura_storage_provider = new $providerClass($dataDir);
        } else {
            error_log("Unsupported storage provider class configured: " . $providerClass);
            $ventura_storage_provider = null;

            return;
        }

        $ventura_storage_provider->initialize();
    } catch (RuntimeException $e) {
        error_log("Failed to initialize Ventura Storage Provider: " . $e->getMessage());
        $ventura_storage_provider = null;

        return;
    } catch (Throwable $e) {
        // Catch potential instantiation errors too
        error_log("Failed to instantiate Ventura Storage Provider ($providerClass): " . $e->getMessage());
        $ventura_storage_provider = null;

        return;
    }

    // Set the custom error/exception handlers
    set_error_handler(__NAMESPACE__ . "\\_error_handler");
    set_exception_handler(__NAMESPACE__ . "\\_exception_handler");
}

bootstrap();
