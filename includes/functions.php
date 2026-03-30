<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function database_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['database_config'] ?? [];

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function initialize_error_handling(): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $initialized = true;

    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler(function (Throwable $throwable): never {
        $message = (bool) app_config('debug', false)
            ? sprintf('%s in %s on line %d', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine())
            : '';

        fail_with_error('server_error', $message, 500, [], [
            'exception' => get_class($throwable),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]);
    });

    register_shutdown_function(function (): void {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

        if (!in_array((int) ($error['type'] ?? 0), $fatalTypes, true)) {
            return;
        }

        $message = (bool) app_config('debug', false)
            ? sprintf('%s in %s on line %d', (string) ($error['message'] ?? 'Fatal error'), (string) ($error['file'] ?? 'unknown file'), (int) ($error['line'] ?? 0))
            : '';

        fail_with_error('server_error', $message, 500, [], [
            'file' => (string) ($error['file'] ?? ''),
            'line' => (int) ($error['line'] ?? 0),
        ]);
    });
}

function current_script_name(): string
{
    return basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
}

function wants_json_response(): bool
{
    $path = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

    return str_contains($path, '/ajax/')
        || str_contains($accept, 'application/json')
        || $requestedWith === 'xmlhttprequest';
}

function error_catalog(): array
{
    return [
        'database_connection' => [
            'title' => 'Database Connection Error',
            'message' => 'The application could not connect to the MySQL database for this request.',
            'solutions' => [
                'Confirm that the MySQL service is running and the scholargrid database exists.',
                'Check the host, port, username, and password in config/database.php.',
                'Make sure PHP is started with the project php.ini so pdo_mysql is loaded.',
            ],
        ],
        'missing_extension' => [
            'title' => 'Missing PHP Extension',
            'message' => 'A required PHP extension is not available in the active runtime.',
            'solutions' => [
                'Start the site with run-local.ps1 or run-local.bat so the local php.ini is used.',
                'Verify the required extension is enabled in php.ini and restart the server.',
                'Use php -m with the same config to confirm the extension is actually loaded.',
            ],
        ],
        'csrf' => [
            'title' => 'Security Validation Error',
            'message' => 'The form request could not be verified securely.',
            'solutions' => [
                'Refresh the page and submit the form again.',
                'Avoid opening the same form in multiple tabs before submitting.',
                'Clear the browser session and log in again if the problem continues.',
            ],
        ],
        'course_not_found' => [
            'title' => 'Course Not Found',
            'message' => 'The requested course does not exist or is no longer available.',
            'solutions' => [
                'Return to the course catalog and open a valid course from the list.',
                'Check that the course slug in the URL is correct.',
                'Confirm the course row exists in the courses table if you edited data manually.',
            ],
        ],
        'request_not_allowed' => [
            'title' => 'Request Method Not Allowed',
            'message' => 'This endpoint does not support the type of request that was sent.',
            'solutions' => [
                'Retry the action from the website instead of opening the endpoint directly.',
                'Make sure forms and JavaScript calls use the expected HTTP method.',
                'Reload the page and repeat the action from the correct screen.',
            ],
        ],
        'validation_error' => [
            'title' => 'Invalid Request Data',
            'message' => 'Some of the submitted values were incomplete or invalid.',
            'solutions' => [
                'Go back and review the submitted values.',
                'Start the action again from the page that created the request.',
                'If this came from a test action, reload the test page and continue from a fresh session.',
            ],
        ],
        'test_session' => [
            'title' => 'Test Session Error',
            'message' => 'The active test session could not be found or has expired.',
            'solutions' => [
                'Return to the course test page and start a new attempt.',
                'Avoid refreshing the browser repeatedly during an active test.',
                'If you were idle for a long time, restart the test session.',
            ],
        ],
        'server_error' => [
            'title' => 'Unexpected Application Error',
            'message' => 'An unexpected error interrupted this request.',
            'solutions' => [
                'Try the action again once.',
                'Return to the previous page and reload the screen.',
                'If the error keeps returning, review the server log and the reference code shown on this page.',
            ],
        ],
    ];
}

