<?php

namespace Seven;

use PDO;

class Db
{
    private $db;
    private $primaryKeys = array();
    private $tables = array();
    private $created = false;
    private $updated = false;
    public $numQueries = 0;

    public function __construct()
    {
        global $config;
        $credentials = $config->database->credentials;
        $dataSource = $config->database->dataSource;
        $this->created = $config->database->timestampedFields->created;
        $this->updated = $config->database->timestampedFields->updated;
        try {
            $this->db = new PDO("mysql:host=$dataSource->hostname;dbname=$dataSource->database",
                $credentials->username,
                $credentials->password
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            $this->db->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS, true);
        } catch(PDOException $ex) {
            logError('database', $ex->getMessage(), __line__, __file__, true);
            if($config->environment != 'production') {
                echo $ex->getMessage();
            }
        }
        if($this->db) {
            $query = 'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :tableSchema';
            $result = $this->select($query, array(':tableSchema' => $dataSource->database));
            foreach($result as $tableColumn) {
                if($tableColumn['COLUMN_KEY'] === 'PRI') {
                  $this->primaryKeys[$tableColumn['TABLE_NAME']] = $tableColumn['COLUMN_NAME'];
                } else {
                  $this->tables[$tableColumn['TABLE_NAME']][] = $tableColumn['COLUMN_NAME'];
                }
            }
            $this->numQueries++;
        }
    }

    public function command($query)
    {
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $this->numQueries++;
        return true;
    }

    public function insert($query, $params)
    {
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $insertId = $this->db->lastInsertId();
        $this->numQueries++;
        return $insertId;
    }

    public function select($query, $params = array())
    {
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->numQueries++;
        return $results;
    }

    public function selectOne($query, $params = array())
    {
        return $this->select($query, $params)[0];
    }

    public function update($query, $params = array())
    {
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $rowCount = $stmt->rowCount();
        $this->numQueries++;
        return $rowCount;
    }

    public function delete($query, $params = array())
    {
        return $this->update($query, $params);
    }

    public function put($tableName, $object, $id = null)
    {
        $fieldsAndTokens = array();
        foreach($object as $key => $value) {
            if(in_array($key, $this->tables[$tableName])) {
                $fieldsAndTokens[$key] = ':' . $key;
            }
        }
        if(empty($fieldsAndTokens)) {
            return false;
        } else {
            if($id) {
                $updateParams = array();
                foreach($fieldsAndTokens as $field => $token) {
                    $updateParams[] = "$field = $token";
                }
                if($this->updated && in_array($this->updated, $this->tables[$tableName])) {
                    $updateParams[] = "$this->updated = NOW()";
                }
                $query = "UPDATE $tableName SET " . implode(', ', $updateParams) . " WHERE {$this->primaryKeys[$tableName]} = :{$this->primaryKeys[$tableName]}";
                $fieldsAndTokens[$this->primaryKeys[$tableName]] = ":{$this->primaryKeys[$tableName]}";
                $object[$this->primaryKeys[$tableName]] = $id;
            } else {
                if($this->created && in_array($this->created, $this->tables[$tableName])) {
                    $fieldsAndTokens[$this->created] = "NOW()";
                }
                if($this->updated && in_array($this->updated, $this->tables[$tableName])) {
                    $fieldsAndTokens[$this->updated] = "NOW()";
                }
                $query = "INSERT INTO $tableName (" . implode(', ', array_keys($fieldsAndTokens)) . ') VALUES (' . implode(', ', $fieldsAndTokens) . ')';
            }
            $bind = array();
            foreach($fieldsAndTokens as $field => $token) {
              if(isset($object[$field])) {
                  $bind[$token] = $object[$field];
                }
            }
            return ($id) ? $this->update($query, $bind) : $this->insert($query, $bind);
        }
    }

    public function getOneById($varOne, $varTwo = null)
    {
        if(is_object($varOne) && array_pop(explode('\\', get_parent_class($varOne))) == 'Argument') {
            return $this->selectOne("SELECT * FROM $varOne->tableName WHERE {$this->primaryKeys[$varOne->tableName]} = :{$this->primaryKeys[$varOne->tableName]}", array(":{$this->primaryKeys[$varOne->tableName]}" => $varOne));
        } else {
            return $this->selectOne("SELECT * FROM $varOne WHERE {$this->primaryKeys[$varOne]} = :{$this->primaryKeys[$varOne]}", array(":{$this->primaryKeys[$varOne]}" => $varTwo));
        }
    }

    public function deleteOneById($varOne, $varTwo = null)
    {
        if(is_object($varOne) && array_pop(explode('\\', get_parent_class($varOne))) == 'Argument') {
            return $this->update("DELETE FROM $varOne->tableName WHERE {$this->primaryKeys[$varOne->tableName]} = :{$this->primaryKeys[$varOne->tableName]}", array(":{$this->primaryKeys[$varOne->tableName]}" => $varOne));
        } else {
            return $this->update("DELETE FROM $varOne WHERE {$this->primaryKeys[$varOne]} = :{$this->primaryKeys[$varOne]} LIMIT 1", array(":{$this->primaryKeys[$varOne]}" => $varTwo));
        }
    }

    public function existsById($tableName, $id)
    {
        return $this->selectOne("SELECT EXISTS(SELECT * FROM {$tableName} WHERE {$this->primaryKeys[$tableName]} = :id LIMIT 1) AS e", [':id' => $id])['e'];
    }
}

