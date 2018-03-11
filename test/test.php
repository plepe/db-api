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
      'write' => true,
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
    'commentsCount' => array(
      'type' => 'int',
      'select' => 'select count(*) from test2_comments where test2_id=test2.id',
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

$db->query("drop table if exists test1; create table test1 ( a int not null auto_increment, b tinytext, c tinytext, d1 text, primary key(a)); insert into test1 values (1, 'foo', 'foo', 'foo'), (2, 'bar', 'bar', 'bar');");
$db->query("drop table if exists test2_comments; drop table if exists test2; create table test2 ( id int not null auto_increment, primary key(id)); insert into test2 values (1), (2); create table test2_comments ( test2_id int not null, id int not null auto_increment, text mediumtext, primary key(id), foreign key(test2_id) references test2(id) on update cascade on delete cascade); insert into test2_comments values (1, 1, 'foo'), (1, 2, 'bar'), (2, 3, 'foobar');");

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
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo', 'd' => 'f'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_fields () {
    global $table1;

    $actual = $table1->load(array('query' => 1, 'fields' => array('b', 'c')));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_all_fields () {
    global $table1;

    $actual = $table1->load(array('fields' => array('b', 'c')));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 1, 'b' => 'foo'),
      array('a' => 2, 'b' => 'bar'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_all_fields_limit () {
    global $table1;

    $actual = $table1->load(array('fields' => array('b', 'c'), 'limit' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 1, 'b' => 'foo'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_all_offset_limit () {
    global $table1;

    $actual = $table1->load(array('fields' => array('b', 'c'), 'offset' => 1, 'limit' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 2, 'b' => 'bar'),
    );
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
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo', 'd' => 'f'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_update () {
    global $table1;

    $ids = $table1->save(array(
      array('a' => 1, 'b' => 'bla', 'd' => 'bla'),
    ));

    $this->assertEquals(array(1), $ids);

    $actual = $table1->load(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'bla', 'd' => 'b'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_create () {
    global $table1;

    $ids = $table1->save(array(
      array('a' => '__new', 'b' => 'bla', 'd' => 'bla'),
    ));
    $this->assertEquals(array(3), $ids);

    $actual = $table1->load(array('query' => $ids[0]));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 3, 'b' => 'bla', 'd' => 'b'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoadQuery2 () {
    global $table2;

    $this->assertEquals("select `id`, (select count(*) from test2_comments where test2_id=test2.id) as `commentsCount` from `test2` where `id`='1'", $table2->_build_load_query(array('query' => 1)));
  }

  public function testBuildLoad2 () {
    global $table2;

    $actual = $table2->load(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 1,
        'commentsCount' => 2,
	'comments' => array (
	  array (
	    'test2_id' => 1,
	    'id' => 1,
	    'text' => 'foo',
	  ),
	  array (
	    'test2_id' => 1,
	    'id' => 2,
	    'text' => 'bar',
	  ),
	),
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad2_update () {
    global $table2;

    $ids = $table2->save(array(
      array('id' => 1, 'comments' => array(array('id' => 2, 'text' => 'foobar'), array('id' => '__new', 'text' => 'foobar2'))),
    ));
    $this->assertEquals(array(1), $ids);

    $actual = $table2->load(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 1,
        'commentsCount' => 3,
	'comments' => array (
	  array (
	    'test2_id' => 1,
	    'id' => 1,
	    'text' => 'foo',
	  ),
	  array (
	    'test2_id' => 1,
	    'id' => 2,
	    'text' => 'foobar',
	  ),
          array(
	    'test2_id' => 1,
	    'id' => 4,
	    'text' => 'foobar2',
          ),
	),
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad2_create () {
    global $table2;

    $ids = $table2->save(array(
      array('id' => '__new', 'comments' => array(array('id' => '__new', 'text' => 'foobar'), array('id' => '__new', 'text' => 'foobar2'))),
    ));
    $this->assertEquals(array(3), $ids);

    $actual = $table2->load(array('query' => $ids[0]));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 3,
        'commentsCount' => 2,
	'comments' => array (
	  array (
	    'test2_id' => 3,
	    'id' => 5,
	    'text' => 'foobar',
	  ),
	  array (
	    'test2_id' => 3,
	    'id' => 6,
	    'text' => 'foobar2',
	  ),
	),
      ),
    );

    $this->assertEquals($expected, $actual);
  }
}