function build_error_payload(
    string $type,
    string $message = '',
    int $statusCode = 500,
    array $solutions = [],
    array $context = []
): array {
    $catalog = error_catalog();
    $template = $catalog[$type] ?? $catalog['server_error'];
    $reference = strtoupper(bin2hex(random_bytes(3)));
    $backUrl = trim((string) ($context['back_url'] ?? current_path_with_query()));

    if ($backUrl === '' || str_contains($backUrl, 'error.php')) {
        $backUrl = '/index.php';
    }

    return [
        'type' => $type,
        'title' => $template['title'],
        'message' => trim($message) !== '' ? $message : $template['message'],
        'solutions' => $solutions !== [] ? $solutions : $template['solutions'],
        'status_code' => $statusCode,
        'reference' => $reference,
        'back_url' => $backUrl,
        'created_at' => date('Y-m-d H:i:s'),
        'context' => $context,
    ];
}

function render_emergency_error(array $payload): never
{
    http_response_code((int) ($payload['status_code'] ?? 500));
    $title = e((string) ($payload['title'] ?? 'Unexpected Error'));
    $message = e((string) ($payload['message'] ?? 'A request error occurred.'));
    $reference = e((string) ($payload['reference'] ?? 'N/A'));
    $solutions = $payload['solutions'] ?? [];

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $title . '</title><style>body{font-family:Segoe UI,Tahoma,sans-serif;background:#f7f1e8;color:#1f2937;margin:0;padding:32px}main{max-width:760px;margin:0 auto;background:#fff;border-radius:24px;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,.08)}h1{margin-top:0}ol{line-height:1.7}code{background:#f2f4f8;padding:2px 6px;border-radius:6px}</style></head><body><main><p><strong>Error Reference:</strong> <code>' . $reference . '</code></p><h1>' . $title . '</h1><p>' . $message . '</p><ol>';

    foreach ($solutions as $solution) {
        echo '<li>' . e((string) $solution) . '</li>';
    }

    echo '</ol><p><a href="' . e(site_url('index.php')) . '">Return to Home</a></p></main></body></html>';
    exit;
}

function fail_with_error(
    string $type,
    string $message = '',
    int $statusCode = 500,
    array $solutions = [],
    array $context = []
): never {
    static $handling = false;

    $payload = build_error_payload($type, $message, $statusCode, $solutions, $context);

    if ($handling || current_script_name() === 'error.php') {
        render_emergency_error($payload);
    }

    $handling = true;

    if (wants_json_response()) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $payload,
            'redirect_url' => site_url('error.php'),
        ]);
        exit;
    }

    $_SESSION['app_error'] = $payload;

    if (!headers_sent()) {
        header('Location: ' . site_url('error.php'));
        exit;
    }

    render_emergency_error($payload);
}

