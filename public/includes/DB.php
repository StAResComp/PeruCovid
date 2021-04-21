<?php

declare(strict_types=1);

namespace PERUCOVID;

class DB {

    /****** includes/DB.php
     * NAME
     * DB.php
     * SYNOPSIS
     * Class for communicating with relational database.
     * AUTHOR
     * Swithun Crowe
     * CREATION DATE
     * 20210121
     ******
     */
    
    private $pdo = null;
    private $errorMessage = "";
    private $errorCode = 0;
    private $fetch = \PDO::FETCH_OBJ;

    public static $instance = null;

    /****** DB.php/__construct
     * NAME
     * __construct
     * SYNOPSIS
     * Creates connection to database using defined DSN and credentials.
     * ARGUMENTS
     *   * transaction - boolean - default true to start transaction
     ******
     */
    public function __construct(bool $transaction=true) { //{{{
        include 'DB_config.php';
        
				$this->pdo = new \PDO($PC_DSN, $PC_DB_USER, $PC_DB_PASS);
				$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // start transaction, which will need to be committed
        if ($transaction) {
            $this->pdo->beginTransaction();
        }
    }
    //}}}
    
    /****** DB.php/setFetch
     * NAME
     * setFetch
     * SYNOPSIS
     * Set the fetch method for the database queries
     * ARGUMENTS
     *   * fetch - integer - fetch constant
     ******
     */
    public function setFetch(int $fetch) { //{{{
        $this->fetch = $fetch;
    }
    //}}}

    /****** DB.php/__call
     * NAME
     * __call
     * SYNOPSIS
     * This method is called when a non-existant method is called on the DB object.
     * The method name is taken to be the name of a stored procedure and the arguments passed to the method are parameters for the procedure.
     * ARGUMENTS
     * name - string - the name of the stored procedure
     * args - array - array of arguments to pass as parameters to the procedure
     * RETURN VALUE
     * Array of result stdClass objects
     ******
     */
    public function __call($name, $args) : array { //{{{
				$results = [];

				try {
						// generate SQL string, with ? for each argument
						$sql = sprintf("SELECT * FROM %s(%s)",
													 $name,
													 substr(str_pad("", count($args) * 2, "?,"), 0, -1));

						// prepare statement
						$stmt = $this->pdo->prepare($sql);
            $stmt->setFetchMode($this->fetch);

						// bind procedure parameters
						foreach ($args as $i => $value) {
								$stmt->bindValue($i + 1, $value);
						}

						// execute procedure
						$stmt->execute();

            $results = $stmt->fetchAll();
            
            // add columns if fetching numbered array
            if (is_array($results) && \PDO::FETCH_NUM == $this->fetch) {
                $cols = [];
                for ($i = 0, $l = $stmt->columnCount(); $i < $l; ++ $i) {
                    $meta = $stmt->getColumnMeta($i);
                    $cols[] = $meta['name'];
                }
                
                array_unshift($results, $cols);
            }
				}
				catch (\PDOException $e) {
						$this->errorMessage = $e->getMessage();
						$this->errorCode = $e->getCode();
				}

				return $results;
    }
    //}}}

    /****** DB.php/exec
     * NAME
     * exec
     * SYNOPSIS
     * Call to execute abritrary SQL statements
     * ARGUMENTS
     * sql - string - SQL with placeholders for arguments
     * args - array - array of arguments to pass as parameters to the statement
     * RETURN VALUE
     * Array of result stdClass objects
     ******
     */
    public function exec(string $sql, array $args) : array { //{{{
				$results = [];

				try {
						// prepare statement
						$stmt = $this->pdo->prepare($sql);
            $stmt->setFetchMode($this->fetch);

						// bind procedure parameters
						foreach ($args as $i => $value) {
								$stmt->bindValue($i + 1, $value);
						}

						// execute procedure
						$stmt->execute();

            $results = $stmt->fetchAll();
            
            // add columns if fetching numbered array
            if (is_array($results) && \PDO::FETCH_NUM == $this->fetch) {
                $cols = [];
                for ($i = 0, $l = $stmt->columnCount(); $i < $l; ++ $i) {
                    $meta = $stmt->getColumnMeta($i);
                    $cols[] = $meta['name'];
                }
                
                array_unshift($results, $cols);
            }
				}
				catch (\PDOException $e) {
						$this->errorMessage = $e->getMessage();
						$this->errorCode = $e->getCode();
				}

				return $results;
    }
    //}}}

    /****** DB.php/getError
     * NAME
     * getError
     * SYNOPSIS
     * Get the error code and message from a failed database call.
     * RETURN VALUE
     * An array containing the error code and error message
     ******
     */
    public function getError() { //{{{
				return array($this->errorCode,
										 $this->errorMessage);
    }
    //}}}
    
    /****** DB.php/commit
     * NAME
     * commit
     * SYNOPSIS
     * Commit the transaction started in constructor.
     * RETURN VALUE
     * None
     ******
     */
    public function commit() { //{{{
        $this->pdo->commit();
    }
    //}}}

    /****** DB.php/beginTransaction
     * NAME
     * beginTransaction
     * SYNOPSIS
     * Start a new transaction
     * RETURN VALUE
     * None
     ******
     */
    public function beginTransaction() { //{{{
        $this->pdo->beginTransaction();
    }
    //}}}

    /****** DB.php/rollback
     * NAME
     * rollback
     * SYNOPSIS
     * Rollback transaction
     * RETURN VALUE
     * None
     ******
     */
    public function rollback() { //{{{
        $this->pdo->rollback();
    }
    //}}}

    /****** DB.php/__destruct
     * NAME
     * __descruct
     * SYNOPSIS
     * Rollback any transaction on destruction.
     * RETURN VALUE
     * None
     ******
     */
    public function __destruct() { //{{{
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
    //}}}
}

?>
