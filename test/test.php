<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
function first_char ($value, $DBApi) {
  return substr($value, 0, 1);
}

$spec1 = array(
  'id' => 'test1',
  'fields' => array(
    'a' => array(
      'type' => 'int',
      'read' => true,
      'write' => false,
    ),
    'b' => array(
      'type' => 'string',
      'write' => true,
    ),
    'c' => array(
      'type' => 'string',
      'read' => false,
    ),
    'd' => array(
      'column' => 'd1',
      'read' => 'first_char',
    ),
  ),
  'id_field' => 'a',
);

$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->query("drop table if exists test1; create table test1 ( a int not null, b tinytext, c tinytext, d1 text, primary key(a)); insert into test1 values (1, 'foo', 'foo', 'foo'), (2, 'bar', 'bar', 'bar');");

global $table1;
$table1 = new DBApi($db, $spec1);

/**
 * @backupGlobals disabled
 */
class db_api_test extends PHPUnit_Framework_TestCase {
  public function testBuildLoadQuery1 () {
    global $table1;

    $this->assertEquals("select `a`, `b`, `d1` as `d` from `test1` where `a`='1'", $table1->_build_load_query(1));
  }

  public function testBuildLoad1 () {
    global $table1;

    $actual = $table1->load(1);
    $expected = array('a' => 1, 'b' => 'foo', 'd' => 'f');
    $this->assertEquals($expected, $actual);
  }
}
