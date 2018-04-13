<?php
$id = 'db-api';

$depend = array(
  'PDOext',
  'json_readable_encode',
  'twig',
);

$include = array(
  'php' => array(
    'src/DBApi.php',
    'src/DBApiTable.php',
    'src/iterator_to_array_deep.php',
    'src/DBView.php',
    'src/DBViewJSON.php',
    'src/DBViewTwig.php',
  ),
);
