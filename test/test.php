<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
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
      'read' => true,
      'write' => true,
      'select' => 'select substr(`d1`, 1, 1)'
    ),
  ),
  'id_field' => 'a',
);

$spec2 = array(
  'id' => 'test2',
  'table' => 'test2',
  'fields' => array(
    'id' => array(
      'read' => true,
      'write' => true,
      'type' => 'int',
    ),
    'visible' => array(
      'read' => false,
      'write' => true,
      'type' => 'boolean',
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
          'write' => true,
        ),
      ),
      'parent_field' => 'test2_id',
    ),
  ),
);

$spec2a = $spec2;
$spec2a['id'] = 'test2a';
$spec2a['query-visible'] = 'visible=true';

$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->query("drop table if exists test1; create table test1 ( a int not null auto_increment, b tinytext not null, c tinytext null, d1 text not null, primary key(a)); insert into test1 values (1, 'foo', 'foo', 'foo'), (2, 'bar', 'bar', 'bar');");
$db->query("drop table if exists test2_comments; drop table if exists test2; create table test2 ( id int not null auto_increment, visible boolean not null default true, primary key(id)); insert into test2 (id) values (1), (2); create table test2_comments ( test2_id int not null, id int not null auto_increment, text mediumtext, primary key(id), foreign key(test2_id) references test2(id) on update cascade on delete cascade); insert into test2_comments values (1, 1, 'foo'), (1, 2, 'bar'), (2, 3, 'foobar');");

global $api;
global $table1;
global $table2;
global $table2a;

$api = new DBApi($db);
$table1 = $api->addTable($spec1);
$table2 = $api->addTable($spec2);
$table2a = $api->addTable($spec2a);

/**
 * @backupGlobals disabled
 */
class db_api_test extends PHPUnit_Framework_TestCase {
  public function testBuildLoadQuery1 () {
    global $table1;

    $query = array('query' => 1);
    $this->assertEquals("select `a`, `b`, (select substr(`d1`, 1, 1)) as `d` from `test1` where `a`='1'", $table1->_build_select_query($query));
  }

