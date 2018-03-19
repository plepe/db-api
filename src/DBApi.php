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
    foreach ($actions as $i => $action) {
      if (!array_key_exists($action['table'], $this->tables)) {
        throw new Exception("No such table '{$action['table']}'");
      }

      switch ($action['action'] ?? 'select') {
        case 'update':
          yield $this->tables[$action['table']]->update($action);
          break;
        case 'insert-update':
          yield $this->tables[$action['table']]->insert_update($action['data']);
          break;
        case 'select':
          yield $this->tables[$action['table']]->select($action);
          break;
        case 'delete':
          yield $this->tables[$action['table']]->delete($action);
          break;
        default:
          throw new Exception("No such action '{$action['action']}'");
      }
    }
  }
}
