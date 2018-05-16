<?php include "conf.php"; /* load a local configuration */ ?>
<?php require __DIR__ . '/vendor/autoload.php'; ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

include 'test/structure.php';

$api->handle_http_response();

