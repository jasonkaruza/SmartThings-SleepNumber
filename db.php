<?php
global $DB;

class Database
{
    private $pdo;

    public function __construct($host, $dbname, $username, $password)
    {
        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    /**
     * Insert a record into the specified table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @return int|bool Last inserted ID or false on failure
     */
    /**
     * Insert a record into the specified table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @return int|bool Last inserted ID or false on failure
     */
    public function insert($table, array $data)
    {
        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));

            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Insert failed: " . $e->getMessage());
        }
    }


    /**
     * Insert or update a record in the specified table (upsert)
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @return int|bool Last inserted ID or true if updated, false on failure
     */
    /**
     * Insert or update a record in the specified table (upsert)
     * 
     * @param string $table Table name
     * @param array $insertData Associative array of column => value pairs for insert
     * @param array|null $updateData Associative array of column => value pairs for update (optional)
     * @return int|bool Last inserted ID or true if updated, false on failure
     */
    public function upsert($table, array $insertData, array $updateData = null)
    {
        try {
            $columns = array_keys($insertData);
            $placeholders = array_fill(0, count($columns), '?');
            $updateData = $updateData ?? $insertData;
            $updateParts = array_map(function ($column) {
                return "$column = ?";
            }, array_keys($updateData));

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders),
                implode(', ', $updateParts)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($insertData), array_values($updateData)));

            // If a row was inserted, return lastInsertId; if updated, return true
            return $this->pdo->lastInsertId() ?: true;
        } catch (PDOException $e) {
            throw new Exception("Upsert failed: " . $e->getMessage());
        }
    }

    /**
     * Update records in the specified table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs to update
     * @param array $where Associative array of column => value pairs for WHERE clause
     * @return int Number of affected rows
     */
    public function update($table, array $data, array $where)
    {
        try {
            $setParts = array_map(function ($column) {
                return "$column = ?";
            }, array_keys($data));

            $whereParts = array_map(function ($column) {
                return "$column = ?";
            }, array_keys($where));

            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s",
                $table,
                implode(', ', $setParts),
                implode(' AND ', $whereParts)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge(
                array_values($data),
                array_values($where)
            ));

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Update failed: " . $e->getMessage());
        }
    }

    /**
     * Delete records from the specified table
     * 
     * @param string $table Table name
     * @param array $where Associative array of column => value pairs for WHERE clause
     * @return int Number of affected rows
     */
    public function delete($table, array $where)
    {
        try {
            $whereParts = array_map(function ($column) {
                return "$column = ?";
            }, array_keys($where));

            $sql = sprintf(
                "DELETE FROM %s WHERE %s",
                $table,
                implode(' AND ', $whereParts)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($where));

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Delete failed: " . $e->getMessage());
        }
    }

    /**
     * Select records from the specified table
     * 
     * @param string $table Table name
     * @param array $columns Columns to select
     * @param array|null $where Optional WHERE conditions
     * @param string|null $orderBy Optional ORDER BY clause
     * @param int|null $limit Optional LIMIT value
     * @param int|null $offset Optional OFFSET value
     * @return array Array of matching records
     */
    public function select(
        $table,
        array $columns = ['*'],
        array $where = null,
        $orderBy = null,
        $limit = null,
        $offset = null,
        $sortOrder = null
    ) {
        try {
            $sql = sprintf("SELECT %s FROM %s", implode(', ', $columns), $table);
            $values = [];

            if ($where) {
                $whereParts = array_map(function ($column) {
                    return "$column = ?";
                }, array_keys($where));
                $sql .= " WHERE " . implode(' AND ', $whereParts);
                $values = array_values($where);
            }

            if ($orderBy) {
                $sql .= " ORDER BY " . $orderBy;
                if ($sortOrder) {
                    $sql .= " " . $sortOrder;
                }
            }

            if ($limit) {
                $sql .= " LIMIT " . (int)$limit;
                if ($offset) {
                    $sql .= " OFFSET " . (int)$offset;
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Select failed: " . $e->getMessage());
        }
    }

    public function getRowOrFieldById($table, $id, $specificField = null): mixed
    {
        $row = $this->select($table, where: [ID => $id]);
        if ($row) {
            if ($specificField && array_key_exists($specificField, $row)) {
                return $row[$specificField];
            }
            return $row;
        }
        return false;
    }

    public function getRowOrFieldByField($table, $field, $value, $specificField = null): mixed
    {
        $row = $this->select($table, where: [$field => $value], orderBy: ID, limit: 1, sortOrder: 'DESC');
        if ($row) {
            $row = $row[0];
            if ($specificField && array_key_exists($specificField, $row)) {
                return $row[$specificField];
            }
            return $row;
        }
        return false;
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    public function raw($sql)
    {
        try {

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Raw query $sql failed: " . $e->getMessage());
        }
    }
}
