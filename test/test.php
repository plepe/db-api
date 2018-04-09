<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->query("drop table if exists test1; create table test1 ( a int not null auto_increment, b tinytext not null, c tinytext null, d1 text null, e int null, primary key(a)); insert into test1 values (1, 'foo', 'foo', 'foo', 5), (2, 'bar', 'bar', 'bar', 10);");
$db->query("drop table if exists test2_comments; drop table if exists test2; create table test2 ( id int not null auto_increment, visible boolean not null default true, primary key(id)); insert into test2 (id) values (1), (2); create table test2_comments ( test2_id int not null, id int not null auto_increment, text mediumtext, primary key(id), foreign key(test2_id) references test2(id) on update cascade on delete cascade); insert into test2_comments values (1, 1, 'foo'), (1, 2, 'bar'), (2, 3, 'foobar');");
$db->query(<<<EOT
drop table if exists test3;
create table test3 (
  name          varchar(255)    not null,
  birthday      date            null,
  weight        float           null,
  primary key (name)
);
insert into test3 values
  ('Alice', '1978-01-01', 50),
  ('Bob', '1982-03-25', 82),
  ('Conny', '1982-08-12', 50),
  ('Dennis', '1950-11-20', 68);
EOT
);

global $api;
global $table1;
global $table1a;
global $table2;
global $table2a;
global $table3;

include "structure.php";

/**
 * @backupGlobals disabled
 */
class db_api_test extends PHPUnit_Framework_TestCase {
  public function testBuildLoadQuery1 () {
    global $table1;

    $query = array('query' => 1);
    $this->assertEquals("select `a` as `a`, `b` as `b`, (select substr(`d1`, 1, 1)) as `d` from `test1` where `a`='1'", $table1->_build_select_query($query));
  }

