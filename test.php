<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php require __DIR__ . '/vendor/autoload.php'; ?>
<?php call_hooks('init'); ?>
<?php
// Initialize database
$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

include 'test/structure.php';

$viewDef = null;

$view = new DBViewJSON($api, $viewDef);
$view->set_query(array(
  'table' => 'test2',
));
print $view->show();

$view = new DBViewTwig($api, "<div>\n<b>{{ entry.id }}</b><br/>\n{{ entry.commentsCount }}\n</div>\n");
$view->set_query(array(
  'table' => 'test2',
));
print $view->show();
