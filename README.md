# OpenTicket CMS v0.2.0 alpha

Download, upload, install in your browser. An MIT-licensed PHP/MySQL event ticketing CMS for Apache/cPanel or Ubuntu with PHP-FPM and Nginx.

## Install from the upload-ready ZIP

1. Enable HTTPS for your domain. Use PHP **8.2+** with **PDO MySQL**, and MySQL **8.0+** or MariaDB **10.6+**.
2. Upload and extract `OpenTicket-CMS-v0.2.0.zip` into the website folder. `index.php` must be in the domain's document root (or your chosen subfolder). This release has no Composer, Node, or terminal requirement for application installation.
3. Create an empty database and database user in your hosting panel. Grant that user privileges on that database, including CREATE/ALTER/INDEX and normal read/write privileges.
4. Make `storage/` writable by PHP. Prefer owner/group permissions (typically 0750/0770); do not make it world-writable.
5. Visit your domain. The wizard checks hosting requirements and generates `storage/install-key.php`.
6. Open that file in your hosting file manager and copy the value between the quotes into the wizard. This proves control of the uploaded files before creating an administrator.
7. Enter website name, timezone, database details, and administrator name/email/password. Click **Install OpenTicket**.
8. You are signed in to Admin studio. Create and publish an event. The installer disables itself once `storage/config.php` exists and removes the installation key.

Only `storage/` needs to remain writable. Keep `storage/config.php` private (0600, readable by the PHP process). Back up the database **and** this file. Do not delete configuration to reinstall an existing database.

The application has no sample events or sample administrator. Public users can register attendee accounts; only the administrator can manage events and admission.

## Included

- Browser installation wizard and requirement checks
- Native admin and attendee accounts; hashed passwords; password change
- Event create/edit/draft/publish/archive
- Homepage search and category filters, website name and introduction settings
- Site timezone, stored UTC event dates
- Free ticket booking, printable booking codes, My tickets, cancellation
- Capacity locking with InnoDB transactions, organizer check-in once per booking
- Attendee search and CSV export
- CSRF tokens, escaped content, prepared SQL, session rotation, login/signup throttling

## Alpha limitations

Free admission only. No Stripe, tax/fees, ticket types, email delivery, forgot-password emails, QR scanner, uploads, plugins, themes, automatic upgrades, or organization/staff roles yet. All people in a booking are admitted together. This is an installable first CMS release, not feature parity with WordPress or a completed paid-ticket system.

## Ubuntu hosting

Use `docs/UBUNTU.md` for server package installation and Nginx setup. Application users with an existing PHP/MySQL host can use only the browser wizard above.

## Releases and development

The upload-ready ZIP contains application files and install instructions only. GitHub's source ZIP also includes CI and tests; preferably use the packaged application ZIP.

Run `python3 scripts/package.py` to generate the upload-ready ZIP. CI packages it as an Actions artifact named **OpenTicket-CMS-install** after PHP lint and real MariaDB/HTTP integration tests pass.

For local development, PHP's built-in server and a disposable MySQL database can run the wizard on localhost. Never expose the PHP development server publicly.

The previous Cloudflare/TypeScript implementation remains in Git history (commit `43491c9`). This CMS replaces it as the main installation path. No automatic migration from the hosted preview is included. Existing preview data remains separate.

## License
MIT. See LICENSE. Contributions should include steps to reproduce changes and meaningful validation. See CONTRIBUTING.md.