function get_error_page_payload(): array
{
    $payload = $_SESSION['app_error'] ?? null;
    unset($_SESSION['app_error']);

    if (is_array($payload)) {
        return $payload;
    }

    return build_error_payload('server_error', '', 500, [], ['back_url' => '/index.php']);
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_mysql')) {
        fail_with_error('missing_extension', 'The pdo_mysql extension is not enabled in the active PHP runtime.', 500);
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        database_config('host'),
        database_config('port'),
        database_config('database'),
        database_config('charset', 'utf8mb4')
    );

    $sslCa = trim((string) database_config('ssl_ca', ''));
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if ($sslCa !== '' && is_file($sslCa)) {
        $sslCaAttribute = defined('Pdo\\Mysql::ATTR_SSL_CA')
            ? constant('Pdo\\Mysql::ATTR_SSL_CA')
            : (defined('PDO::MYSQL_ATTR_SSL_CA') ? constant('PDO::MYSQL_ATTR_SSL_CA') : null);

        if ($sslCaAttribute !== null) {
            $options[$sslCaAttribute] = $sslCa;
        }
    }

    $verifyServerCert = database_config('ssl_verify_server_cert', null);

    if ($verifyServerCert !== null) {
        $verifyAttribute = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
            ? constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
            : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') : null);

        if ($verifyAttribute !== null) {
            $options[$verifyAttribute] = (bool) $verifyServerCert;
        }
    }

    try {
        $pdo = new PDO(
            $dsn,
            (string) database_config('username'),
            (string) database_config('password'),
            $options
        );
    } catch (PDOException $exception) {
        $message = (bool) app_config('debug', false)
            ? 'Database connection failed: ' . $exception->getMessage()
            : '';

        fail_with_error('database_connection', $message, 500);
    }

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function site_url(string $path = ''): string
{
    $base = rtrim((string) app_config('app_url', ''), '/');
    $path = ltrim($path, '/');

    if ($base === '') {
        return $path === '' ? '/' : '/' . $path;
    }

    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function redirect(string $path = ''): never
{
    header('Location: ' . site_url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($sessionToken, $submittedToken)) {
        fail_with_error('csrf', '', 419);
    }
}

function ensure_openssl(): void
{
    if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
        fail_with_error('missing_extension', 'The OpenSSL extension is not available in the active PHP runtime.', 500, [
            'Start the site with run-local.ps1 or run-local.bat so the project php.ini is loaded.',
            'Confirm that the openssl extension is enabled in the active PHP configuration.',
            'Restart the server after changing php.ini and verify openssl appears in php -m.',
        ]);
    }
}

function encryption_key(): string
{
    ensure_openssl();

    return hash('sha256', (string) app_config('app_key'), true);
}

function encrypt_value(?string $value): string
{
    ensure_openssl();

    $plainText = trim((string) $value);

    if ($plainText === '') {
        return '';
    }

    $iv = random_bytes(16);
    $cipherText = openssl_encrypt(
        $plainText,
        'AES-256-CBC',
        encryption_key(),
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($cipherText === false) {
        throw new RuntimeException('Encryption failed.');
    }

    $mac = hash_hmac('sha256', $iv . $cipherText, encryption_key(), true);

    return base64_encode($iv . $mac . $cipherText);
}

function decrypt_value(?string $payload): string
{
    ensure_openssl();

    if ($payload === null || trim($payload) === '') {
        return '';
    }

    $decoded = base64_decode($payload, true);

    if ($decoded === false || strlen($decoded) < 49) {
        return '';
    }

    $iv = substr($decoded, 0, 16);
    $mac = substr($decoded, 16, 32);
    $cipherText = substr($decoded, 48);
    $calculatedMac = hash_hmac('sha256', $iv . $cipherText, encryption_key(), true);

    if (!hash_equals($mac, $calculatedMac)) {
        return '';
    }

    $plainText = openssl_decrypt(
        $cipherText,
        'AES-256-CBC',
        encryption_key(),
        OPENSSL_RAW_DATA,
        $iv
    );

    return $plainText === false ? '' : $plainText;
}

function lookup_hash(string $value): string
{
    $normalized = strtolower(trim($value));

    return hash('sha256', $normalized . '|' . app_config('lookup_salt'));
}

function table_has_column(string $table, string $column): bool
{
    static $cache = [];

    $cacheKey = strtolower($table . '.' . $column);

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $statement = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column'
    );
    $statement->execute([
        'table' => $table,
        'column' => $column,
    ]);

    $cache[$cacheKey] = (int) $statement->fetchColumn() > 0;

    return $cache[$cacheKey];
}

function student_uses_encrypted_profile(): bool
{
    return table_has_column('students', 'email_hash')
        && table_has_column('students', 'name_cipher')
        && table_has_column('students', 'email_cipher')
        && table_has_column('students', 'contact_cipher')
        && table_has_column('students', 'age_cipher');
}

function admin_uses_encrypted_profile(): bool
{
    return table_has_column('admins', 'email_hash')
        && table_has_column('admins', 'name_cipher')
        && table_has_column('admins', 'email_cipher');
}

