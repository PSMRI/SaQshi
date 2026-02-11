<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$logFile = 'C:/logs/php_errors.log';

$lastSize = isset($_GET['size']) ? (int)$_GET['size'] : 0;

$size = filesize($logFile);
if ($size < $lastSize) {
    $lastSize = 0;
}

$fp = fopen($logFile, 'r');
fseek($fp, $lastSize);
echo fread($fp, $size - $lastSize);
fclose($fp);
