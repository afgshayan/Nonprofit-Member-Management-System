<?php
/**
 * Nonprofit Members Portal â€” Graphical Web Installer
 * Works standalone â€” no Laravel needed.
 * ----------------------------------------
 * Steps: welcome â†’ requirements â†’ database â†’ admin â†’ install â†’ done
 */

define('ROOT',    realpath(__DIR__ . '/../../'));
define('LOCK',    ROOT . '/storage/installed.lock');
define('ENV',     ROOT . '/.env');
define('ENVEXMP', ROOT . '/.env.example');

session_start();

// â”€â”€ Already installed? â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (file_exists(LOCK) && ($_GET['step'] ?? '') !== 'done') {
    $_SESSION['installer_done'] = true;
}

// â”€â”€ Step routing â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$step = $_GET['step'] ?? ($_SESSION['installer_step'] ?? 'welcome');
$allowed = ['welcome','requirements','database','admin','install','done'];
if (!in_array($step, $allowed)) $step = 'welcome';

// â”€â”€ POST handlers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$errors = [];
$info   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 'database') {
        $data = [
            'app_name' => trim($_POST['app_name'] ?? 'Nonprofit Members Portal'),
            'app_url'  => rtrim(trim($_POST['app_url'] ?? ''), '/'),
            'db_host'  => trim($_POST['db_host'] ?? '127.0.0.1'),
            'db_port'  => trim($_POST['db_port'] ?? '3306'),
            'db_name'  => trim($_POST['db_name'] ?? ''),
            'db_user'  => trim($_POST['db_user'] ?? ''),
            'db_pass'  => $_POST['db_pass'] ?? '',
        ];
        if (empty($data['db_name'])) $errors[] = 'Database name is required.';
        if (empty($data['db_user'])) $errors[] = 'Database username is required.';
        if (empty($data['app_url'])) $errors[] = 'Application URL is required.';

        if (empty($errors)) {
            // Test connection
            try {
                $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};charset=utf8mb4";
                $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                // Try to create / select the database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['db_name']}` 
                            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$data['db_name']}`");
            } catch (\Throwable $e) {
                $errors[] = 'Database connection failed: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $_SESSION['installer_db']   = $data;
            $_SESSION['installer_step'] = 'admin';
            header('Location: ?step=admin'); exit;
        }
    }

    elseif ($step === 'admin') {
        $admin = [
            'name'       => trim($_POST['admin_name']  ?? ''),
            'email'      => trim($_POST['admin_email'] ?? ''),
            'password'   => $_POST['admin_password']   ?? '',
            'confirm'    => $_POST['admin_confirm']     ?? '',
            'login_slug' => preg_replace('/[^a-zA-Z0-9\-_]/', '', $_POST['login_slug'] ?? 'login') ?: 'login',
        ];
        if (empty($admin['name']))     $errors[] = 'Full name is required.';
        if (empty($admin['email']))    $errors[] = 'Email address is required.';
        if (!filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if (strlen($admin['password']) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($admin['password'] !== $admin['confirm']) $errors[] = 'Passwords do not match.';
        if (in_array($admin['login_slug'], ['install','logout','dashboard','settings','persons','storage'])) {
            $errors[] = 'That slug is reserved. Please choose a different login path.';
        }

        if (empty($errors)) {
            $_SESSION['installer_admin'] = $admin;
            $_SESSION['installer_step']  = 'install';
            header('Location: ?step=install'); exit;
        }
    }

    elseif ($step === 'install') {
        $result = runInstallation();
        if ($result === true) {
            $_SESSION['installer_step'] = 'done';
            header('Location: ?step=done'); exit;
        } else {
            $errors = (array) $result;
        }
    }
}

// â”€â”€ Installation engine â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function runInstallation(): bool|array
{
    $db    = $_SESSION['installer_db']    ?? null;
    $admin = $_SESSION['installer_admin'] ?? null;
    if (!$db || !$admin) return ['Session data lost. Please start over.'];

    $errs = [];

    // Merge login_slug from admin step into db for writeEnv
    $db['login_slug'] = $admin['login_slug'] ?? 'login';

    // 1. Write .env
    try {
        writeEnv($db);
    } catch (\Throwable $e) {
        return ['Failed to write .env file: ' . $e->getMessage()];
    }

    // 2. Connect to DB
    try {
        $dsn = "mysql:host={$db['db_host']};port={$db['db_port']};dbname={$db['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (\Throwable $e) {
        return ['Database connection failed: ' . $e->getMessage()];
    }

    // 3. Create tables
    try {
        createTables($pdo);
    } catch (\Throwable $e) {
        return ['Failed to create tables: ' . $e->getMessage()];
    }

    // 4. Create admin user
    try {
        createAdmin($pdo, $admin);
    } catch (\Throwable $e) {
        return ['Failed to create admin user: ' . $e->getMessage()];
    }

    // 5. Seed default settings
    try {
        seedSettings($pdo, $db['app_name'], $admin['login_slug'] ?? 'login');
    } catch (\Throwable $e) {
        // non-fatal
    }

    // 6. Clear Laravel caches
    clearCaches();

    // 7. Create installed lock
    file_put_contents(LOCK, date('Y-m-d H:i:s') . "\nInstalled successfully.\n");

    return true;
}

function writeEnv(array $db): void
{
    $envPath = ENV;

    // If .env exists as a directory (can happen on some servers), remove it
    if (is_dir($envPath)) {
        if (!rmdir($envPath)) {
            throw new \RuntimeException(
                'A directory named ".env" exists in the project root and could not be removed. '
                . 'Please delete it manually via FTP/SSH and try again.'
            );
        }
    }

    // Verify the project root is writable
    if (!is_writable(dirname($envPath))) {
        throw new \RuntimeException(
            'The project root directory is not writable by the web server. '
            . 'Please set write permissions (chmod 755 or 775) on the project root and try again.'
        );
    }

    $template = file_exists(ENVEXMP) ? file_get_contents(ENVEXMP) : '';

    $appKey = 'base64:' . base64_encode(random_bytes(32));

    $replacements = [
        '/^APP_NAME=.*/m'              => 'APP_NAME="' . addslashes($db['app_name']) . '"',
        '/^APP_URL=.*/m'               => 'APP_URL=' . $db['app_url'],
        '/^APP_KEY=.*/m'               => 'APP_KEY=' . $appKey,
        '/^APP_ENV=.*/m'               => 'APP_ENV=production',
        '/^APP_DEBUG=.*/m'             => 'APP_DEBUG=false',
        '/^APP_LOCALE=.*/m'            => 'APP_LOCALE=en',
        '/^#?\s*DB_CONNECTION=.*/m'    => 'DB_CONNECTION=mysql',
        '/^#?\s*DB_HOST=.*/m'          => 'DB_HOST=' . $db['db_host'],
        '/^#?\s*DB_PORT=.*/m'          => 'DB_PORT=' . $db['db_port'],
        '/^#?\s*DB_DATABASE=.*/m'      => 'DB_DATABASE=' . $db['db_name'],
        '/^#?\s*DB_USERNAME=.*/m'      => 'DB_USERNAME=' . $db['db_user'],
        '/^#?\s*DB_PASSWORD=.*/m'      => 'DB_PASSWORD=' . $db['db_pass'],
        '/^#?\s*SESSION_DRIVER=.*/m'   => 'SESSION_DRIVER=file',
        '/^#?\s*CACHE_STORE=.*/m'      => 'CACHE_STORE=file',
        '/^#?\s*QUEUE_CONNECTION=.*/m' => 'QUEUE_CONNECTION=sync',
        '/^LOGIN_SLUG=.*/m'            => 'LOGIN_SLUG=' . ($db['login_slug'] ?? 'login'),
    ];

    if (!$template) {
        // Build minimal .env from scratch
        $template = implode("\n", [
            'APP_NAME="Nonprofit Members Portal"', 'APP_ENV=production', 'APP_KEY=',
            'APP_DEBUG=false', 'APP_URL=http://localhost', 'APP_LOCALE=en',
            '', 'LOG_CHANNEL=stack', 'LOG_LEVEL=error',
            '', 'DB_CONNECTION=mysql', 'DB_HOST=127.0.0.1', 'DB_PORT=3306',
            'DB_DATABASE=members_portal', 'DB_USERNAME=root', 'DB_PASSWORD=',
            '', 'SESSION_DRIVER=file', 'CACHE_STORE=file', 'QUEUE_CONNECTION=sync',
        ]);
    }

    foreach ($replacements as $pattern => $replacement) {
        $new = preg_replace($pattern, $replacement, $template);
        if ($new !== null) $template = $new;
    }

    $written = file_put_contents($envPath, $template);

    if ($written === false) {
        throw new \RuntimeException(
            'Failed to write the .env file. Please check that the web server has write permission '
            . 'to the project root directory and try again.'
        );
    }
}

function createTables(PDO $pdo): void
{
    $sqls = [
        // users
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(191) NOT NULL,
            `role` ENUM('admin','editor','viewer') NOT NULL DEFAULT 'admin',
            `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
            `password` VARCHAR(255) NOT NULL,
            `remember_token` VARCHAR(100) NULL DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `users_email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // password_reset_tokens
        "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
            `email` VARCHAR(191) NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // failed_jobs
        "CREATE TABLE IF NOT EXISTS `failed_jobs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `uuid` VARCHAR(255) NOT NULL,
            `connection` TEXT NOT NULL,
            `queue` TEXT NOT NULL,
            `payload` LONGTEXT NOT NULL,
            `exception` LONGTEXT NOT NULL,
            `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // jobs
        "CREATE TABLE IF NOT EXISTS `jobs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `queue` VARCHAR(191) NOT NULL,
            `payload` LONGTEXT NOT NULL,
            `attempts` TINYINT UNSIGNED NOT NULL,
            `reserved_at` INT UNSIGNED NULL DEFAULT NULL,
            `available_at` INT UNSIGNED NOT NULL,
            `created_at` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `jobs_queue_index` (`queue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // job_batches
        "CREATE TABLE IF NOT EXISTS `job_batches` (
            `id` VARCHAR(191) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `total_jobs` INT NOT NULL,
            `pending_jobs` INT NOT NULL,
            `failed_jobs` INT NOT NULL,
            `failed_job_ids` LONGTEXT NOT NULL,
            `options` MEDIUMTEXT NULL DEFAULT NULL,
            `cancelled_at` INT NULL DEFAULT NULL,
            `created_at` INT NOT NULL,
            `finished_at` INT NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // persons
        "CREATE TABLE IF NOT EXISTS `persons` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `first_name` VARCHAR(100) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `date_of_birth` DATE NULL DEFAULT NULL,
            `occupation` VARCHAR(200) NULL DEFAULT NULL,
            `email` VARCHAR(191) NULL DEFAULT NULL,
            `waen_email` VARCHAR(191) NULL DEFAULT NULL,
            `whatsapp` VARCHAR(30) NULL DEFAULT NULL,
            `phone` VARCHAR(30) NULL DEFAULT NULL,
            `street_address` VARCHAR(255) NULL DEFAULT NULL,
            `apartment` VARCHAR(100) NULL DEFAULT NULL,
            `city` VARCHAR(100) NULL DEFAULT NULL,
            `state_province` VARCHAR(100) NULL DEFAULT NULL,
            `zip_code` VARCHAR(20) NULL DEFAULT NULL,
            `country` VARCHAR(100) NULL DEFAULT NULL,
            `education` VARCHAR(100) NULL DEFAULT NULL,
            `gender` VARCHAR(20) NULL DEFAULT NULL,
            `facebook` VARCHAR(255) NULL DEFAULT NULL,
            `instagram` VARCHAR(255) NULL DEFAULT NULL,
            `linkedin` VARCHAR(255) NULL DEFAULT NULL,
            `twitter` VARCHAR(255) NULL DEFAULT NULL,
            `biography` TEXT NULL DEFAULT NULL,
            `headshot` VARCHAR(255) NULL DEFAULT NULL,
            `cv_file` VARCHAR(255) NULL DEFAULT NULL,
            `areas_of_expertise` TEXT NULL DEFAULT NULL,
            `proposed_initiatives` TEXT NULL DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `persons_email_unique` (`email`),
            KEY `persons_first_name_index` (`first_name`),
            KEY `persons_last_name_index` (`last_name`),
            KEY `persons_city_index` (`city`),
            KEY `persons_state_province_index` (`state_province`),
            KEY `persons_country_index` (`country`),
            KEY `persons_education_index` (`education`),
            KEY `persons_occupation_index` (`occupation`),
            KEY `persons_created_at_index` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // settings
        "CREATE TABLE IF NOT EXISTS `settings` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(100) NOT NULL,
            `value` TEXT NULL DEFAULT NULL,
            `group` VARCHAR(60) NOT NULL DEFAULT 'general',
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `settings_key_unique` (`key`),
            KEY `settings_group_index` (`group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // migrations tracking
        "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // media library
        "CREATE TABLE IF NOT EXISTS `media` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `filename` VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `path` VARCHAR(500) NOT NULL,
            `disk` VARCHAR(50) NOT NULL DEFAULT 'public',
            `mime_type` VARCHAR(100) NOT NULL,
            `size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `type` VARCHAR(20) NOT NULL DEFAULT 'file',
            `uploaded_by` BIGINT UNSIGNED NULL DEFAULT NULL,
            `alt_text` VARCHAR(255) NULL DEFAULT NULL,
            `description` TEXT NULL DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `media_type_index` (`type`),
            KEY `media_mime_type_index` (`mime_type`),
            KEY `media_uploaded_by_index` (`uploaded_by`),
            KEY `media_created_at_index` (`created_at`),
            CONSTRAINT `media_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // certificates
        "CREATE TABLE IF NOT EXISTS `certificates` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `person_id` BIGINT UNSIGNED NOT NULL,
            `certificate_number` VARCHAR(100) NOT NULL,
            `verify_token` VARCHAR(32) NOT NULL,
            `title` VARCHAR(255) NULL DEFAULT NULL,
            `issued_at` DATE NOT NULL,
            `notes` TEXT NULL DEFAULT NULL,
            `pdf_media_id` BIGINT UNSIGNED NULL DEFAULT NULL,
            `issued_by` BIGINT UNSIGNED NULL DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `certificates_certificate_number_unique` (`certificate_number`),
            UNIQUE KEY `certificates_verify_token_unique` (`verify_token`),
            KEY `certificates_person_id_issued_at_index` (`person_id`, `issued_at`),
            KEY `certificates_pdf_media_id_index` (`pdf_media_id`),
            KEY `certificates_issued_by_index` (`issued_by`),
            CONSTRAINT `certificates_person_id_foreign` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE,
            CONSTRAINT `certificates_pdf_media_id_foreign` FOREIGN KEY (`pdf_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
            CONSTRAINT `certificates_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];


    foreach ($sqls as $sql) {
        $pdo->exec($sql);
    }

    // Seed migrations table so "artisan migrate" doesn't re-run and wipe data
    $migrationNames = [
        '0001_01_01_000000_create_users_table',
        '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table',
        '2026_03_02_000000_create_persons_table',
        '2026_03_02_100000_create_settings_table',
        '2026_03_02_200000_add_extended_fields_to_persons_table',
        '2026_03_03_000000_add_role_to_users_table',
        '2026_03_04_000000_remove_city_birth_year_from_persons_table',
        '2026_03_27_000000_revamp_persons_fields',
        '2026_03_27_100000_create_media_table',
        '2026_05_23_000000_create_certificates_table',
    ];
    $existing = $pdo->query("SELECT migration FROM `migrations`")->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:m, 1)");
    foreach ($migrationNames as $m) {
        if (!in_array($m, $existing)) {
            $ins->execute([':m' => $m]);
        }
    }
}