function hash_secret(string $value): string
{
    $iterations = 600000;
    $salt = random_bytes(16);
    $hash = hash_pbkdf2('sha256', $value, $salt, $iterations, 32, true);

    return sprintf(
        'pbkdf2_sha256$%d$%s$%s',
        $iterations,
        base64_encode($salt),
        base64_encode($hash)
    );
}

function verify_secret(string $plainText, string $storedHash): bool
{
    if (str_starts_with($storedHash, 'pbkdf2_sha256$')) {
        $parts = explode('$', $storedHash);

        if (count($parts) !== 4) {
            return false;
        }

        [, $iterations, $saltEncoded, $hashEncoded] = $parts;
        $salt = base64_decode($saltEncoded, true);
        $expected = base64_decode($hashEncoded, true);

        if ($salt === false || $expected === false) {
            return false;
        }

        $calculated = hash_pbkdf2('sha256', $plainText, $salt, (int) $iterations, 32, true);

        return hash_equals($expected, $calculated);
    }

    return password_verify($plainText, $storedHash);
}

function record_password_matches(array $record, string $plainText): bool
{
    $storedSecret = null;

    if (isset($record['password_hash']) && trim((string) $record['password_hash']) !== '') {
        $storedSecret = (string) $record['password_hash'];
    } elseif (isset($record['password']) && trim((string) $record['password']) !== '') {
        $storedSecret = (string) $record['password'];
    }

    if ($storedSecret === null) {
        return false;
    }

    if (verify_secret($plainText, $storedSecret)) {
        return true;
    }

    return hash_equals($storedSecret, $plainText);
}

function record_is_active(array $record): bool
{
    if (!array_key_exists('status', $record)) {
        return true;
    }

    return strtolower(trim((string) $record['status'])) === 'active';
}

function selected_value(array $row, string $key, bool $encrypted = false): string
{
    if (!array_key_exists($key, $row)) {
        return '';
    }

    $value = (string) $row[$key];

    if ($value === '') {
        return '';
    }

    return $encrypted ? decrypt_value($value) : $value;
}

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function student_id(): ?int
{
    if (current_role() !== 'student' || empty($_SESSION['student_id'])) {
        return null;
    }

    return (int) $_SESSION['student_id'];
}

function admin_id(): ?int
{
    if (current_role() !== 'admin' || empty($_SESSION['admin_id'])) {
        return null;
    }

    return (int) $_SESSION['admin_id'];
}

function is_student_logged_in(): bool
{
    return student_id() !== null;
}

function is_admin_logged_in(): bool
{
    return admin_id() !== null;
}

function current_path_with_query(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $base = rtrim((string) app_config('app_url', ''), '/');

    if ($base !== '' && str_starts_with($uri, $base)) {
        $trimmed = substr($uri, strlen($base));

        return $trimmed === '' ? '/' : $trimmed;
    }

    return $uri;
}

function sanitize_next_path(?string $path): string
{
    $candidate = trim((string) $path);

    if ($candidate === '' || str_contains($candidate, '://') || str_starts_with($candidate, '//')) {
        return '/dashboard.php';
    }

    return str_starts_with($candidate, '/') ? $candidate : '/' . ltrim($candidate, '/');
}

function require_student(): void
{
    if (!is_student_logged_in()) {
        $next = urlencode(current_path_with_query());
        redirect('login.php?next=' . $next);
    }
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect('login.php?role=admin');
    }
}

function current_student(): ?array
{
    static $student = false;

    if ($student !== false) {
        return $student;
    }

    $id = student_id();

    if ($id === null) {
        $student = null;

        return $student;
    }

    $statement = db()->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    $student = $row ? hydrate_student($row) : null;

    return $student;
}

function current_admin(): ?array
{
    static $admin = false;

    if ($admin !== false) {
        return $admin;
    }

    $id = admin_id();

    if ($id === null) {
        $admin = null;

        return $admin;
    }

    $statement = db()->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    $admin = $row ? hydrate_admin($row) : null;

    return $admin;
}

