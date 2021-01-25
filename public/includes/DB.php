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

    public static $instance = null;

    /****** DB.php/__construct
     * NAME
     * __construct
     * SYNOPSIS
     * Creates connection to database using defined DSN and credentials.
     ******
     */
    public function __construct() { //{{{
        include 'DB_config.php';
        
				$this->pdo = new \PDO($PC_DSN, $PC_DB_USER, $PC_DB_PASS);
				$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // start transaction, which will need to be committed
        $this->pdo->beginTransaction();
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
            $stmt->setFetchMode(\PDO::FETCH_OBJ);

						// bind procedure parameters
						foreach ($args as $i => $value) {
								$stmt->bindValue($i + 1, $value);
						}

						// execute procedure
						$stmt->execute();

            $results = $stmt->fetchAll();
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
