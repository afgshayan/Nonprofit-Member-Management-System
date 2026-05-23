# Nonprofit Members Portal

A complete **member management system** built for nonprofits, associations, and organizations. Manage your member database, control access by role, import/export data, and configure the portal — all through a clean, modern web interface.

---

## Features

- **Member database** — store full member profiles: name, province, city, country, email, phone, WhatsApp, gender, birth year, education, expertise, event, notes
- **Search & filter** — live search with filters by province, education, event, and expertise
- **Sortable columns** — click any column header to sort ascending/descending
- **Pagination** — configurable per-page results (10 / 25 / 50 / 100)
- **CSV import** — bulk-import members from a CSV file with validation and error reporting
- **CSV export** — export the full member list (with active filters applied)
- **Media management** — upload and attach images/documents to member profiles
- **Certificate issuance** — generate, issue, and manage digital certificates for members with public verification URL
- **Public certificate verification** — unique shareable links for certificate verification without login
- **Role-based access control** — three user roles with different permission levels
- **User management** — admins can create, edit, and delete portal user accounts
- **Settings panel** — configure app name, per-page default, session lifetime, login rate limiting, and custom login URL slug
- **Web installer** — browser-based setup wizard; no command line needed
- **Responsive UI** — works on desktop and tablet screens

---

## Technology Stack

| Layer | Technology |
|---|---|
| **Backend framework** | Laravel 12 (PHP 8.2+) |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ via Eloquent ORM |
| **Frontend** | Bootstrap 5.3, Bootstrap Icons, vanilla JavaScript |
| **Typography** | Inter (Google Fonts) |
| **Auth** | Custom session-based authentication |
| **Routing** | Laravel resource routes with custom URL prefix |
| **Security middleware** | Custom `SecurityHeaders` middleware |
| **Role middleware** | Custom `CheckRole` middleware |
| **Templating** | Laravel Blade |
| **Soft deletes** | Laravel SoftDeletes (force-deleted on request) |
| **Settings storage** | Database-backed key/value settings table |
| **Installer** | Self-contained PHP installer (`public/install/index.php`) |

---

## User Roles

| Role | Permissions |
|---|---|
| **Admin** | Full access — members CRUD, import/export, settings, user management |
| **Editor** | Can view, add, and edit members — no delete, no settings, no user management |
| **Viewer** | Read-only — can only view member names and locations; contact info, event, and dates are hidden |

---

## Security

- **Password hashing** — all passwords stored with bcrypt (cost factor 12)
- **CSRF protection** — every form is protected by Laravel's CSRF token
- **Rate limiting** — login attempts are rate-limited (configurable via Settings)
- **Custom login slug** — the login URL can be changed to any slug (e.g. `/staff-login`) to obscure the entry point
- **Security headers** — every HTTP response includes:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- **Role-based access** — each controller method checks the user's role; unauthorized requests return HTTP 403
- **Viewer data masking** — viewer-role users cannot see email, phone, WhatsApp, event name, or registration date in any view
- **Installer lock** — after installation a `storage/installed.lock` file is created; the installer is blocked until this file is deleted
- **Admin-only destructive actions** — delete and bulk-delete are restricted to admin role only

---

## Installation

See [INSTALL.md](INSTALL.md) for full installation instructions.

Quick summary:
1. Upload project to your server
2. Create an empty MySQL database
3. Point your web server document root to the `public/` folder
4. Visit your domain — the installer opens automatically
5. Follow the 6-step wizard

---

## Project Structure

```
app/
  Http/
    Controllers/
      AuthController.php           — Login / logout
      PersonController.php         — Members CRUD, import, export
      MediaController.php          — Media uploads and management
      CertificateController.php    — Certificate issuance and public verification
      SettingController.php        — Settings panel
      UserController.php           — User management
    Middleware/
      SecurityHeaders.php          — HTTP security headers
      CheckRole.php                — Role-based route guard
  Models/
    Person.php                     — Member model (SoftDeletes)
    Media.php                      — Media attachments model
    Certificate.php                — Digital certificates model
    User.php                       — User model with role helpers
    Setting.php                    — Settings key/value model
resources/views/
  layouts/app.blade.php            — Main layout (sidebar + topbar)
  persons/                         — Member views (index, show, create, edit)
  media/                           — Media management views
  certificates/                    — Certificate views and public verification
  users/                           — User management views
  auth/                            — Login view
public/install/
  index.php                        — Self-contained web installer
storage/
  app/
    public/                        — Public file uploads (certificates, media)
    private/                       — Private file uploads
database/migrations/               — All database migrations
routes/web.php                     — Application routes
config/update.php                  — GitHub update checker configuration
```

---

## Migrating Between Domains

If you need to move an existing installation to a new domain, follow these steps:

### 1. **Database Settings**
- Update the `.env` file with the new database credentials (if different)
- The database contents remain the same during migration

### 2. **Application URL** (if different)
- Update `.env` `APP_URL` to match the new domain:
  ```
  APP_URL=https://new-domain.com
  ```

### 3. **File Uploads & Media**
- Copy the entire `storage/app/` directory to the new server (contains all uploaded media and certificates)
- Ensure the new server has write permissions: `chmod -R 755 storage/`

### 4. **Session & Cache Storage**
- Sessions are stored in the database, so they migrate automatically
- Clear the cache on the new server (optional):
  ```bash
  php artisan cache:clear
  php artisan config:clear
  ```

### 5. **Public Paths (if domain changed)**
- Public certificate verification URLs will automatically work with the new domain
- No manual updates needed — the system uses relative URLs

### 6. **Installer Lock**
- If the web installer is blocked (`storage/installed.lock`), delete it only if you need to re-run setup:
  ```bash
  rm storage/installed.lock
  ```

### 7. **SSL Certificate**
- If using HTTPS, ensure your SSL certificate is valid for the new domain
- Update `APP_URL` to use `https://` if securing the new domain

### Tips
- Test all features (member CRUD, CSV import/export, certificate verification) after migration
- Verify that file uploads work by testing media and certificate generation
- Check that the automatic update checker works correctly (it uses GitHub)

---

## License

Private / commercial use. All rights reserved.