function hydrate_student(array $row): array
{
    if (array_key_exists('name_cipher', $row)) {
        $row['name'] = decrypt_value($row['name_cipher'] ?? '');
    } else {
        $row['name'] = (string) ($row['name'] ?? '');
    }

    if (array_key_exists('email_cipher', $row)) {
        $row['email'] = decrypt_value($row['email_cipher'] ?? '');
    } else {
        $row['email'] = (string) ($row['email'] ?? '');
    }

    if (array_key_exists('contact_cipher', $row)) {
        $row['contact_number'] = decrypt_value($row['contact_cipher'] ?? '');
    } else {
        $row['contact_number'] = (string) ($row['contact_number'] ?? '');
    }

    if (array_key_exists('age_cipher', $row)) {
        $row['age'] = decrypt_value($row['age_cipher'] ?? '');
    } else {
        $row['age'] = (string) ($row['age'] ?? '');
    }

    return $row;
}

function hydrate_admin(array $row): array
{
    if (array_key_exists('name_cipher', $row)) {
        $row['name'] = decrypt_value($row['name_cipher'] ?? '');
    } else {
        $row['name'] = (string) ($row['name'] ?? '');
    }

    if (array_key_exists('email_cipher', $row)) {
        $row['email'] = decrypt_value($row['email_cipher'] ?? '');
    } else {
        $row['email'] = (string) ($row['email'] ?? '');
    }

    return $row;
}

function course_display_title(array $course): string
{
    $slug = (string) ($course['slug'] ?? '');
    $title = (string) ($course['title'] ?? '');

    return match ($slug) {
        'css' => 'Cascading Style Sheets',
        'c' => 'Programming C',
        'cpp' => 'Programming C++',
        default => $title,
    };
}

function course_search_aliases(): array
{
    return [
        'css' => ['Cascading Style Sheets'],
        'c' => ['Programming C'],
        'cpp' => ['Programming C++'],
    ];
}

