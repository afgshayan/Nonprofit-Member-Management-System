# Nonprofit Members Portal — Installation Guide

## Server Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.2 or higher |
| MySQL / MariaDB | 5.7 / 10.3 or higher |
| Web Server | Apache / Nginx / LiteSpeed|
| PHP Extensions | PDO, pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath |

---

## Installation Steps

### Step 1 — Upload the project

Upload the entire project folder to your server's document root or a subdirectory.

```
/var/www/html/members-portal
```

Make sure the following folders are **writable** by the web server:

```
storage/
bootstrap/cache/
```

### Step 2 — Create a MySQL database

Create an empty MySQL database and note the credentials (host, database name, username, password).

### Step 3 — Configure your web server

Point your virtual host document root to the `public/` folder inside the project.

Example Apache virtual host:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/members-portal/public
    <Directory /var/www/html/members-portal/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Make sure `mod_rewrite` is enabled and `.htaccess` files are allowed.

### Step 4 — Run the web installer

Open your browser and visit your domain. You will be automatically redirected to the installer:

```
https://yourdomain.com/install
```

Follow the on-screen steps:

| Step | Description |
|---|---|
| **1. Welcome** | Overview of the installer |
| **2. Requirements** | Auto-checks all required PHP extensions |
| **3. Database** | Enter MySQL host, database name, username, password |
| **4. Admin account** | Set your admin name, email, password and custom login page slug |
| **5. Install** | Creates all tables and seeds initial data automatically |
| **6. Done** | Click the login link and start using the portal |

> The installer writes a `storage/installed.lock` file when complete. If the lock file exists, the installer will redirect to the login page.

---

## Reinstall / Reset

To run the installer again from scratch, delete this file from the server:

```
storage/installed.lock
```

Then revisit `/install` in your browser. **Warning:** reinstalling will drop and recreate all database tables.

---

## Clearing Caches

If you make configuration changes after installation, run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

