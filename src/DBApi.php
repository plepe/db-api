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
}
