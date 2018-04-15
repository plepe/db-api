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
    'src/DBApiView.php',
    'src/DBApiViewJSON.php',
    'src/DBApiViewTwig.php',
    'src/emptyElement.php',
  ),
);