function createAdmin(PDO $pdo, array $admin): void
{
    $now  = date('Y-m-d H:i:s');
    $hash = password_hash($admin['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        "INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`, `updated_at`)
         VALUES (:name, :email, :password, 'admin', :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE `password` = :password2, `role` = 'admin', `updated_at` = :updated_at2"
    );
    $stmt->execute([
        ':name'         => $admin['name'],
        ':email'        => $admin['email'],
        ':password'     => $hash,
        ':created_at'   => $now,
        ':updated_at'   => $now,
        ':password2'    => $hash,
        ':updated_at2'  => $now,
    ]);
}

function seedSettings(PDO $pdo, string $appName, string $loginSlug = 'login'): void
{
    $now = date('Y-m-d H:i:s');
    $defaults = [
        ['app_name','general',$appName],
        ['app_description','general','Member Management System'],
        ['timezone','general','UTC'],
        ['per_page_default','general','100'],
        ['date_format','general','Y-m-d'],
        ['session_lifetime','security','120'],
        ['login_max_attempts','security','5'],
        ['login_decay_seconds','security','60'],
        ['login_slug','security',$loginSlug],
        ['force_https','security','0'],
        ['remember_me_days','security','30'],
        ['captcha_type','captcha','none'],
        ['captcha_site_key','captcha',''],
        ['captcha_secret_key','captcha',''],
        ['captcha_theme','captcha','light'],
        ['captcha_language','captcha','en'],
        ['import_batch_size','import_export','500'],
        ['export_chunk_size','import_export','1000'],
        ['max_upload_mb','import_export','50'],
        ['root_access_title','general','Access Restricted'],
        ['root_access_message','general','You do not have permission to access this area. Please use the application link provided by your administrator.'],
    ];
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO `settings` (`key`,`group`,`value`,`created_at`,`updated_at`) VALUES (?,?,?,?,?)"
    );
    foreach ($defaults as [$k,$g,$v]) {
        $stmt->execute([$k,$g,$v,$now,$now]);
    }
}

function clearCaches(): void
{
    // Delete compiled files so Laravel regenerates them cleanly
    $cachePaths = [
        ROOT . '/bootstrap/cache/config.php',
        ROOT . '/bootstrap/cache/routes-v7.php',
        ROOT . '/bootstrap/cache/packages.php',
        ROOT . '/bootstrap/cache/services.php',
        ROOT . '/bootstrap/cache/events.php',
    ];
    foreach ($cachePaths as $p) {
        if (file_exists($p)) @unlink($p);
    }
    // Clear compiled views
    $viewsDir = ROOT . '/storage/framework/views';
    if (is_dir($viewsDir)) {
        foreach (glob($viewsDir . '/*.php') as $f) @unlink($f);
    }
    // Ensure writable directories exist
    $dirs = [
        ROOT . '/storage/logs',
        ROOT . '/storage/framework/sessions',
        ROOT . '/storage/framework/cache/data',
        ROOT . '/storage/framework/views',
        ROOT . '/storage/app/public',
    ];
    foreach ($dirs as $d) {
        if (!is_dir($d)) @mkdir($d, 0775, true);
    }
}

// â”€â”€ Requirements check â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function checkRequirements(): array
{
    $checks = [];

    // PHP version
    $checks[] = [
        'label'  => 'PHP Version >= 8.2',
        'pass'   => version_compare(PHP_VERSION, '8.2.0', '>='),
        'value'  => PHP_VERSION,
    ];

    // Extensions
    foreach (['pdo','pdo_mysql','mbstring','openssl','tokenizer','xml','ctype','json','bcmath','fileinfo','curl'] as $ext) {
        $checks[] = [
            'label' => "Extension: {$ext}",
            'pass'  => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'Loaded' : 'Missing',
        ];
    }

    // Writable paths
    $paths = [
        ROOT . '/.env'                            => '.env (file writable / parent writable)',
        ROOT . '/storage'                         => 'storage/',
        ROOT . '/bootstrap/cache'                 => 'bootstrap/cache/',
        ROOT . '/storage/framework/sessions'      => 'storage/framework/sessions/',
        ROOT . '/storage/framework/views'         => 'storage/framework/views/',
        ROOT . '/storage/framework/cache/data'    => 'storage/framework/cache/data/',
        ROOT . '/storage/logs'                    => 'storage/logs/',
    ];
    foreach ($paths as $path => $label) {
        // Special case: .env is a file, never a directory — just check parent is writable
        if ($path === ROOT . '/.env') {
            // If .env was accidentally created as a directory, remove it
            if (is_dir($path)) {
                @rmdir($path);
            }
            $writable = is_writable(dirname($path));
        } elseif (is_file($path)) {
            $writable = is_writable($path);
        } else {
            if (!is_dir($path)) @mkdir($path, 0775, true);
            $writable = is_writable($path);
        }
        $checks[] = [
            'label' => "Writable: {$label}",
            'pass'  => $writable,
            'value' => $writable ? 'Writable' : 'Not writable',
        ];
    }

    return $checks;
}

$requirements = ($step === 'requirements') ? checkRequirements() : [];
$allPass = !in_array(false, array_column($requirements, 'pass'), true);

// â”€â”€ Helper: step number â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$stepNum = ['welcome'=>1,'requirements'=>2,'database'=>3,'admin'=>4,'install'=>5,'done'=>6];
$current = $stepNum[$step] ?? 1;

// â”€â”€ Saved form values (repopulate on error) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$dbData    = $_SESSION['installer_db']    ?? [];
$adminData = $_SESSION['installer_admin'] ?? [];

// Auto-detect application base URL (public/install/ → 3 levels up = app root, no /public in URL)
$_proto          = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_base           = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/\\');
$_detectedAppUrl = $_proto . '://' . $_SERVER['HTTP_HOST'] . ($_base === '/' || $_base === '\\' ? '' : $_base);

// Read LOGIN_SLUG from .env for already-installed banner
$_loginSlugFromEnv = 'login';
if (file_exists(ENV)) {
    $_envRaw = file_get_contents(ENV);
    if (preg_match('/^LOGIN_SLUG=(.+)$/m', $_envRaw, $_slugM)) {
        $_loginSlugFromEnv = trim($_slugM[1]);
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nonprofit Members Portal - Installation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* â”€â”€ Reset & base â”€â”€ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 15px;
    line-height: 1.6;
    background: #f0f0f1;
    color: #1d2327;
    min-height: 100vh;
    padding: 0 16px 48px;
}

/* â”€â”€ Logo area â”€â”€ */
#logo {
    text-align: center;
    padding: 48px 0 28px;
}
#logo .logo-mark {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: #1d2327;
}
.logo-icon {
    width: 46px; height: 46px;
    background: #2271b1;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fff;
}
.logo-text { font-size: 1.55rem; font-weight: 700; letter-spacing: 1.5px; }
.logo-sub  { font-size: .75rem; color: #787c82; letter-spacing: .5px; margin-top: 2px; }

/* â”€â”€ Step progress â”€â”€ */
#step-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    max-width: 560px;
    margin: 0 auto 28px;
}
.sp-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .75rem;
    color: #a7aaad;
    white-space: nowrap;
}
.sp-item.done  { color: #00a32a; }
.sp-item.active { color: #2271b1; font-weight: 600; }
.sp-num {
    width: 22px; height: 22px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 700;
    border: 2px solid currentColor;
    flex-shrink: 0;
    background: transparent;
}
.sp-item.done .sp-num  { background: #00a32a; color: #fff; border-color: #00a32a; }
.sp-item.active .sp-num { background: #2271b1; color: #fff; border-color: #2271b1; }
.sp-line { flex: 1; min-width: 20px; height: 1px; background: #dcdcde; }
.sp-item.done + .sp-line { background: #00a32a; }

/* â”€â”€ Main container â”€â”€ */
#setup-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    max-width: 560px;
    margin: 0 auto;
    border-radius: 4px;
    padding: 32px 36px 28px;
}

/* â”€â”€ Headings â”€â”€ */
h1.setup-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1d2327;
    margin-bottom: 6px;
}
p.setup-sub {
    color: #787c82;
    font-size: .875rem;
    margin-bottom: 24px;
}

/* â”€â”€ Dividers â”€â”€ */
.setup-hr { border: none; border-top: 1px solid #f0f0f1; margin: 24px 0; }

/* â”€â”€ Form labels & inputs â”€â”€ */
.field { margin-bottom: 18px; }
.field label {
    display: block;
    font-size: .8125rem;
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 5px;
}
.field .hint {
    font-size: .75rem;
    color: #787c82;
    margin-top: 4px;
}
.field input[type=text],
.field input[type=email],
.field input[type=url],
.field input[type=password],
.field input[type=number],
.field select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: .875rem;
    font-family: inherit;
    color: #1d2327;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    line-height: 1.5;
}
.field input:focus, .field select:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}
.field-row { display: grid; gap: 16px; }
.field-row.col2 { grid-template-columns: 1fr 1fr; }
.field-row.col3 { grid-template-columns: 1fr 1fr 1fr; }

/* â”€â”€ Buttons â”€â”€ */
.btn-next {
    display: inline-flex; align-items: center; gap: 6px;
    background: #2271b1;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 9px 20px;
    font-size: .875rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s;
}
.btn-next:hover  { background: #135e96; color: #fff; }
.btn-back {
    display: inline-flex; align-items: center; gap: 6px;
    background: transparent;
    color: #2271b1;
    border: 1px solid #2271b1;
    border-radius: 4px;
    padding: 8px 18px;
    font-size: .875rem;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s;
}
.btn-back:hover { background: #f0f6fc; color: #135e96; border-color: #135e96; }
.action-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 28px;
    gap: 12px;
}
.action-row.end { justify-content: flex-end; }

/* â”€â”€ Notices â”€â”€ */
.notice {
    border-left: 4px solid #d63638;
    background: #fcf0f1;
    padding: 12px 14px;
    border-radius: 0 4px 4px 0;
    font-size: .8125rem;
    margin-bottom: 20px;
    color: #1d2327;
}
.notice ul { margin: 6px 0 0 18px; }
.notice-info  { border-color: #72aee6; background: #f0f6fc; }
.notice-success { border-color: #00a32a; background: #edfaef; }

/* â”€â”€ Requirements list â”€â”€ */
.req-list { margin: 0; padding: 0; list-style: none; }
.req-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    font-size: .8125rem;
    border-radius: 3px;
    margin-bottom: 3px;
}
.req-list li.pass { background: #edfaef; color: #1d2327; }
.req-list li.fail { background: #fcf0f1; color: #d63638; font-weight: 600; }
.req-list .req-label { flex: 1; }
.req-list .req-value { font-size: .75rem; color: #787c82; }
.req-list li.fail .req-value { color: #d63638; }
.check-icon { font-size: 1rem; flex-shrink: 0; }

/* â”€â”€ Feature grid (welcome) â”€â”€ */
.feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    border: 1px solid #f0f0f1;
    border-radius: 4px;
    background: #fafafa;
}
.feature-icon {
    width: 32px; height: 32px;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    color: #fff;
    flex-shrink: 0;
}
.feature-item strong { font-size: .8125rem; display: block; margin-bottom: 2px; }
.feature-item span   { font-size: .75rem; color: #787c82; }

/* â”€â”€ Summary table (install step) â”€â”€ */
.summary-table { width: 100%; border-collapse: collapse; font-size: .8125rem; margin-bottom: 24px; }
.summary-table td { padding: 7px 10px; border-bottom: 1px solid #f0f0f1; }
.summary-table td:first-child { color: #787c82; width: 40%; }
.summary-table td:last-child { font-weight: 600; }

/* â”€â”€ Done / already installed â”€â”€ */
.done-circle {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: #00a32a;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem;
    color: #fff;
    margin: 0 auto 16px;
}
.already-circle { background: #2271b1; }

/* â”€â”€ Footer â”€â”€ */
#setup-footer {
    text-align: center;
    font-size: .75rem;
    color: #a7aaad;
    margin-top: 18px;
}

/* -- Icon helpers (CSS Unicode escapes, encoding-safe) -- */
.ic-check::before  { content: '\2714'; }
.ic-cross::before  { content: '\2718'; }
.ic-arr-r::before  { content: '\00A0\2192'; }
.ic-arr-l::before  { content: '\2190\00A0'; }
.ic-rocket::before { content: '\1F680\00A0'; }
</style>
</head>
<body>

<div id="logo">
    <div class="logo-mark">
        <div class="logo-icon">&#128101;</div>
        <div>
            <div class="logo-text">Members Portal</div>
            <div class="logo-sub">Member Database Management</div>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['installer_done']) && $step !== 'done'): ?>
<div id="setup-container" style="text-align:center;">
    <div class="done-circle already-circle">&#128274;</div>
    <h1 class="setup-title" style="text-align:center;">Already Installed</h1>
    <p class="setup-sub" style="text-align:center;">Nonprofit Members Portal is already installed on this server.</p>
    <a href="<?= htmlspecialchars(rtrim($dbData['app_url'] ?? '..', '/')) ?>/<?= htmlspecialchars($_loginSlugFromEnv) ?>" class="btn-next" style="margin:0 auto;">
        Go to Application <span class="ic-arr-r"></span>
    </a>
</div>

<?php else: ?>

<?php /* â”€â”€ Step progress bar â”€â”€ */ ?>
<?php
$stepDefs = [1=>'Welcome',2=>'Check',3=>'Database',4=>'Admin',5=>'Install',6=>'Done'];
?>
<div id="step-progress">
<?php $i=0; foreach ($stepDefs as $n => $lbl): $cls = $n < $current ? 'done' : ($n === $current ? 'active' : ''); ?>
<?php if ($i++ > 0): ?><div class="sp-line"></div><?php endif; ?>
<div class="sp-item <?= $cls ?>">
    <span class="sp-num"><?= $n < $current ? '&#10004;' : $n ?></span>
    <span><?= $lbl ?></span>
</div>
<?php endforeach; ?>
</div>

<div id="setup-container">

    <?php if (!empty($errors)): ?>
    <div class="notice">
        <strong>Please correct the following errors:</strong>
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <?php /* â•â•â•â•â•â•â•â•â•â• WELCOME â•â•â•â•â•â•â•â•â•â• */ ?>
    <?php if ($step === 'welcome'): ?>
    <h1 class="setup-title">Welcome to Nonprofit Members Portal</h1>
    <p class="setup-sub">This installer will get your Nonprofit Members Portal up and running in just a few steps.</p>

    <div class="feature-grid">
        <?php foreach ([
            ['&#128101;','#2271b1','Member Records','Manage 100,000+ members'],
            ['&#128274;','#00a32a','Secure Login','Rate-limiting &amp; CAPTCHA'],
            ['&#128202;','#f0b429','CSV Tools','Bulk import &amp; export'],
            ['&#9881;&#65039;','#8250df','Settings','Advanced configuration'],
        ] as [$ico,$clr,$t,$d]): ?>
        <div class="feature-item">
            <div class="feature-icon" style="background:<?= $clr ?>;"><?= $ico ?></div>
            <div><strong><?= $t ?></strong><span><?= $d ?></span></div>
        </div>
        <?php endforeach; ?>
    </div>

    <hr class="setup-hr">
    <div class="action-row end">
        <a href="?step=requirements" class="btn-next">Let's go <span class="ic-arr-r"></span></a>
    </div>

    <?php /* â•â•â•â•â•â•â•â•â•â• REQUIREMENTS â•â•â•â•â•â•â•â•â•â• */ ?>
    <?php elseif ($step === 'requirements'): ?>
    <h1 class="setup-title">Server Requirements</h1>
    <p class="setup-sub">Checking that your server meets all requirements before we continue.</p>

    <ul class="req-list">
    <?php foreach ($requirements as $r): ?>
        <li class="<?= $r['pass'] ? '&#10004;' : '&#10008;' ?>">
            <span class="check-icon"><?= $r['pass'] ? '&#10004;' : '&#10008;' ?></span>
            <span class="req-label"><?= htmlspecialchars($r['label']) ?></span>
            <span class="req-value"><?= htmlspecialchars($r['value']) ?></span>
        </li>
    <?php endforeach; ?>
    </ul>

    <?php if (!$allPass): ?>
    <div class="notice" style="margin-top:16px;">
        <strong>Some requirements are not met.</strong> Please resolve the issues above before continuing.
    </div>
    <?php endif; ?>

    <hr class="setup-hr">
    <div class="action-row">
        <a href="?step=welcome" class="btn-back"><span class="ic-arr-l"></span> Back</a>
        <?php if ($allPass): ?>
        <a href="?step=database" class="btn-next">Continue <span class="ic-arr-r"></span></a>
        <?php else: ?>
        <a href="?step=requirements" class="btn-next">Re-check</a>
        <?php endif; ?>
    </div>

    <?php /* â•â•â•â•â•â•â•â•â•â• DATABASE â•â•â•â•â•â•â•â•â•â• */ ?>
    <?php elseif ($step === 'database'): ?>
    <h1 class="setup-title">Database &amp; Site Setup</h1>
    <p class="setup-sub">Enter your database credentials and the public URL of this application.</p>

    <form method="POST" action="?step=database" novalidate>
        <div class="field-row col2">
            <div class="field">
                <label>Site Name</label>
                <input type="text" name="app_name"
                       value="<?= htmlspecialchars($dbData['app_name'] ?? 'Nonprofit Members Portal') ?>" required>
            </div>
            <div class="field">
                <label>Application URL <span style="color:#d63638;">*</span></label>
                <input type="url" name="app_url"
                       placeholder="http://yourdomain.com/public"
                       value="<?= htmlspecialchars($dbData['app_url'] ?? $_detectedAppUrl) ?>" required>
                <div class="hint">No trailing slash. Example: https://example.com</div>
            </div>
        </div>

        <hr class="setup-hr">
        <p style="font-size:.8125rem;font-weight:600;color:#1d2327;margin-bottom:14px;">Database Connection</p>

        <div class="field-row col2">
            <div class="field">
                <label>Database Host</label>
                <input type="text" name="db_host"
                       value="<?= htmlspecialchars($dbData['db_host'] ?? '127.0.0.1') ?>">
            </div>
            <div class="field">
                <label>Port</label>
                <input type="number" name="db_port"
                       value="<?= htmlspecialchars($dbData['db_port'] ?? '3306') ?>">
            </div>
        </div>
        <div class="field-row col3">
            <div class="field">
                <label>Database Name <span style="color:#d63638;">*</span></label>
                <input type="text" name="db_name"
                       value="<?= htmlspecialchars($dbData['db_name'] ?? '') ?>" required>
                <div class="hint">Created if it doesn't exist.</div>
            </div>
            <div class="field">
                <label>Username <span style="color:#d63638;">*</span></label>
                <input type="text" name="db_user"
                       value="<?= htmlspecialchars($dbData['db_user'] ?? 'root') ?>" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="db_pass"
                       value="<?= htmlspecialchars($dbData['db_pass'] ?? '') ?>"
                       autocomplete="new-password">
            </div>
        </div>

        <hr class="setup-hr">
        <div class="action-row">
            <a href="?step=requirements" class="btn-back"><span class="ic-arr-l"></span> Back</a>
            <button type="submit" class="btn-next">Test &amp; Continue <span class="ic-arr-r"></span></button>
        </div>
    </form>

    <?php /* â•â•â•â•â•â•â•â•â•â• ADMIN â•â•â•â•â•â•â•â•â•â• */ ?>
    <?php elseif ($step === 'admin'): ?>
    <h1 class="setup-title">Admin Account</h1>
    <p class="setup-sub">Create the administrator account you'll use to log in.</p>

    <form method="POST" action="?step=admin" novalidate>
        <div class="field">
            <label>Full Name <span style="color:#d63638;">*</span></label>
            <input type="text" name="admin_name"
                   value="<?= htmlspecialchars($adminData['name'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label>Email Address <span style="color:#d63638;">*</span></label>
            <input type="email" name="admin_email"
                   value="<?= htmlspecialchars($adminData['email'] ?? '') ?>" required>
        </div>
        <div class="field-row col2">
            <div class="field">
                <label>Password <span style="color:#d63638;">*</span></label>
                <input type="password" name="admin_password"
                       autocomplete="new-password" required minlength="8">
                <div class="hint">At least 8 characters.</div>
            </div>
            <div class="field">
                <label>Confirm Password <span style="color:#d63638;">*</span></label>
                <input type="password" name="admin_confirm"
                       autocomplete="new-password" required>
            </div>
        </div>

        <div class="field">
            <label>Login Page URL Slug</label>
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="color:#787c82;font-size:.875rem;font-weight:600;">/</span>
                <input type="text" name="login_slug"
                       value="<?= htmlspecialchars($adminData['login_slug'] ?? 'login') ?>"
                       pattern="[a-zA-Z0-9\-_]+" maxlength="50" placeholder="login">
            </div>
            <div class="hint">URL path users visit to log in. Example: <strong>login</strong> &rarr; yourdomain.com/login</div>
        </div>

        <hr class="setup-hr">
        <div class="action-row">
            <a href="?step=database" class="btn-back"><span class="ic-arr-l"></span> Back</a>
            <button type="submit" class="btn-next">Review &amp; Install <span class="ic-arr-r"></span></button>
        </div>
    </form>

    <?php /* â•â•â•â•â•â•â•â•â•â• INSTALL â•â•â•â•â•â•â•â•â•â• */ ?>
    <?php elseif ($step === 'install'): ?>
    <?php $d = $_SESSION['installer_db'] ?? []; $a = $_SESSION['installer_admin'] ?? []; ?>

    <div id="summaryBox">
        <h1 class="setup-title">Ready to Install</h1>
        <p class="setup-sub">Review your settings below, then click Install.</p>

        <table class="summary-table">
            <tbody>
            <?php foreach ([
                'Site Name'    => $d['app_name'] ?? '',
                'App URL'      => $d['app_url']  ?? '',
                'Login URL'    => ($d['app_url'] ?? '') . '/' . ($a['login_slug'] ?? 'login'),
                'Database'     => ($d['db_name'] ?? '').' @ '.($d['db_host'] ?? '').':'.($d['db_port'] ?? ''),
                'DB User'      => $d['db_user']  ?? '',
                'Admin Email'  => $a['email']    ?? '',
            ] as $k => $v): ?>
            <tr><td><?= $k ?></td><td><?= htmlspecialchars($v) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" action="?step=install" id="installForm">
            <div class="action-row">
                <a href="?step=admin" class="btn-back" id="backBtn"><span class="ic-arr-l"></span> Back</a>
                <button type="submit" class="btn-next" id="installBtn">
                    <span class="ic-rocket"></span>Run the Installation
                </button>
            </div>
        </form>
    </div>

    <div id="installBox" style="display:none;text-align:center;padding:24px 0;">
        <div style="font-size:2.5rem;margin-bottom:12px;">&#9881;</div>
        <p style="font-weight:600;font-size:1rem;margin-bottom:6px;">Installing Nonprofit Members Portal...</p>
        <p style="color:#787c82;font-size:.875rem;">Writing configuration, creating tables, and setting up your account. Please wait.</p>
        <div style="margin-top:20px;height:4px;background:#f0f0f1;border-radius:2px;overflow:hidden;">
            <div id="progress-bar" style="height:100%;width:0%;background:#2271b1;border-radius:2px;transition:width 2s ease;"></div>
        </div>
    </div>

    <?php /* â•â•â•â•â•â•â•â•â•â• DONE â•â•â•â•â•â•â•â•â•â• */ ?>
    <?php elseif ($step === 'done'): ?>
    <?php
        $appUrl    = $_SESSION['installer_db']['app_url']       ?? '..';
        $loginSlug = $_SESSION['installer_admin']['login_slug'] ?? 'login';
    ?>
    <div style="text-align:center;padding:8px 0 4px;">
        <div class="done-circle">&#10004;</div>
        <h1 class="setup-title">Installation Complete!</h1>
        <p class="setup-sub">Nonprofit Members Portal has been installed successfully on your server.</p>

        <div class="notice notice-info" style="text-align:left;margin-bottom:20px;">
            <strong>&#128274; Security:</strong> Delete or rename the <code>public/install/</code>
            directory to prevent others from re-running the installer.
        </div>

        <a href="<?= htmlspecialchars($appUrl) ?>/<?= htmlspecialchars($loginSlug) ?>" class="btn-next" style="display:inline-flex;">
            Log in to Members Portal <span class="ic-arr-r"></span>
        </a>
    </div>
    <?php $_SESSION = []; ?>

    <?php endif; ?>

</div><!-- #setup-container -->
<?php endif; ?>

<div id="setup-footer">Nonprofit Members Portal &nbsp;&middot;&nbsp; Member Database Management</div>

<script>
var installBtn  = document.getElementById('installBtn');
var installForm = document.getElementById('installForm');
var installBox  = document.getElementById('installBox');
var summaryBox  = document.getElementById('summaryBox');
var backBtn     = document.getElementById('backBtn');

if (installForm) {
    installForm.addEventListener('submit', function() {
        if (summaryBox) summaryBox.style.display = 'none';
        if (backBtn)    backBtn.style.display     = 'none';
        if (installBox) installBox.style.display  = '';
        installBtn.disabled = true;
        // Animate progress bar
        setTimeout(function(){ document.getElementById('progress-bar').style.width='90%'; }, 100);
    });
}
</script>
</body>
</html>

