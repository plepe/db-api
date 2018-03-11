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

  function load ($options=array()) {
    foreach ($options as $table => $tableOptions) {
      yield $table => $this->tables[$table]->load($tableOptions);
    }
  }
}