  public function testBuildLoad1 () {
    global $table1;

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo', 'd' => 'f'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_op_in() {
    global $table1;

    $actual = $table1->select(array('query' => array(array('key' => 'a', 'op' => 'in', 'value' => array(1, 2)))));
    $actual = iterator_to_array($actual);
    print_r($actual);
    $expected = array(
      array('a' => 1, 'b' => 'foo', 'd' => 'f'),
      array('a' => 2, 'b' => 'bar', 'd' => 'b'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_op_in_simple() {
    global $table1;

    $actual = $table1->select(array('query' => array(array('a', 'in', array(1, 2)))));
    $actual = iterator_to_array($actual);
    print_r($actual);
    $expected = array(
      array('a' => 1, 'b' => 'foo', 'd' => 'f'),
      array('a' => 2, 'b' => 'bar', 'd' => 'b'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_fields () {
    global $table1;

    $actual = $table1->select(array('query' => 1, 'fields' => array('b', 'c')));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_all_fields () {
    global $table1;

    $actual = $table1->select(array('fields' => array('b', 'c')));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 1, 'b' => 'foo'),
      array('a' => 2, 'b' => 'bar'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_all_fields_limit () {
    global $table1;

    $actual = $table1->select(array('fields' => array('b', 'c'), 'limit' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 1, 'b' => 'foo'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_all_offset_limit () {
    global $table1;

    $actual = $table1->select(array('fields' => array('b', 'c'), 'offset' => 1, 'limit' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 2, 'b' => 'bar'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoadQuery1_customQuery () {
    global $table1;

    $query = array('query' => array(
      array('key' => 'b', 'op' => '=', 'value' => 'foo'),
    ));
    $actual = $table1->_build_select_query($query);
    $expected = "select `a`, `b`, (select substr(`d1`, 1, 1)) as `d` from `test1` where `b`='foo'";
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_customQuery () {
    global $table1;

    $actual = $table1->select(array('query' => array(
      array('key' => 'b', 'op' => '=', 'value' => 'foo'),
    )));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo', 'd' => 'f'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_update () {
    global $table1;

    $ids = $table1->update(array(
      'update' => array('b' => 'bla', 'd' => 'bla'),
      'query' => '1',
    ));

    $this->assertEquals(array(1), $ids);

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'bla', 'd' => 'b'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_insert_update_only_a_b () {
    global $table1;

    $ids = $table1->insert_update(array(
      array('a' => 1, 'b' => 'blubb'),
    ));

    $this->assertEquals(array(1), $ids);

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'blubb', 'd' => 'b'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_insert_update_only_a () {
    global $table1;

    $ids = $table1->insert_update(array(
      array('a' => 1),
    ));

    $this->assertEquals(array(1), $ids);

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'blubb', 'd' => 'b'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_insert_update_perm_denied () {
    global $table1;
    $got_exception = false;

    try {
      $ids = $table1->insert_update(array(
        array('a' => 1, 'c' => 'foo'),
      ));
    } catch (Exception $e) {
      if ($e->getMessage() !== 'permission denied') {
        throw $e;
      }
      $got_exception = true;
    }

    $this->assertEquals(true, $got_exception, 'Didn\'t get a "permission denied" exception!');

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'blubb', 'd' => 'b'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_create () {
    global $table1;

    $ids = $table1->insert_update(array(
      array('b' => 'bla', 'd' => 'bla'),
    ));
    $this->assertEquals(array(3), $ids);

    $actual = $table1->select(array('query' => $ids[0]));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 3, 'b' => 'bla', 'd' => 'b'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_delete () {
    global $table1;

    $result = $table1->delete(array(
      'query' => 1
    ));
    $this->assertEquals(array('count' => 1), $result);

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array();
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoadQuery2 () {
    global $table2;

    $query = array('query' => 1);
    $this->assertEquals("select `id`, (select count(*) from test2_comments where test2_id=test2.id) as `commentsCount` from `test2` where `id`='1'", $table2->_build_select_query($query));
  }

  public function testBuildLoad2 () {
    global $table2;

    $actual = $table2->select(array('query' => 1));
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

  public function testBuildLoad2_fields () {
    global $table2;

    $actual = $table2->select(array('query' => 1, 'fields' => array('commentsCount')));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 1,
        'commentsCount' => 2,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad2_update () {
    global $table2;

    $ids = $table2->update(array(
      'update' => array('comments' => array(array('id' => 1), array('id' => 2, 'text' => 'foobar'), array('text' => 'foobar2'))),
      'query' => 1,
    ));
    $this->assertEquals(array(1), $ids);

    $actual = $table2->select(array('query' => 1));
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

  public function testBuildLoad2_delete_sub_field () {
    global $table2;

    $ids = $table2->update(array(
      'update' => array('comments' => array(array('id' => 2), array('id' => 4))),
      'query' => 1,
    ));
    $this->assertEquals(array(1), $ids);

    $actual = $table2->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    print_r($actual);
    $expected = array (
      array (
	'id' => 1,
        'commentsCount' => 2,
	'comments' => array (
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

    $ids = $table2->insert_update(array(
      array('comments' => array(array('text' => 'foobar'), array('text' => 'foobar2'))),
    ));
    $this->assertEquals(array(3), $ids);

    $actual = $table2->select(array('query' => $ids[0]));
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

  public function testBuildLoad2_create_with_id () {
    global $table2;

    $ids = $table2->insert_update(array(
      array('id' => 4, 'comments' => array(array('text' => 'foobar'), array('text' => 'foobar2'))),
    ));
    $this->assertEquals(array(4), $ids);

    $actual = $table2->select(array('query' => $ids[0]));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 4,
        'commentsCount' => 2,
	'comments' => array (
	  array (
	    'test2_id' => 4,
	    'id' => 7,
	    'text' => 'foobar',
	  ),
	  array (
	    'test2_id' => 4,
	    'id' => 8,
	    'text' => 'foobar2',
	  ),
	),
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad2_update_visible () {
    global $table2;

    $ids = $table2->update(array(
      'update' => array('visible' => false),
      'query' => 1,
    ));
    $this->assertEquals(array(1), $ids);

    $actual = $table2->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 1,
        'commentsCount' => 2,
	'comments' => array (
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

  public function testBuildLoad2a () {
    global $table2a;

    $actual = $table2a->select(array('fields' => array('id', 'commentsCount')));
    $actual = iterator_to_array($actual);
    print_r($actual);
    $expected = array (
      array (
	'id' => 2,
        'commentsCount' => 1,
      ),
      array (
	'id' => 3,
        'commentsCount' => 2,
      ),
      array (
	'id' => 4,
        'commentsCount' => 2,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function testApiTables() {
    global $api;

    $expected = array('test1', 'test2', 'test2a');
    $actual = array_keys($api->tables);

    $this->assertEquals($expected, $actual);
  }

  public function testApiLoad() {
    global $api;

    $expected = array(
      array (
	array ( 'a' => 2,),
	array ( 'a' => 3,),
      ),
      array (
	array ( 'commentsCount' => 2, 'id' => 1,),
      ),
    );
    $actual = iterator_to_array_deep($api->do(
      array(
        array(
          'table' => 'test1',
          'fields' => array('a'),
        ),
        array(
          'type' => 'select',
          'table' => 'test2',
          'query' => 1,
          'fields' => array('commentsCount')
        ),
      )
    ));

    $this->assertEquals($expected, $actual);
  }

  public function testApiUpdate_perm_denied() {
    global $api;

    $expected = "permission denied";
    try {
      iterator_to_array_deep($api->do(
        array(
          array(
            'table' => 'test1',
            'action' => 'update',
            'update' => array('c' => 'foo'),
          ),
          array(
            'table' => 'test1',
            'action' => 'select',
          ),
        )
      ));
    } catch (Exception $e) {
      $actual = $e->getMessage();
    }

    $this->assertEquals($expected, $actual);
  }

}
