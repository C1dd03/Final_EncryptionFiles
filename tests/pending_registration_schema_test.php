<?php
/**
 * Regression checks for pending storage initialization without a database.
 * Run: php tests/pending_registration_schema_test.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../models/User.php';

final class PendingSchemaStatement extends PDOStatement
{
    public array $executions = [];

    public function __construct(private mixed $value = false)
    {
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->value;
    }

    public function execute(?array $params = null): bool
    {
        $this->executions[] = $params;
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        $value = $this->value;
        $this->value = false;
        return $value;
    }
}

final class PendingSchemaConnection extends PDO
{
    public const PREFERRED = '`pending_encryption_system`.`pending_registrations`';
    public const LEGACY = '`pending_registrations`';

    public array $calls = [];
    public ?PDOException $databaseError = null;
    public ?PDOException $tableError = null;
    public ?PDOException $preferredReadError = null;
    public ?PDOException $legacyReadError = null;
    public ?PDOException $copyError = null;
    public bool $legacyExists = true;
    public array $preparedRows = [];
    public array $preparedStatements = [];

    // Intentionally do not invoke PDO's constructor or open a connection.
    public function __construct()
    {
    }

    public function exec(string $statement): int|false
    {
        $sql = $this->record('exec', $statement);
        if (str_starts_with($sql, 'CREATE DATABASE IF NOT EXISTS ')) {
            $error = $this->databaseError;
        } elseif (str_starts_with($sql, 'CREATE TABLE IF NOT EXISTS ' . self::PREFERRED . ' (')) {
            $error = $this->tableError;
        } elseif (str_starts_with($sql, 'INSERT IGNORE INTO ' . self::PREFERRED . ' ')) {
            $error = $this->copyError;
        } else {
            throw new LogicException('Unexpected mutation: ' . $sql);
        }

        if ($error !== null) {
            throw $error;
        }
        return 0;
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $sql = $this->record('query', $query);
        if ($sql === 'SELECT user_id FROM ' . self::PREFERRED . ' LIMIT 0') {
            $error = $this->preferredReadError;
        } elseif ($sql === 'SELECT user_id FROM ' . self::LEGACY . ' LIMIT 0') {
            $error = $this->legacyReadError;
        } elseif ($sql === "SHOW TABLES LIKE 'pending_registrations'") {
            return new PendingSchemaStatement($this->legacyExists ? 'pending_registrations' : false);
        } else {
            throw new LogicException('Unexpected query: ' . $sql);
        }

        if ($error !== null) {
            throw $error;
        }
        return new PendingSchemaStatement();
    }

    public function countCalls(string $prefix): int
    {
        return count(array_filter($this->calls, static fn(array $call): bool => str_starts_with($call[1], $prefix)));
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $sql = $this->record('prepare', $query);
        if ($this->preparedRows === []) {
            throw new LogicException('Unexpected prepared query: ' . $sql);
        }
        $statement = new PendingSchemaStatement(array_shift($this->preparedRows));
        $this->preparedStatements[] = $statement;
        return $statement;
    }

    private function record(string $operation, string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        $this->calls[] = [$operation, $sql];
        return $sql;
    }
}

function schemaAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function schemaError(int $mysqlCode, string $message): PDOException
{
    $error = new PDOException($message);
    $error->errorInfo = [$mysqlCode === 1146 ? '42S02' : 'HY000', $mysqlCode, $message];
    return $error;
}

function schemaUser(PendingSchemaConnection $connection): User
{
    // Neither constructor may run: Database opens MySQL; User performs DDL.
    $databaseReflection = new ReflectionClass(Database::class);
    $database = $databaseReflection->newInstanceWithoutConstructor();
    $databaseReflection->getProperty('conn')->setValue($database, $connection);
    $databaseReflection->getProperty('instance')->setValue(null, $database);

    $userReflection = new ReflectionClass(User::class);
    $user = $userReflection->newInstanceWithoutConstructor();
    $userReflection->getProperty('conn')->setValue($user, $connection);
    $userReflection->getProperty('pendingTable')->setValue($user, PendingSchemaConnection::PREFERRED);
    return $user;
}

function schemaSelectedTable(User $user): string
{
    return (new ReflectionProperty(User::class, 'pendingTable'))->getValue($user);
}

$tests = [
    'preferred storage initializes and copies legacy records' => static function (): void {
        $connection = new PendingSchemaConnection();
        $user = schemaUser($connection);
        $user->ensurePendingRegistrationsSchema();

        schemaAssert(schemaSelectedTable($user) === PendingSchemaConnection::PREFERRED, 'Preferred table was not selected.');
        schemaAssert($connection->countCalls('CREATE DATABASE ') === 1, 'Database initialization was skipped.');
        schemaAssert($connection->countCalls('CREATE TABLE IF NOT EXISTS ' . PendingSchemaConnection::PREFERRED) === 1, 'Preferred table initialization was skipped.');
        schemaAssert($connection->countCalls('INSERT IGNORE INTO ' . PendingSchemaConnection::PREFERRED) === 1, 'Legacy records were not copied to preferred storage.');
        schemaAssert($connection->countCalls('SELECT user_id FROM ' . PendingSchemaConnection::LEGACY) === 0, 'Healthy preferred setup unexpectedly probed fallback storage.');
    },
    'orphaned preferred tablespace uses legacy storage on repeated initialization' => static function (): void {
        $connection = new PendingSchemaConnection();
        $connection->tableError = schemaError(1813, 'Tablespace exists.');
        $connection->preferredReadError = schemaError(1146, 'Preferred table does not exist.');
        $user = schemaUser($connection);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $user->ensurePendingRegistrationsSchema();
            schemaAssert(schemaSelectedTable($user) === PendingSchemaConnection::LEGACY, 'Readable legacy storage was not retained.');
        }

        schemaAssert($connection->countCalls('CREATE TABLE IF NOT EXISTS ' . PendingSchemaConnection::PREFERRED) === 2, 'Repeated setup did not retry the preferred target.');
        schemaAssert($connection->countCalls('SELECT user_id FROM ' . PendingSchemaConnection::LEGACY) === 2, 'Fallback readability was not checked.');
        schemaAssert($connection->countCalls('CREATE TABLE IF NOT EXISTS ' . PendingSchemaConnection::LEGACY) === 0, 'Repeated setup created the fallback table.');
        schemaAssert($connection->countCalls('INSERT IGNORE ') === 0, 'Fallback attempted a legacy self-copy or copied to unavailable storage.');
    },
    'denied database creation preserves readable preferred storage' => static function (): void {
        $connection = new PendingSchemaConnection();
        $connection->databaseError = schemaError(1044, 'CREATE DATABASE access denied.');
        $connection->legacyExists = false;
        $user = schemaUser($connection);
        $user->ensurePendingRegistrationsSchema();

        schemaAssert(schemaSelectedTable($user) === PendingSchemaConnection::PREFERRED, 'A DDL permission error displaced readable preferred storage.');
        schemaAssert($connection->countCalls('SELECT user_id FROM ' . PendingSchemaConnection::PREFERRED) === 1, 'Preferred readability was not checked after denied DDL.');
        schemaAssert($connection->countCalls('SELECT user_id FROM ' . PendingSchemaConnection::LEGACY) === 0, 'Readable preferred storage incorrectly triggered fallback.');
        schemaAssert($connection->countCalls('CREATE TABLE ') === 0, 'Table DDL unexpectedly continued after database DDL failed.');
    },
    'both unavailable tables fail with the original provisioning exception' => static function (): void {
        $connection = new PendingSchemaConnection();
        $connection->tableError = schemaError(1813, 'Tablespace exists.');
        $connection->preferredReadError = schemaError(1146, 'Preferred table does not exist.');
        $connection->legacyReadError = schemaError(1146, 'Legacy table does not exist.');
        $user = schemaUser($connection);
        $caught = null;
        try {
            $user->ensurePendingRegistrationsSchema();
        } catch (RuntimeException $error) {
            $caught = $error;
        }

        schemaAssert($caught !== null, 'Unavailable storage was silently accepted.');
        schemaAssert($caught->getPrevious() === $connection->tableError, 'Original provisioning failure was not preserved as the cause.');
        schemaAssert(str_contains($caught->getMessage(), 'pending_encryption_system.sql'), 'Initialization error omitted the setup file.');
        schemaAssert($connection->countCalls('INSERT IGNORE ') === 0, 'Migration ran without readable storage.');
    },
    'legacy migration failure leaves preferred storage selected' => static function (): void {
        $connection = new PendingSchemaConnection();
        $connection->copyError = schemaError(1054, 'Legacy table has an incompatible column.');
        $user = schemaUser($connection);
        $user->ensurePendingRegistrationsSchema();

        schemaAssert(schemaSelectedTable($user) === PendingSchemaConnection::PREFERRED, 'Migration failure displaced initialized preferred storage.');
        schemaAssert($connection->countCalls('INSERT IGNORE INTO ' . PendingSchemaConnection::PREFERRED) === 1, 'Legacy migration was not attempted.');
        schemaAssert($connection->countCalls('SELECT user_id FROM ' . PendingSchemaConnection::LEGACY) === 0, 'Migration failure incorrectly triggered fallback.');
    },
    'admin forms share the latest standard account ID' => static function (): void {
        $connection = new PendingSchemaConnection();
        $year = date('Y');
        $connection->preparedRows = [['id_number' => $year . '-0074']];
        $user = schemaUser($connection);
        schemaAssert($user->getNextIdsForForms() === ['admin_id' => $year . '-0075', 'standard_id' => $year . '-0075'], 'Roles received different ID sequences.');
        schemaAssert(count($connection->preparedStatements) === 1, 'Form IDs were generated separately.');
        schemaAssert(User::isValidAdminIdFormat($year . '-0075'), 'Standard ID rejected for an admin.');
        schemaAssert(!User::isValidAdminIdFormat('ADMIN-0001'), 'Legacy prefix accepted for a new admin.');
    },
    'current-year IDs increment without consulting other years' => static function (): void {
        $connection = new PendingSchemaConnection();
        $year = date('Y');
        $connection->preparedRows = [['id_number' => $year . '-0074']];
        $user = schemaUser($connection);

        schemaAssert($user->generateIdNumber() === $year . '-0075', 'The current-year ID was not incremented.');
        schemaAssert(count($connection->preparedStatements) === 1, 'ID generation queried other years despite a current-year result.');
        schemaAssert($connection->preparedStatements[0]->executions === [[':yearPrefix' => $year . '-%']], 'The ID query did not filter the current year.');
    },
];

$failures = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        fwrite(STDOUT, "PASS: {$name}\n");
    } catch (Throwable $error) {
        $failures++;
        fwrite(STDERR, "FAIL: {$name}: {$error->getMessage()}\n");
    }
}
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, null);
fwrite(STDOUT, count($tests) . ' tests, ' . $failures . " failures.\n");
exit($failures === 0 ? 0 : 1);
