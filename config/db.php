<?php

/**
 * Database Connection Class
 * eCommerce Management System
 */

class Database
{
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'ecommerce_db';
    private $charset = 'utf8mb4';

    private $pdo;
    private $error;
    private static $instance = null;

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            $this->pdo->exec("SET time_zone = '+03:00'");
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError('Database Connection Failed: ' . $e->getMessage());

            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                die('Database connection failed. Please try again later.');
            } else {
                die('Database Connection Error: ' . $e->getMessage());
            }
        }
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Query Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }

    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert($table, $data)
    {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->pdo->prepare($sql);

            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError('Insert Error: ' . $e->getMessage() . ' | Table: ' . $table);
            throw new Exception('Insert operation failed: ' . $e->getMessage());
        }
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        try {
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setParts);

            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);

            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            foreach ($whereParams as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Update Error: ' . $e->getMessage() . ' | Table: ' . $table);
            throw new Exception('Update operation failed: ' . $e->getMessage());
        }
    }

    public function delete($table, $where, $whereParams = [])
    {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);

            foreach ($whereParams as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Delete Error: ' . $e->getMessage() . ' | Table: ' . $table);
            throw new Exception('Delete operation failed: ' . $e->getMessage());
        }
    }

    public function count($table, $where = '', $params = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        $result = $this->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }

    public function exists($table, $where, $params = [])
    {
        return $this->count($table, $where, $params) > 0;
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollback()
    {
        return $this->pdo->rollback();
    }

    public function callProcedure($procedureName, $params = [])
    {
        try {
            $placeholders = rtrim(str_repeat('?,', count($params)), ',');
            $sql = "CALL {$procedureName}({$placeholders})";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError("Procedure Error: {$e->getMessage()} | Procedure: {$procedureName}");
            throw new Exception("Stored procedure execution failed: {$e->getMessage()}");
        }
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function escapeLike($string)
    {
        return str_replace(['%', '_'], ['\%', '\_'], $string);
    }

    private function logError($message)
    {
        $logDir = dirname(__DIR__) . '/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . 'database_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function getStats()
    {
        try {
            return [
                'products' => $this->count('products', 'status = ?', ['active']),
                'orders' => $this->count('orders'),
                'customers' => $this->count('customers', 'is_active = ?', [1]),
                'categories' => $this->count('categories', 'is_active = ?', [1]),
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    public function isConnected()
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getError()
    {
        return $this->error;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper to get instance
function getDB()
{
    return Database::getInstance();
}

// Environment Config
if (!defined('ENVIRONMENT')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('ENVIRONMENT', (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) ? 'development' : 'production');
}

// Optional: Test database connection in development
if (ENVIRONMENT === 'development') {
    try {
        $db = getDB();
        if (!$db->isConnected()) {
            //die("PDO not initialized. Check db.php.");
        }
    } catch (Exception $e) {
        die("Database Initialization Error: " . $e->getMessage());
    }
}

