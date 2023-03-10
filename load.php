<?php

// pristupove udaje
$server = 'localhost';
$user = 'root';
$password = '';
$database = 'test';

// konfigurace
$locale = array (
  0 => 'czech',
  1 => 'utf8',
  2 => 'cz_CZ',
);

$connectError = DB::connect($server, $user, $password, $database,NULL);
if (null !== $connectError) {
    $message = 'Připojení k databázi se nezdařilo. Důvodem je pravděpodobně výpadek serveru nebo chybné přístupové údaje. Zkontrolujte přístupové údaje v souboru <em>config.php</em>.';
}


// classes
abstract class DB
{


public static $logger;



private static $mysqli;


    public static function connect($server, $user, $password, $database, $port, $charset = 'utf8')
    {
        $mysqli = @mysqli_connect($server, $user, $password, $database, $port);
        $connectError = mysqli_connect_error();

        if (null === $connectError) {
            if (null !== $charset) {
                mysqli_set_charset($mysqli, $charset);
            }

            self::$mysqli = $mysqli;
        }

        return $connectError;
    }


public static function query($sql, $expect_error = false, $log = true)
{
if (null !== self::$logger && $log) {
call_user_func(self::$logger, $sql);
}

$q = mysqli_query(self::$mysqli, $sql);
if ($q === false && !$expect_error) {

trigger_error('SQL error: ' . mysqli_error(self::$mysqli) . ' --- SQL code: ' . $sql, E_USER_WARNING);
}

return $q;
}







public static function query_row($sql, $expect_error = false)
{
$q = self::query($sql, $expect_error);
if (false === $q) return false;
$row = self::row($q);
self::free($q);

return $row;
}







public static function count($table, $where = '1')
{
$q = self::query('SELECT COUNT(*) FROM `' . $table . '` WHERE ' . $where);
if (is_resource($q)) {
$count = intval(mysqli_result($q, 0));
mysqli_free_result($q);

return $count;
}

return 0;
}





public static function error()
{
return mysqli_error(self::$mysqli);
}






public static function row($query)
{
return mysqli_fetch_assoc($query);
}






public static function rown($query)
{
return mysqli_fetch_row($query);
}







public static function result(\mysqli_result $result, $column = 0)
{
    $row = $result->fetch_row();

    if (null !== $row && isset($row[$column])) {
        return $row[$column];
    } else {
        return null;
    }
}







public static function free($query)
{
return mysqli_free_result($query);
}






public static function size($query)
{
return mysqli_num_rows($query);
}





public static function insertID()
{
return mysqli_insert_id(self::$mysqli);
}





public static function affectedRows()
{
return mysqli_affected_rows(self::$mysqli);
}







public static function esc($value, $handleArray = false)
{
if (null === $value) return null;
if ($handleArray && is_array($value)) {
foreach ($value as &$item) {
$item = self::esc($item);
}

return $value;
}
if (is_string($value)) return mysqli_real_escape_string(self::$mysqli, $value);
if (is_numeric($value)) return (0 + $value);
return mysqli_real_escape_string(self::$mysqli, @strval($value));
}







public static function val($value, $handleArray = false)
{
$value = self::esc($value, $handleArray);
if ($handleArray && is_array($value)) {
$out = '';
$itemCounter = 0;
foreach ($value as $item) {
if (0 !== $itemCounter) $out .= ',';
$out .= self::val($item);
++$itemCounter;
}

return $out;
} elseif (is_string($value)) {
return '\'' . $value . '\'';
} elseif (null === $value) {
return 'NULL';
}
return $value;
}






public static function arr($arr)
{
$sql = '';
for ($i = 0; isset($arr[$i]); ++$i) {
if (0 !== $i) $sql .= ',';
$sql .= self::val($arr[$i]);
}

return $sql;
}








public static function insert($table, $data, $get_insert_id = false)
{
if (empty($data)) return false;
$counter = 0;
$col_list = '';
$val_list = '';
foreach ($data as $col => $val) {
if (0 !== $counter) {
$col_list .= ',';
$val_list .= ',';
}
$col_list .= "`{$col}`";
$val_list .= self::val($val);
++$counter;
}
$q = self::query("INSERT INTO `{$table}` ({$col_list}) VALUES({$val_list})");
if (false !== $q && $get_insert_id) return self::insertID();
return $q;
}









public static function update($table, $cond, $data, $limit = 1)
{
if (empty($data)) return false;
$counter = 0;
$set_list = '';
foreach ($data as $col => $val) {
if (0 !== $counter) $set_list .= ',';
$set_list .= "`{$col}`=" . self::val($val);
++$counter;
}

return self::query("UPDATE `{$table}` SET {$set_list} WHERE {$cond}" . ((null === $limit) ? '' : " LIMIT {$limit}"));
}






public static function datetime($timestamp)
{
return date('Y-m-d H:i:s', $timestamp);
}






public static function date($timestamp)
{
return date('Y-m-d', $timestamp);
}

}


if (!function_exists('memory_get_usage')) {





function memory_get_usage()
{
return 1048576;
}
}