function all_courses(?string $search = null): array
{
    $sql = 'SELECT * FROM courses';
    $params = [];

    if ($search !== null && trim($search) !== '') {
        $sql .= ' WHERE title LIKE :search OR short_description LIKE :search';
        $params['search'] = '%' . trim($search) . '%';

        $index = 0;
        foreach (course_search_aliases() as $slug => $aliases) {
            foreach ($aliases as $alias) {
                $index++;
                $sql .= " OR (slug = :alias_slug_{$index} AND :alias_search_{$index} LIKE :alias_value_{$index})";
                $params["alias_slug_{$index}"] = $slug;
                $params["alias_search_{$index}"] = strtolower(trim($search));
                $params["alias_value_{$index}"] = '%' . strtolower($alias) . '%';
            }
        }
    }

    $sql .= ' ORDER BY title ASC';

    $statement = db()->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function find_course_by_slug(string $slug): ?array
{
    $statement = db()->prepare('SELECT * FROM courses WHERE slug = :slug LIMIT 1');
    $statement->execute(['slug' => $slug]);
    $course = $statement->fetch();

    return $course ?: null;
}

function course_options(): array
{
    $statement = db()->query('SELECT id, title, slug FROM courses ORDER BY title ASC');

    return $statement->fetchAll();
}

function course_topic_map(): array
{
    return [
        'html' => ['frontend', 'web-basics', 'markup'],
        'css' => ['frontend', 'web-basics', 'design'],
        'javascript' => ['frontend', 'programming', 'web-interactivity'],
        'python' => ['programming', 'python', 'data'],
        'machine-learning' => ['python', 'data', 'ai'],
        'dsa' => ['programming', 'core-cs', 'problem-solving'],
        'c' => ['programming', 'systems', 'core-cs'],
        'cpp' => ['programming', 'systems', 'core-cs'],
        'ethical-hacking' => ['security', 'systems', 'networking'],
        'linux' => ['systems', 'networking', 'backend'],
        'django' => ['python', 'backend', 'web-development'],
        'mysql' => ['database', 'backend', 'data'],
        'mongodb' => ['database', 'backend', 'data'],
    ];
}

function topic_label(string $topic): string
{
    return match ($topic) {
        'frontend' => 'frontend development',
        'web-basics' => 'web basics',
        'markup' => 'markup structure',
        'design' => 'UI styling',
        'programming' => 'programming fundamentals',
        'web-interactivity' => 'web interactivity',
        'python' => 'Python-based learning',
        'data' => 'data-focused topics',
        'ai' => 'machine learning and AI',
        'core-cs' => 'core computer science',
        'problem-solving' => 'problem solving',
        'systems' => 'systems programming',
        'security' => 'security and hardening',
        'networking' => 'networking and infrastructure',
        'backend' => 'backend engineering',
        'web-development' => 'web development',
        'database' => 'database systems',
        default => str_replace('-', ' ', $topic),
    };
}

function student_interest_profile(int $studentId): array
{
    $statement = db()->prepare(
        'SELECT
            c.slug,
            scp.total_tests,
            scp.best_score,
            scp.last_score,
            scp.last_viewed_at,
            COUNT(ta.id) AS attempt_count
         FROM student_course_progress scp
         INNER JOIN courses c ON c.id = scp.course_id
         LEFT JOIN test_attempts ta
            ON ta.student_id = scp.student_id
           AND ta.course_id = scp.course_id
         WHERE scp.student_id = :student_id
         GROUP BY c.slug, scp.total_tests, scp.best_score, scp.last_score, scp.last_viewed_at'
    );
    $statement->execute(['student_id' => $studentId]);

    $topicWeights = [];
    $courseWeights = [];
    $topicMap = course_topic_map();

    foreach ($statement->fetchAll() as $row) {
        $slug = (string) $row['slug'];
        $topics = $topicMap[$slug] ?? [];
        $lastViewedAt = strtotime((string) ($row['last_viewed_at'] ?? '')) ?: time();
        $daysSince = max(0, (int) floor((time() - $lastViewedAt) / 86400));
        $recencyWeight = max(0.5, 2.5 - min(2.0, $daysSince / 5));
        $engagementWeight = 1.0
            + min(4.0, (float) $row['total_tests'] * 1.2)
            + min(1.0, (float) $row['best_score'] / 20)
            + min(1.0, (float) $row['last_score'] / 20)
            + min(1.5, (float) $row['attempt_count'] * 0.4)
            + $recencyWeight;

        $courseWeights[$slug] = $engagementWeight;

        foreach ($topics as $topic) {
            $topicWeights[$topic] = ($topicWeights[$topic] ?? 0.0) + $engagementWeight;
        }
    }

    arsort($topicWeights);

    return [
        'topic_weights' => $topicWeights,
        'course_weights' => $courseWeights,
    ];
}

function personalized_related_courses(int $studentId, array $currentCourse, int $limit = 3): array
{
    $statement = db()->prepare(
        'SELECT id, title, slug, short_description
         FROM courses
         WHERE id != :id
         ORDER BY title ASC'
    );
    $statement->execute(['id' => (int) $currentCourse['id']]);
    $candidates = $statement->fetchAll();

    $topicMap = course_topic_map();
    $currentTopics = $topicMap[$currentCourse['slug']] ?? [];
    $profile = student_interest_profile($studentId);
    $topicWeights = $profile['topic_weights'];
    $courseWeights = $profile['course_weights'];
    $recommendations = [];

    foreach ($candidates as $candidate) {
        $topics = $topicMap[$candidate['slug']] ?? [];
        $sharedTopics = array_values(array_intersect($currentTopics, $topics));
        $matchingInterestTopics = [];
        $score = 0.0;

        foreach ($topics as $topic) {
            if (isset($topicWeights[$topic])) {
                $matchingInterestTopics[$topic] = $topicWeights[$topic];
                $score += $topicWeights[$topic];
            }
        }

        $score += count($sharedTopics) * 7.5;
        $score += ($courseWeights[$candidate['slug']] ?? 0.0) * 0.35;

        if ($score <= 0.0) {
            $score = (float) count($sharedTopics) * 3.0;
        }

        arsort($matchingInterestTopics);
        $reasonTopic = array_key_first($matchingInterestTopics);

        if ($reasonTopic !== null) {
            $reason = 'Matched to your recent interest in ' . topic_label($reasonTopic) . '.';
        } elseif ($sharedTopics !== []) {
            $reason = 'Matched because it belongs to the same study track.';
        } else {
            $reason = 'Recommended to expand your current learning path.';
        }

        $candidate['recommendation_score'] = $score;
        $candidate['recommendation_reason'] = $reason;
        $recommendations[] = $candidate;
    }

    usort($recommendations, function (array $left, array $right): int {
        if ((float) $left['recommendation_score'] === (float) $right['recommendation_score']) {
            return strcmp((string) $left['title'], (string) $right['title']);
        }

        return (float) $right['recommendation_score'] <=> (float) $left['recommendation_score'];
    });

    return array_slice($recommendations, 0, $limit);
}

function log_action(
    string $action,
    string $details = '',
    int $durationSeconds = 0,
    string $pageUrl = '',
    ?int $overrideStudentId = null,
    ?int $overrideAdminId = null
): void {
    $statement = db()->prepare(
        'INSERT INTO logs (
            student_id,
            admin_id,
            action,
            details,
            page_url,
            duration_seconds,
            ip_address,
            created_at
        ) VALUES (
            :student_id,
            :admin_id,
            :action,
            :details,
            :page_url,
            :duration_seconds,
            :ip_address,
            NOW()
        )'
    );

    $statement->execute([
        'student_id' => $overrideStudentId ?? student_id(),
        'admin_id' => $overrideAdminId ?? admin_id(),
        'action' => $action,
        'details' => $details,
        'page_url' => $pageUrl,
        'duration_seconds' => max(0, $durationSeconds),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ]);
}

