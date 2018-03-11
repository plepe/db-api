<?php
class DBApi {
  function __construct ($db) {
    $this->db = $db;
  }

  function addTable($spec) {
    $table = new DBApiTable($this->db, $spec);

    return $table;
  }
}
