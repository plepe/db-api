<?php
class DBApiChangeset {
  function __construct ($api, $options=array()) {
    $this->api = $api;
    $this->db = $api->db;
    $this->options = $options;

    $this->objects = array();
    $this->removed_objects = array();
  }

  function add ($table, $id) {
    if (!array_key_exists($table->id, $this->objects)) {
      $this->objects[$table->id] = array();
    }

    $this->objects[$table->id][] = $id;
  }

  function remove ($table, $id) {
    if (!array_key_exists($table->id, $this->removed_objects)) {
      $this->removed_objects[$table->id] = array();
    }

    $this->removed_objects[$table->id][] = $id;
  }

  function beginTransaction () {
    $this->db->beginTransaction();
  }

  function rollBack () {
    $this->db->rollBack();
  }

  function commit () {
    $this->db->commit();
  }

  function __destruct () {
  }
}
