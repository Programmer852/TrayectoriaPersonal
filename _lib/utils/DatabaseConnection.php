<?php

class DatabaseConnection
{
    private $pdo;
    private $mysqli;
    private $dbType;
    private $connected = false;

    public function __construct($dbType = 'PDO')
    {
        $this->dbType = strtoupper($dbType);
        if (!in_array($this->dbType, ['PDO', 'MYSQLI'])) {
            throw new InvalidArgumentException("Invalid database type. Use 'PDO' or 'MYSQLI'.");
        }
    }

    public function connect($host, $dbname, $username, $password, $port = 3306)
    {
        try {
            if ($this->dbType === 'PDO') {
                $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
                $this->pdo = new PDO($dsn, $username, $password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connected = true;
            } elseif ($this->dbType === 'MYSQLI') {
                $this->mysqli = new mysqli($host, $username, $password, $dbname, $port);
                if ($this->mysqli->connect_error) {
                    throw new Exception("MySQLi connection error: " . $this->mysqli->connect_error);
                }
                $this->connected = true;
            }
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    public function disconnect()
    {
        if ($this->dbType === 'PDO' && $this->pdo) {
            $this->pdo = null;
        } elseif ($this->dbType === 'MYSQLI' && $this->mysqli) {
            $this->mysqli->close();
        }
        $this->connected = false;
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function executeQuery($query, $params = [])
    {
        try {
            if ($this->dbType === 'PDO') {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($this->dbType === 'MYSQLI') {
                if (!empty($params)) {
                    throw new Exception("MySQLi does not support prepared statements directly in this method.");
                }
                $result = $this->mysqli->query($query);
                if (!$result) {
                    throw new Exception("Query error: " . $this->mysqli->error);
                }
                return $result->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    public function executeQueryPro($query, $params = [])
    {
        try {
            if ($this->dbType === 'PDO') {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                
                // Verificar si es una consulta de tipo SELECT
                if (stripos(trim($query), 'SELECT') === 0) {
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                // Retorna el número de filas afectadas para operaciones no-query
                return $stmt->rowCount();
            } elseif ($this->dbType === 'MYSQLI') {
                if (!empty($params)) {
                    throw new Exception("MySQLi no admite parámetros dinámicos en esta función.");
                }

                $result = $this->mysqli->query($query);
                if (!$result) {
                    throw new Exception("Query error: " . $this->mysqli->error);
                }

                // Verificar si es una consulta de tipo SELECT
                if (stripos(trim($query), 'SELECT') === 0) {
                    return $result->fetch_all(MYSQLI_ASSOC);
                }
                
                // Retorna el número de filas afectadas para operaciones no-query
                return $this->mysqli->affected_rows;
            }
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }


    private function handleError($message)
    {
        error_log("Database error: $message");
        throw new Exception("A database error occurred. Check the logs for details.");
    }
}

?>
