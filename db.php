<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php session_start(); ?>
<?php $auth = new Auth(); ?>
<?php
$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$rights = rights($auth);

Header("Content-type: text/plain; charset=utf8");

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    if (array_key_exists('id', $_REQUEST)) {
      $ids = explode(',', $_REQUEST['id']);
      print "{\n";
      foreach ($ids as $i => $id) {
        print $i === 0 ? '' : ",\n";

        if (preg_match("/^\d+$/", $id)) {
          print "\"{$id}\": ";
          print json_readable_encode(load_entry($id, $rights['anonym']));
        }
      }
      print "\n}";
    } else {
      print json_readable_encode(load_overview($_REQUEST, $rights['anonym']));
    }
    break;
  case 'POST':
    $data = json_decode(file_get_contents('php://input'),true);

    $result = update_data($data);

    print json_readable_encode($result);

    break;
}
