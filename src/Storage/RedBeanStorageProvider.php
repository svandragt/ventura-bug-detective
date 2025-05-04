<?php

namespace Ventura\Storage;

use JsonException;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;
use RedBeanPHP\RedException\SQL;
use RuntimeException;

class RedBeanStorageProvider implements StorageProvider
{
    private string $data_dir;
    private string $db_file;

    public function __construct(string $data_dir = VENTURA_DATA_DIR)
    {
        $this->data_dir = $data_dir;
        $this->db_file = $this->data_dir . '/ventura.db';
    }

    /**
     * @throws RuntimeException
     */
    public function initialize(): void
    {
        if (!is_dir($this->data_dir)) {
            if (!mkdir($this->data_dir, 0777, true) && !is_dir($this->data_dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $this->data_dir));
            }
        }

        R::setup(sprintf("sqlite:%s", $this->db_file));
        R::useWriterCache(true);
    }

    public function saveError(string $hash, array $error_data, array $context_data): bool
    {
        // Ensure data directory exists (double-check, though initialize should handle it)
        if (!is_dir($this->data_dir)) {
            error_log("Ventura data directory missing during saveError: " . $this->data_dir);

            return false;
        }

        $errorBean = R::findOne('error', 'hash = ?', [$hash]);

        if ($errorBean instanceof OODBBean) {
            $errorBean->count++;
            $errorBean->updated = time();
        } else {
            $errorBean = R::dispense('error');
            $this->populateNewErrorBean($errorBean, $hash, $error_data);
        }

        $contextBean = R::dispense('context');
        try {
            $contextBean->data = json_encode($context_data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            error_log(sprintf('Failed to encode context data: %s', $e->getMessage()));
            $contextBean->data = '[]';
        }
        $contextBean->created = time();

        // Link context to error
        $errorBean->ownContextList[] = $contextBean;

        try {
            R::store($errorBean);

            return true;
        } catch (SQL $e) {
            error_log(sprintf('Failed to store error data using RedBeanPHP: %s', $e->getMessage()));

            return false;
        }
    }

    /**
     * Helper function to populate a new error bean's properties.
     * Moved from the global scope into the RedBean specific implementation.
     *
     * @param OODBBean $errorBean The RedBeanPHP bean to populate.
     * @param string $hash The calculated hash for the error.
     * @param array $error_data The original error data array.
     *
     * @return void
     */
    private function populateNewErrorBean(OODBBean $errorBean, string $hash, array $error_data): void
    {
        $errorBean->hash = $hash;
        $errorBean->code = $error_data['code'];
        $errorBean->message = $error_data['message'];
        $errorBean->file = $error_data['file'];
        $errorBean->line = $error_data['line'];
        $errorBean->type = $error_data['type'];
        try {
            $errorBean->trace = json_encode($error_data['trace'], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            error_log(sprintf('Failed to encode trace data: %s', $e->getMessage()));
            $errorBean->trace = '[]';
        }
        $errorBean->count = 1;
        $errorBean->created = time();
        $errorBean->updated = time(); // Set updated time on creation
    }

    public function getTopErrors(int $limit = 50): array
    {
        $errors = R::findAll('error', ' ORDER BY count DESC LIMIT ?', [$limit]);

        return $errors;
    }

    /**
     * Retrieves error details by its unique hash identifier.
     *
     * @param string $hash The unique hash that identifies the error
     * @return array|null Array containing error details and context if found, null otherwise
     */
    public function getErrorById(string $hash): ?array
    {
        $error = R::findOne('error', 'hash = ?', [$hash]);

        if (!$error instanceof OODBBean) {
            return null;
        }

        $contextList = array_map(
            static fn($context) => json_decode($context->data, true),
            array_slice(array_values($error->ownContextList), -3)
        );

        return [
            'id' => $error->getID(),
            'hash' => $error->hash,
            'code' => $error->code,
            'message' => $error->message,
            'file' => $error->file,
            'line' => $error->line,
            'type' => $error->type,
            'trace' => json_decode($error->trace, true),
            'count' => $error->count,
            'created' => $error->created,
            'updated' => $error->updated,
            'context' => $contextList
        ];
    }
}
