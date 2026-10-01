<?php
/**
 * Database configuration file for CyberTrendHub E-commerce
 * Auto-detects driver: SQLite (local dev) or PostgreSQL (Replit).
 */

// Set the base URL dynamically based on server environment
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host_url = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . $host_url . '/');
} else {
    define('BASE_URL', 'http://localhost:5000/');
}

// PDO connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

/**
 * SQLite's PDO driver does not report row counts for SELECT statements
 * (rowCount() always returns 0). This statement class computes the count
 * with a COUNT(*) subquery so the application's existing
 * "$statement->rowCount()" checks keep working.
 */
class CountablePdoStatement extends PDOStatement
{
    private static $db;
    private $execParams;
    private $cachedCount;
    private $countComputed = false;

    public static function setDb($db)
    {
        self::$db = $db;
    }

    public function execute(?array $params = null): bool
    {
        $this->execParams = $params;
        $this->countComputed = false;
        $this->cachedCount = null;
        return parent::execute($params);
    }

    public function rowCount(): int
    {
        $sql = trim((string)$this->queryString);
        if ($this->countComputed || stripos($sql, 'SELECT') !== 0) {
            return parent::rowCount();
        }
        if ($this->cachedCount === null) {
            try {
                $sql = rtrim($sql, "; \t\n\r");
                $st = self::$db->prepare('SELECT COUNT(*) FROM (' . $sql . ') AS __cnt');
                $st->execute($this->execParams);
                $this->cachedCount = (int)$st->fetchColumn();
            } catch (PDOException $e) {
                $this->cachedCount = parent::rowCount();
            }
        }
        $this->countComputed = true;
        return $this->cachedCount;
    }
}

if (getenv('PGDATABASE') || getenv('PGHOST')) {
    // ---- PostgreSQL (Replit) ----
    $host = getenv('PGHOST') ?: 'localhost';
    $port = getenv('PGPORT') ?: '5432';
    $db   = getenv('PGDATABASE') ?: 'ecommerceweb';
    $user = getenv('PGUSER') ?: 'postgres';
    $pass = getenv('PGPASSWORD') ?: '';
    $dsn  = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, $options);
} else {
    // ---- SQLite (local development) ----
    $dbFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cybertrendhub.sqlite';
    $dsn = 'sqlite:' . $dbFile;
    $pdo = new PDO($dsn, null, null, $options);
    CountablePdoStatement::setDb($pdo);
    $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['CountablePdoStatement']);
    $pdo->exec('PRAGMA foreign_keys = ON');
}

// Set variables for files that use them directly
$username = $user ?? '';
$password = $pass ?? '';

// Session configuration
if (session_id() === '') {
    session_start();
}
?>
