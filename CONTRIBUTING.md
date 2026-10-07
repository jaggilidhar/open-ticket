# Contributing

Use PHP 8.2+, InnoDB MySQL/MariaDB, and no required Composer/runtime build step for release installation. Preserve the installer lock and protect configuration files. All state-changing requests must verify CSRF and authorization. Keep event inventory transactions serialized through the event-row lock. Never overwrite an installed site's config or database in a release package.

Run PHP lint, tests/integration.py against a disposable database, and scripts/package.py before submitting a pull request. Review the generated ZIP to ensure no live storage files or credentials are included. Contributions are under the MIT license.
