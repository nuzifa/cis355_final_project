<?php
/* ---------------------------------------------------------------------------
 * filename    : database.php
 * author      : George Corser, gcorser@gmail.com
 * description : This class enables PHP to connect to MySQL using 
 *               PDO (PHP Data Objects). See: https://phpdelusions.net/pdo#why
 * important   : This file contains passwords!
 *               Do not put real version of this file in public github repo!
 *               Create sibling subdirectory and at top of all PHP files:
 *               require '../database/database.php';
 * ---------------------------------------------------------------------------
 */
// The Database class enables PHP to connect-to/disconnect-from MySQL database
class Database {
    private static $dbName = 'cis355_final'; // Ensure this matches your database name
    private static $dbHost = 'localhost';
    private static $dbUsername = 'root';
    private static $dbUserPassword = '';
    private static $connection = null;

    public static function connect() {
        if (null == self::$connection) {      
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$dbHost . ";dbname=" . self::$dbName, 
                    self::$dbUsername, 
                    self::$dbUserPassword
                );  
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) { 
                die("Database connection failed: " . $e->getMessage()); 
            }
        } 	
        return self::$connection;
    } 

    public static function disconnect() {
        self::$connection = null;
    } 
}
?>