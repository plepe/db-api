<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$api = new DBApi($db);
include 'src/example.php';

$param = $_REQUEST;

Header("Content-type: text/plain; charset=utf8");

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    print "[\n";
    foreach ($table->load($param) as $i => $elem) {
      print $i === 0 ? '' : ",\n";
      print json_readable_encode($elem);
    }
    print "\n]\n";
    break;
  case 'POST':
    $data = json_decode(file_get_contents('php://input'),true);

    $result = $table->save($data);

    print json_readable_encode($result);

    break;
}
