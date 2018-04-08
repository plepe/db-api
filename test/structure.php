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
    'e' => array(
      'type' => 'int',
      'include' => false,
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

$spec3 = array(
  'id' => 'test3',
  'id_field' => 'name',
  'fields' => array(
    'name' => array(
      'type' => 'string',
    ),
    'age' => array(
      'type' => 'int',
      // calculate age, see https://stackoverflow.com/a/7749665
      'select' => "YEAR('2018-03-19') - YEAR(birthday) - (DATE_FORMAT('2018-03-19', '%m%d') < DATE_FORMAT(birthday, '%m%d'))",
    ),
    'weight' => array(
      'type' => 'float',
    ),
  ),
);

$spec2a = $spec2;
$spec2a['id'] = 'test2a';
$spec2a['query-visible'] = 'visible=true';

$spec1a = $spec1;
$spec1a['id'] = 'test1a';
$spec1a['table'] = 'test1';
$spec1a['fields']['a']['write'] = false;

$api = new DBApi($db);
$table1 = $api->addTable($spec1);
$table1a = $api->addTable($spec1a);
$table2 = $api->addTable($spec2);
$table2a = $api->addTable($spec2a);
$table3 = $api->addTable($spec3);
