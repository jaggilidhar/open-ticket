# Contributing

Open an issue describing the problem or feature before large changes. Fork the repository, create a feature branch, and submit a pull request with the behavior change and validation results.

Keep ownership checks server-side. Validate untrusted input, keep secrets out of source, and preserve the atomic reservation and check-in semantics. Add migrations instead of editing migrations already applied to a deployed database.

Run TypeScript checks, the SQL tests, and a build before submitting. Explain any checks you could not run. Contributions are under the MIT license.
