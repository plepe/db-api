<?php
$dbApiViewTypes = array(
  'Base' => 'DBApiView',
  'JSON' => 'DBApiViewJSON',
  'Twig' => 'DBApiViewTwig',
);

class DBApi {
  function __construct ($db) {
    $this->db = $db;
    $this->tables = array();
  }

  function addTable ($schema) {
    $table = new DBApiTable($this, $schema);

    $this->tables[$table->id] = $table;

    return $table;
  }

  function getTable ($id) {
    return $this->tables[$id];
  }

  function do ($actions, $options=array()) {
    $changeset = new DBApiChangeset($this, $options);
    $changeset->beginTransaction();

    foreach ($actions as $i => $action) {
      if (!array_key_exists('table', $action)) {
        if ($action['action'] === 'schema') {
          $ret = array();
          foreach ($this->tables as $table) {
            foreach ($table->schema($action) as $schema) {
              $ret[] = $schema;
            }
          }
          yield $ret;
          continue;
        }
        else {
          throw new Exception("No table specified");
        }
      }

      if (!array_key_exists($action['table'], $this->tables)) {
        throw new Exception("No such table '{$action['table']}'");
      }

      try {
        switch ($action['action'] ?? 'select') {
          case 'update':
            yield $this->tables[$action['table']]->update($action, $changeset);
            break;
          case 'insert-update':
            yield $this->tables[$action['table']]->insert_update($action['data'], $changeset);
            break;
          case 'select':
            yield $this->tables[$action['table']]->select($action, $changeset);
            break;
          case 'delete':
            yield $this->tables[$action['table']]->delete($action, $changeset);
            break;
          case 'schema':
            yield $this->tables[$action['table']]->schema($action, $changeset);
            break;
          case 'nop':
            yield;
            break;
          default:
            throw new Exception("No such action '{$action['action']}'");
        }
      } catch (Exception $e) {
        $changeset->rollBack();
        throw $e;
      }
    }

    $changeset->commit();

    return $changeset;
  }

  function createView ($def=array(), $options=array()) {
    global $dbApiViewTypes;

    return new $dbApiViewTypes[$def['type']]($this, $def, $options);
  }

  function handle_http_response () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $actions = json_decode(file_get_contents('php://input'), true);
    } else {
      $actions = $_GET;
      if (!sizeof($_GET)) {
        $actions = json_decode(urldecode($_SERVER['QUERY_STRING']), true);
      }
    }

    Header("Content-type: application/json; charset=utf8");

    $output = "[[";
    $error = false;

    try {
      foreach ($this->do($actions) as $i => $result) {
        $output .= $i === 0 ? "\n" : "\n] ,[\n";
        foreach ($result as $j => $elem) {
          $output .= $j === 0 ? '' : ",\n";
          $output .= json_readable_encode($elem);
        }
      }
      $output .= "\n]]\n";
    }
    catch (Exception $e) {
      print json_readable_encode(array('error' => $e->getMessage()));
      $error = true;
    }

    if (!$error) {
      print $output;
    }
  }
}
