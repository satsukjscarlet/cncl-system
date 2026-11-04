<?php

// Local-only restore into a NEW database. Never drop or overwrite an existing database.
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = getopt('', ['source:', 'target:', 'mysql-bin:', 'execute']);
try {
    if (!$app->environment('local')) throw new RuntimeException('Only APP_ENV=local is allowed. Do not use this script on production.');
    $connection = Illuminate\Support\Facades\DB::connection();
    $config = $connection->getConfig();
    if ($connection->getDriverName() !== 'mysql' || !in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Only a local MySQL/MariaDB connection is allowed.');
    }
    $source = realpath($options['source'] ?? '');
    $target = $options['target'] ?? '';
    if (!$source || !is_file($source) || !is_readable($source)) throw new RuntimeException('Provide a readable --source file.');
    if (!preg_match('/^cncl_restore_[a-z0-9_]{1,40}$/D', $target)) throw new RuntimeException('Target must be a NEW database named cncl_restore_... (lowercase letters, numbers, underscores).');
    if ($connection->selectOne('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$target])) {
        throw new RuntimeException('Target already exists. Choose a NEW name. Nothing was overwritten.');
    }
    $bin = rtrim($options['mysql-bin'] ?? 'D:/xampp/mysql/bin', '/\\');
    $suffix = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
    $mysql = $bin . '/mysql' . $suffix;
    $dump = $bin . '/mysqldump' . $suffix;
    if (!is_file($mysql) || !is_file($dump)) throw new RuntimeException('mysql/mysqldump not found; provide --mysql-bin.');
    $header = fopen($source, 'rb');
    $gzip = fread($header, 2) === "\x1f\x8b";
    fclose($header);
    echo json_encode(['source' => $source, 'compressed_gzip' => $gzip, 'source_bytes' => filesize($source),
        'source_sha256' => hash_file('sha256', $source), 'current_database' => $connection->getDatabaseName(),
        'current_tables' => count($connection->select('SHOW TABLES')), 'new_database' => $target,
        'execute' => isset($options['execute'])], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    if (!isset($options['execute'])) exit(0);

    $directory = storage_path('app/private/backups/restore-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)));
    if (!mkdir($directory, 0770, true)) throw new RuntimeException('Cannot create private backup directory.');
    $environment = getenv();
    $environment['MYSQL_PWD'] = (string) ($config['password'] ?? '');
    $common = ['--protocol=TCP', '--host=' . $config['host'], '--port=' . ($config['port'] ?? 3306), '--user=' . $config['username'], '--default-character-set=utf8mb4'];
    $run = function (array $command, string $input, string $prefix) use ($environment): void {
        $process = proc_open($command, [0 => ['file', $input, 'r'], 1 => ['file', $prefix . '.out.log', 'w'], 2 => ['file', $prefix . '.err.log', 'w']], $pipes, null, $environment, ['bypass_shell' => true]);
        if (!is_resource($process)) throw new RuntimeException('Cannot start database client.');
        $code = proc_close($process);
        if ($code !== 0) throw new RuntimeException('Database client failed; exit=' . $code . '. See private log: ' . $prefix . '.err.log. Partial staging database is retained for inspection.');
    };
    $empty = $directory . '/empty-input';
    touch($empty);
    $backup = $directory . '/before-restore.sql';
    $run(array_merge([$dump], $common, ['--single-transaction', '--quick', '--hex-blob', '--routines', '--events', '--triggers', '--result-file=' . $backup, $connection->getDatabaseName()]), $empty, $directory . '/backup');
    $check = fopen($backup, 'rb');
    fseek($check, -min(4096, filesize($backup)), SEEK_END);
    $completed = str_contains(stream_get_contents($check), 'Dump completed');
    fclose($check);
    if (!$completed) throw new RuntimeException('Backup completion marker missing. Import stopped.');
    echo 'BACKUP ' . $backup . ' SHA256=' . hash_file('sha256', $backup) . PHP_EOL;

    $sql = $directory . '/source-expanded.sql';
    $input = $gzip ? gzopen($source, 'rb') : fopen($source, 'rb');
    $output = fopen($sql, 'xb');
    $tables = [];
    try {
        while (($line = $gzip ? gzgets($input) : fgets($input)) !== false) {
            // A mysqldump for a single database must not switch to another database or create server objects.
            if (preg_match('/^\s*(?:USE\s|(?:CREATE|ALTER|DROP)\s+(?:DATABASE|USER)\b|GRANT\s|REVOKE\s|DELIMITER\b|SOURCE\s|\\\\[.!])|^\/\*!\d*\s*(?:USE|CREATE DATABASE|DROP DATABASE|DEFINER)\b/i', $line)) {
                throw new RuntimeException('Unsupported cross-database/server directive in dump. Import stopped before creating target.');
            }
            if (preg_match('/^CREATE TABLE `([^`]+)`/', $line, $match)) $tables[] = $match[1];
            if (fwrite($output, $line) !== strlen($line)) throw new RuntimeException('Cannot write expanded SQL.');
        }
    } finally {
        $gzip ? gzclose($input) : fclose($input);
        fclose($output);
    }
    if (!$tables) throw new RuntimeException('No CREATE TABLE statements found.');
    $check = fopen($sql, 'rb');
    fseek($check, -min(4096, filesize($sql)), SEEK_END);
    $completed = str_contains(stream_get_contents($check), 'Dump completed');
    fclose($check);
    if (!$completed) throw new RuntimeException('Source dump completion marker missing. Import stopped before creating target.');
    $connection->statement('CREATE DATABASE `' . $target . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $run(array_merge([$mysql], $common, ['--binary-mode=1', '--max-allowed-packet=512M', $target]), $sql, $directory . '/import');
    $pdo = new PDO('mysql:host=' . $config['host'] . ';port=' . ($config['port'] ?? 3306) . ';dbname=' . $target . ';charset=utf8mb4', $config['username'], $config['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $restored = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (count($restored) !== count($tables)) throw new RuntimeException('Table count differs from dump. Check staging before using.');
    $counts = [];
    foreach (['users', 'products', 'customers', 'certificate_requests', 'quality_certificates', 'hard_copy_supplements', 'print_logs', 'migrations'] as $table) {
        if (in_array($table, $restored, true)) $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    }
    $result = ['database' => $target, 'tables' => count($restored), 'row_counts' => $counts,
        'backup' => $backup, 'backup_sha256' => hash_file('sha256', $backup),
        'expanded_sql' => $sql, 'source_sha256' => hash_file('sha256', $source),
        'original_database_untouched' => true, 'application_env_unchanged' => true];
    file_put_contents($directory . '/restore-result.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