function record_course_visit(int $studentId, int $courseId): void
{
    $statement = db()->prepare(
        'INSERT INTO student_course_progress (
            student_id,
            course_id,
            first_viewed_at,
            last_viewed_at,
            total_tests,
            best_score,
            last_score
        ) VALUES (
            :student_id,
            :course_id,
            NOW(),
            NOW(),
            0,
            0,
            0
        )
        ON DUPLICATE KEY UPDATE
            last_viewed_at = NOW()'
    );

    $statement->execute([
        'student_id' => $studentId,
        'course_id' => $courseId,
    ]);
}

function fetch_question_set(int $courseId, string $difficulty, int $limit = 20): array
{
    $statement = db()->prepare(
        'SELECT *
         FROM test_questions
         WHERE course_id = :course_id
           AND difficulty = :difficulty
           AND status = 1
         ORDER BY RAND()
         LIMIT ' . (int) $limit
    );
    $statement->execute([
        'course_id' => $courseId,
        'difficulty' => $difficulty,
    ]);

    return $statement->fetchAll();
}

function difficulty_badge(string $difficulty): string
{
    return match ($difficulty) {
        'advanced' => 'Advanced',
        'intermediate' => 'Intermediate',
        default => 'Beginner',
    };
}

function update_progress_after_attempt(
    int $studentId,
    int $courseId,
    int $score
): void {
    $statement = db()->prepare(
        'INSERT INTO student_course_progress (
            student_id,
            course_id,
            first_viewed_at,
            last_viewed_at,
            total_tests,
            best_score,
            last_score
        ) VALUES (
            :student_id,
            :course_id,
            NOW(),
            NOW(),
            1,
            :score,
            :score
        )
        ON DUPLICATE KEY UPDATE
            last_viewed_at = NOW(),
            total_tests = total_tests + 1,
            best_score = GREATEST(best_score, VALUES(best_score)),
            last_score = VALUES(last_score)'
    );

    $statement->execute([
        'student_id' => $studentId,
        'course_id' => $courseId,
        'score' => $score,
    ]);
}

function page_is_active(string $path): bool
{
    $current = current_path_with_query();

    return str_starts_with($current, '/' . ltrim($path, '/'));
}


