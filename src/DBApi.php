<?php
class DBApi {
  function __construct ($db) {
    $this->db = $db;
    $this->tables = array();
  }

  function addTable ($spec) {
    $table = new DBApiTable($this->db, $spec);

    $this->tables[$table->id] = $table;

    return $table;
  }

  function do ($actions) {
    foreach ($actions as $action) {
      if (!array_key_exists($action['table'], $this->tables)) {
        // ERROR!
      }

      switch ($action['type'] ?? 'select') {
        case 'update':
          yield $this->tables[$action['table']]->save($action);
          break;
        case 'select':
          yield $this->tables[$action['table']]->load($action);
          break;
      }
    }
  }
}
