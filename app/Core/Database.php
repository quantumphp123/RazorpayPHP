<?php

namespace App\Core;

use Exception;
use PDO;
use PDOException;

class Database
{
    protected $connection;
    protected $statement;
    protected $inTransaction = false;

    public function __construct(array $config)
    {
        try {
            $dsn = "mysql:" . http_build_query($config, '', ';');
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }
    public function beginTransaction()
    {
        if (!$this->inTransaction) {
            $this->connection->beginTransaction();
            $this->inTransaction = true;
        }
    }

    public function commit()
    {
        if ($this->inTransaction) {
            $this->connection->commit();
            $this->inTransaction = false;
        }
    }

    public function rollBack()
    {
        if ($this->inTransaction) {
            $this->connection->rollBack();
            $this->inTransaction = false;
        }
    }

    public function query($query, $params = [])
    {
        try {
            $this->statement = $this->connection->prepare($query);
            $this->statement->execute($params);
            return $this;
        } catch (PDOException $e) {
            if ($this->inTransaction) {
                $this->rollBack();
            }
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    public function find()
    {
        return $this->statement->fetch();
    }

    public function findOrFail()
    {
        $result = $this->find();

        if (!$result) {
            abort();
        }

        return $result;
    }

    public function get()
    {
        return $this->statement->fetchAll();
    }

    public function getLastInsertId()
    {
        return $this->connection->lastInsertId();
    }

}
