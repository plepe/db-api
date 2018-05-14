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
    // no sub tables
    if (property_exists($table, 'parent_field')) {
      return;
    }

    if (!array_key_exists($table->id, $this->objects)) {
      $this->objects[$table->id] = array();
    }

    $this->objects[$table->id][] = $id;
  }

  function remove ($table, $id) {
    // no sub tables
    if (property_exists($table, 'parent_field')) {
      return;
    }

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
    $this->writeChanges();
    $this->db->commit();
  }

  function writeChanges () {
    if (!$this->api->history) {
      return;
    }

    foreach ($this->removed_objects as $tableId=>$entries) {
      foreach ($entries as $id) {
        $this->api->history->removeFile($tableId, $id);
      }
    }

    foreach ($this->objects as $tableId=>$entries) {
      $table = $this->api->tables[$tableId];

      foreach ($table->select(array('id' => $entries)) as $entry) {
        $id = $entry[$table->id_field];
        $this->api->history->writeFile($tableId, $id, $entry);
      }
    }

    $this->objects = null;
    $this->removed_objects = null;

    $this->api->history->commit($this);
  }

  function __destruct () {
    if ($this->objects) {
      $this->writeChanges();
    }
  }
}
