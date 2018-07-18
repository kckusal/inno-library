<?php

class DatabaseConnection {
    private $host = '127.0.0.1';	// Alternative: $host = 'localhost';
    private $user = 'root';
    private $pass = 'root';
    private $dbname = 'project-library';

    private $dbh;   // database handler
    private $error;
    private $stmt;  // variable to hold query statement

    public function __construct() {
        // Set DSN, Database Source Name
        $dsn = 'mysql:host='. $this->host . ';dbname='. $this->dbname;
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        // Create new PDO
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        }
        catch (PDOException $e) {
            $this->error = $e->getMessage();
        }

    }

    public function query($query) {
        $this->stmt = $this->dbh->prepare($query);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    // returns an array of the result set rows
    public function resultset() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // returns a single record from the database table
    public function nextResult() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    // returns the number of rows affected in the last query operation
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // -----------------Transaction methods-------------------

    // begin a transaction
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    // end a transaction and roll back changes
    public function endTransaction() {
        return $this->dbh->commit();
    }

    // cancel a transaction and roll back changes
    public function cancelTransaction() {
        return $this->dbh->rollBack();
    }

    // dumps the information that was contained in the Prepared Statement
    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }
    
    
    public function addLog($activity_info) {
        $user_name = $_SESSION['user-data']['name'];
        $user_type = ucwords($_SESSION['user-data']['user_type_name']);
        $this->query("INSERT INTO `logs` (`ID`, `Name`, `type`, `Activity`, `Date`, `time`) VALUES (NULL, :user_name, :user_type, :activity_info, CURRENT_DATE(), CURRENT_TIME())");
        $this->bind(':user_name', $user_name);
        $this->bind(':user_type', $user_type);
        $this->bind(':activity_info', $activity_info);
        $this->execute();
    }

}