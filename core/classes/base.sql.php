<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

/**
 * SQL Class interface, defines the needed functionality for the SQL Drivers
 *
 * @version     1.0
 * @since       1.0.0
 * @author      xLink
 */
interface SQLBase{

    public function __construct($config=array());
    public function connect($persistent, $debug, $logging);
    public function disconnect();
    public function selectDb($db);
    public function getColumns($table);

    public function getError();

    public function escape($string);
    public function freeResult();

    public function prepare();
    public function query($query, $log=false);
    public function getInfo($table, $clause=null, $log=false);
    public function getValue($table, $field, $clause=null, $log=false);
    public function getLine($query, $log=false);
    public function getTable($query, $log=false);

    public function insertRow($table, $array, $log=false);
    public function updateRow($table, $array, $clause, $log=false);
    public function deleteRow($table, $clause, $log=false);

}

?>