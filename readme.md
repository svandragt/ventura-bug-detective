# Ventura - Bug Detective

A minimal PHP error and exception handler designed to capture, group, and store errors using extensible storage
providers.

## Features

* **Error Handling:** Captures PHP errors (Warnings, Notices, Errors, etc.) using `set_error_handler`.
* **Exception Handling:** Catches uncaught exceptions using `set_exception_handler`.
* **Error Grouping:** Groups identical errors based on their code, message, file, line, and type using an SHA256 hash
  before storing.
* **Contextual Data:** Captures global context (`$GLOBALS`) at the time of the error/exception (Note: Capturing
  `$GLOBALS` can be memory-intensive and may include sensitive data; consider refining this in the future).
* **Extensible Storage:** Uses a `StorageProvider` interface (implied, based on configuration logic) allowing different
  storage backends.
* **Configurable:** Configuration primarily through environment variables.
* **Included Provider:** Comes with a `RedBeanStorageProvider` for storing errors using RedBeanPHP (presumably in a
  SQLite database by default).

## Storage Providers

Ventura is designed to work with different storage backends via implementations. `StorageProvider`

- **(Default):`Ventura\Storage\RedBeanStorageProvider`** Uses the RedBeanPHP library to store error data. By default, it
  likely creates a SQLite database file within the configured . Ensure you have `gabordemooij/redbean` required in
  your . `VENTURA_STORAGE_DATA_DIR``composer.json`

You can create your own provider by implementing the necessary methods (likely `initialize`, `saveError`) and
configuring Ventura to use your class via the environment variable. `VENTURA_STORAGE_PROVIDER_CLASS`

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues on the project repository.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.
