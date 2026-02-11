<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$log = 'C:/logs/php_errors.log';

echo "Exists: ";
var_dump(file_exists($log));

echo "<br>Readable: ";
var_dump(is_readable($log));

echo "<br>Filesize: ";
var_dump(@filesize($log));

echo "<br>Open: ";
$fp = @fopen($log, 'r');
var_dump($fp);

if ($fp) {
    echo "<br>Read OK";
    fclose($fp);
}
