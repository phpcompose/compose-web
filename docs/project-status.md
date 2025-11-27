# Compose Web – Project Status (as of pause)

## Current state
- **Auth/users**: Password auth (AuthService, PasswordAuthenticator, SessionAuthStorage, PasswordHasher). User admin list/create/edit with profile/preferences JSON and optional password change. ACL is path-based (`/admin`, `/admin/users` require `admin`); AuthGuard + ACL middleware wired (priorities 10/15). Registration/login/logout pages.
- **Forms/validation**: FormBuilder with filters/validators (incl. MatchField), CSRF via SessionCsrfTokenProvider. Submission carries result/errors.
- **Admin UI**: Bootstrap layout with nav/sidebar driven by `admin.modules` (Users, Contact). Admin pages: `/admin` stub, `/admin/users` list, `/admin/users/form[/{id}]`.
- **Contact module**: Config supports multiple forms under `modules.contact.forms` with root `messages`/`email` defaults. Single handler `src/Module/Contact/Page/ContactPage.php` serves `/contact` and `/contact/{slug}`. Template `pages/contact/index.phtml` uses formConfig/messages. Service accepts per-form email settings and records entries.
- **Contact entries**: Table `cw_contact_entries` (migrations `Version20251121170000`, `Version20251121171500` for tags/read/star) with columns: id, form_slug, email, subject, payload json, tags json nullable, is_read/is_starred booleans, created_at; indexes on form_slug, created_at, is_read, is_starred. Repo `DbalContactEntryRepository` supports record/find/fetchRecent, setRead/setStarred/setTags. Admin list at `/admin/contact/list` shows entries/payload/tags and read/star toggles via same handler.
- **Email**: Emailer with plugin choice (Log/PhpMailer). Config chooses plugin via env `EMAIL_PLUGIN`; SMTP options in config.
- **Misc**: Templates registered; Dockerfile installs pdo_mysql; composer scripts `composer test`, `composer serve`. Migrations config `migrations.php` uses table `cw_migration_versions`. Tests currently passing.

## How to resume
- Ensure DB env vars are set in `.env` and run migrations:  
  `php vendor/bin/doctrine-migrations migrate --configuration=migrations.php`
- If `Version20251121171500` failed earlier (JSON default issue), rerun after the fix (tags column now nullable).

## Next steps / backlog
- Contact admin: add detail view, filters (form slug/read/star), tag editing UI (repo already supports setTags).
- Content pipeline: add content module (pages/blog with JSON payload), taxonomy/tagging, comments + admin.
- Infrastructure: plugin/events via Compose dispatcher; logging subscriber; consider route/name-based ACL; seed default roles/users.
- Testing: add integration tests for contact entry repository/service and admin toggles; seed initial admin user/role data for dev.
