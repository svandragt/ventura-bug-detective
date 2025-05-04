<?php

namespace Ventura\Storage;

interface StorageProvider
{
    /**
     * Initializes the storage connection and setup.
     *
     * @return void
     * @throws \RuntimeException If initialization fails (e.g., cannot create data directory).
     */
    public function initialize() : void;

    /**
     * Saves error details and context to the storage.
     * Groups identical errors based on a hash.
     * Increments a counter for existing errors or creates a new entry.
     *
     * @param string $hash A unique hash representing the error signature.
     * @param array  $error_data Error details (containing 'code', 'message', 'file', 'line', 'type', 'trace').
     * @param array  $context_data Contextual information associated with the error.
     *
     * @return bool True if the error and context were stored successfully, false otherwise.
     */
    public function saveError(string $hash, array $error_data, array $context_data) : bool;
}
