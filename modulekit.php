<?php
$id = 'db-api';

$depend = array(
  'PDOext',
  'json_readable_encode',
);

$include = array(
  'php' => array(
    'src/DBApi.php',
    'src/DBApiTable.php',
  ),
);