  public function testBuildLoad1 () {
    global $table1;

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo', 'd' => 'f'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_op_gt() {
    global $table1;

    $actual = $table1->select(array('query' => array(array('key' => 'a', 'op' => '>', 'value' => 1))));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 2, 'b' => 'bar', 'd' => 'b'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_op_in() {
    global $table1;

    $actual = $table1->select(array('query' => array(array('key' => 'a', 'op' => 'in', 'value' => array(1, 2)))));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 1, 'b' => 'foo', 'd' => 'f'),
      array('a' => 2, 'b' => 'bar', 'd' => 'b'),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_op_in_empty() {
    global $table1;

    $actual = $table1->select(array('query' => array(array('key' => 'a', 'op' => 'in', 'value' => array()))));
    $actual = iterator_to_array($actual);
    print_r($actual);
    $expected = array(
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_query_unreadable_field () {
    global $table1;

    try {
      $actual = $table1->select(array('query' => array(array('key' => 'c', 'op' => '=', 'value' => 'foo'))));
      $actual = iterator_to_array($actual);
    } catch (Exception $e) {
      if ($e->getMessage() !== "permission denied, order by 'c'") {
        throw $e;
      }
      $got_exception = true;
    }

    $this->assertEquals(true, $got_exception, 'Didn\'t get a "permission denied" exception!');
  }

  public function testBuildLoad1_fields () {
    global $table1;

    $actual = $table1->select(array('query' => 1, 'fields' => array('b', 'c')));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo'));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_fields_with_e () {
    global $table1;

    $actual = $table1->select(array('query' => 1, 'fields' => array('b', 'e')));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'foo', 'e' => 5));
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

  public function testBuildLoad1_empty_query () {
    global $table1;

    $actual = $table1->select(array('query' => array(), 'fields' => array('a')));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 1),
      array('a' => 2),
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoadQuery1_customQuery () {
    global $table1;

    $query = array('query' => array(
      array('key' => 'b', 'op' => '=', 'value' => 'foo'),
    ));
    $actual = $table1->_build_select_query($query);
    $expected = "select `a` as `a`, `b` as `b`, (select substr(`d1`, 1, 1)) as `d` from `test1` where `b`='foo'";
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

  public function testBuildLoad1_insert_update_only_b () {
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

  public function testBuildLoad1a_insert_update_only_b () {
    global $table1a;

    $ids = $table1a->insert_update(array(
      array('a' => 1, 'b' => 'blubb'),
    ));

    $this->assertEquals(array(1), $ids);

    $actual = $table1a->select(array('query' => 1));
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

  public function testBuildLoad1_insert_update_d_null () {
    global $table1;

    $ids = $table1->insert_update(array(
      array('a' => 1, 'd' => null),
    ));

    $this->assertEquals(array(1), $ids);

    $actual = $table1->select(array('query' => 1));
    $actual = iterator_to_array($actual);
    $expected = array(array('a' => 1, 'b' => 'blubb', 'd' => null));
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoad1_select_null() {
    global $table1;

    $actual = $table1->select(array(
      'query' => array(array('d', '=', null)),
    ));


    $actual = iterator_to_array($actual);
    print_r($actual);
    $expected = array(array('a' => 1, 'b' => 'blubb', 'd' => null));
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

  public function testBuildLoad1_idchange() {
    global $table1;

    $ids = $table1->insert_update(array(
      array('__id' => '3', 'a' => '4'),
    ));
    $this->assertEquals(array(4), $ids);

    $actual = $table1->select(array('query' => array(array('a', 'in', array(3, 4)))));
    $actual = iterator_to_array($actual);
    $expected = array(
      array('a' => 4, 'b' => 'bla', 'd' => 'b')
    );
    $this->assertEquals($expected, $actual);
  }

  public function testBuildLoadQuery2 () {
    global $table2;

    $query = array('query' => 1);
    $this->assertEquals("select `id` as `id`, (select count(*) from test2_comments where test2_id=test2.id) as `commentsCount` from `test2` where `id`='1'", $table2->_build_select_query($query));
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

  public function testBuildLoad2_select_field () {
    global $table2;

    $actual = $table2->select(array(
      'query' => array(array('commentsCount', '=', 1)),
      'fields' => array('id', 'commentsCount'),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 2,
        'commentsCount' => 1,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

// select x from bla where (select true from test2_comments where limit 1)=true is not null;
  public function testBuildLoad2_select_nestedfield () {
    global $table2;

    $actual = $table2->select(array(
      'query' => array(array(array('comments', 'text'), '=', 'foobar')),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'id' => 2,
        'commentsCount' => 1,
        'comments' => array (
          array (
            'test2_id' => 2,
            'id' => 3,
            'text' => 'foobar',
          ),
        ),
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

  public function test3_load_order_age () {
    global $table3;

    $actual = $table3->select(array(
      'fields' => array('name', 'age'),
      'order' => array('age'),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'name' => 'Bob',
	'age' => 35,
      ),
      array (
	'name' => 'Conny',
	'age' => 35,
      ),
      array (
	'name' => 'Alice',
	'age' => 40,
      ),
      array (
	'name' => 'Dennis',
	'age' => 67,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function test3_load_order_age_asc () {
    global $table3;

    $actual = $table3->select(array(
      'fields' => array('name', 'age'),
      'order' => array('+age'),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'name' => 'Bob',
	'age' => 35,
      ),
      array (
	'name' => 'Conny',
	'age' => 35,
      ),
      array (
	'name' => 'Alice',
	'age' => 40,
      ),
      array (
	'name' => 'Dennis',
	'age' => 67,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function test3_load_order_age_desc () {
    global $table3;

    $actual = $table3->select(array(
      'fields' => array('name', 'age'),
      'order' => array('-age'),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'name' => 'Dennis',
	'age' => 67,
      ),
      array (
	'name' => 'Alice',
	'age' => 40,
      ),
      array (
	'name' => 'Bob',
	'age' => 35,
      ),
      array (
	'name' => 'Conny',
	'age' => 35,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function test3_load_order_weight_age () {
    global $table3;

    $actual = $table3->select(array(
      'fields' => array('name', 'weight', 'age'),
      'order' => array('weight', 'age'),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'name' => 'Conny',
        'weight' => 50.0,
	'age' => 35,
      ),
      array (
	'name' => 'Alice',
        'weight' => 50.0,
	'age' => 40,
      ),
      array (
	'name' => 'Dennis',
        'weight' => 68.0,
	'age' => 67,
      ),
      array (
	'name' => 'Bob',
        'weight' => 82.0,
	'age' => 35,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function test3_load_order_weight_age_limit () {
    global $table3;

    $actual = $table3->select(array(
      'fields' => array('name', 'weight', 'age'),
      'order' => array('weight', 'age'),
      'limit' => 2,
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'name' => 'Conny',
        'weight' => 50.0,
	'age' => 35,
      ),
      array (
	'name' => 'Alice',
        'weight' => 50.0,
	'age' => 40,
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function test3_load_strsearch () {
    global $table3;

    $actual = $table3->select(array(
      'fields' => array('name'),
      'query' => array(array('name', 'strsearch', 'nn')),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'name' => 'Conny',
      ),
      array (
	'name' => 'Dennis',
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function test3_load_strsearch2 () {
    global $table3;

    $actual = $table3->select(array(
      'fields' => array('name'),
      'query' => array(array('name', 'strsearch', 'd nn')),
    ));
    $actual = iterator_to_array($actual);
    $expected = array (
      array (
	'name' => 'Dennis',
      ),
    );

    $this->assertEquals($expected, $actual);
  }

  public function testApiTables() {
    global $api;

    $expected = array('test1', 'test1a', 'test2', 'test2a', 'test3');
    $actual = array_keys($api->tables);

    $this->assertEquals($expected, $actual);
  }

  public function testApiLoad() {
    global $api;

    $expected = array(
      array (
	array ( 'a' => 2,),
	array ( 'a' => 4,),
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
