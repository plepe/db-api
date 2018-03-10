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

$spec2 = array(
  'id' => 'test2',
  'fields' => array(
    'id' => array(
      'read' => true,
      'write' => true,
      'type' => 'int',
    ),
    'comments' => array(
      'type' => 'sub_table',
      'id' => 'test2_comments',
      'fields' => array(
        'test2_id' => array(
          'read' => true,
          'write' => true,
          'type' => 'int',
        ),
        'id' => array(
          'read' => true,
          'write' => true,
          'type' => 'int',
        ),
        'text' => array(
        ),
      ),
      'parent_field' => 'test2_id',
    ),
  ),
);

$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->query("drop table if exists test1; create table test1 ( a int not null, b tinytext, c tinytext, d1 text, primary key(a)); insert into test1 values (1, 'foo', 'foo', 'foo'), (2, 'bar', 'bar', 'bar');");
$db->query("drop table if exists test2_comments; drop table if exists test2; create table test2 ( id int not null, primary key(id)); insert into test2 values (1), (2); create table test2_comments ( test2_id int not null, id int not null, text mediumtext, primary key(test2_id, id), foreign key(test2_id) references test2(id) on update cascade on delete cascade); insert into test2_comments values (1, 1, 'foo'), (1, 2, 'bar'), (2, 3, 'foobar');");

global $table1;
global $table2;
$table1 = new DBApi($db, $spec1);
$table2 = new DBApi($db, $spec2);

/**
 * @backupGlobals disabled
 */
class db_api_test extends PHPUnit_Framework_TestCase {
  public function testBuildLoadQuery1 () {
    global $table1;

    $this->assertEquals("select `a`, `b`, `d1` as `d` from `test1` where `a`='1'", $table1->_build_load_query(array('query' => 1)));
  }

  public function testBuildLoad1 () {
    global $table1;

    $actual = $table1->load(array('query' => 1));
    $expected = array('1' => array('a' => 1, 'b' => 'foo', 'd' => 'f'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_fields () {
    global $table1;

    $actual = $table1->load(array('query' => 1, 'fields' => array('b', 'c')));
    $expected = array('1' => array('a' => 1, 'b' => 'foo'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoadQuery1_customQuery () {
    global $table1;

    $actual = $table1->_build_load_query(array('query' => array(
      array('key' => 'b', 'op' => '=', 'value' => 'foo'),
    )));
    $expected = "select `a`, `b`, `d1` as `d` from `test1` where `b`='foo'";
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_customQuery () {
    global $table1;

    $actual = $table1->load(array('query' => array(
      array('key' => 'b', 'op' => '=', 'value' => 'foo'),
    )));
    $expected = array(1 => array('a' => 1, 'b' => 'foo', 'd' => 'f'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoadQuery2 () {
    global $table2;

    $this->assertEquals("select `id` from `test2` where `id`='1'", $table2->_build_load_query(array('query' => 1)));
  }

  public function testBuildLoad2 () {
    global $table2;

    $actual = $table2->load(array('query' => 1));
    $expected = array (
      1 => array (
	'id' => 1,
	'comments' => array (
	  1 => array (
	    'test2_id' => 1,
	    'id' => 1,
	    'text' => 'foo',
	  ),
	  2 => array (
	    'test2_id' => 1,
	    'id' => 2,
	    'text' => 'bar',
	  ),
	),
      ),
    );

    $this->assertEquals($expected, $actual);
  }
}
