<?php
class DBApiHistory {
  function __construct ($api, $options=array()) {
    $this->api = $api;
    $this->api->history = $this;
    $this->path = $options['path'];

    $this->tables = array();
  }

  function checkInitialCommit () {
    if (!is_dir("{$this->path}/.git")) {
      exec("cd " . escapeShellArg($this->path) . "; git init", $out);
      $this->dump();
      $this->commit(new DBApiChangeset($this->api, array('message' => 'initial commit')));
    }
  }

  function setTable ($schema) {
    $table = new DBApiTable($this->api, $schema);

    $this->tables[$table->id] = $table;

    return $table;
  }

  function clearRepo ($subPath='') {
    $d = opendir("{$this->path}/{$subPath}");
    while ($f = readdir($d)) {
      if ($f === '.' || $f === '..') {
        continue;
      }
      if ($subPath === '' && $f === '.git') {
        continue;
      }

      if (is_dir("{$this->path}/{$subPath}/$f")) {
        $this->clearRepo("{$subPath}/$f");
        rmdir("{$this->path}/{$subPath}/$f");
      } else {
        unlink("{$this->path}/{$subPath}/$f");
      }
    }

    return true;
  }

  function writeFiles ($tableId, $ids) {
    $table = $this->api->tables[$tableId];
    if (array_key_exists($tableId, $this->tables)) {
      $table = $this->tables[$tableId];
    }

    foreach ($table->select(array('id' => $ids)) as $entry) {
      $id = $entry[$table->id_field];
      file_put_contents("{$this->path}/{$tableId}/{$id}.json", json_readable_encode($entry) . "\n");
      exec("cd " . escapeShellArg($this->path) . "; git add " . escapeShellArg("{$tableId}/{$id}.json"));
    }
  }

  function removeFiles ($tableId, $ids) {
    foreach ($ids as $id) {
      unlink("{$this->path}/{$tableId}/{$id}.json");
      exec("cd " . escapeShellArg($this->path) . "; git rm --cached " . escapeShellArg("{$tableId}/{$id}.json"));
    }
  }

  function commit ($changeset) {
    global $auth;

    if($auth && $auth->current_user()) {
      $user = $auth->current_user()->name();
      $email = $auth->current_user()->email();
    }
    else {
      $user = "Unknown";
      $email = "unknown@unknown";
    }

    if(!$email)
      $email = "unknown@unknown";

    $result = exec("cd " . escapeShellArg($this->path) . "; git " .
	     "-c user.name=" . escapeShellArg($user) . " " .
	     "-c user.email=" . escapeShellArg($email) . " " .
	     "commit " .
	     // "-a -m " . escapeShellArg($changeset->options['message'] ?? '') . " " . // PHP7
	     "-a -m " . escapeShellArg(array_key_exists('message', $changeset->options) ? $changeset->options['message'] : '') . " " .
	     "--allow-empty-message ".
	     "--author=" . escapeShellArg("{$user} <{$email}>")
	  );
  }

  function dump () {
    $this->clearRepo();

    foreach ($this->api->tables as $table) {
      mkdir("{$this->path}/{$table->id}");

      if (array_key_exists($table->id, $this->tables)) {
        $table = $this->tables[$table->id];
      }

      foreach ($table->select() as $entry) {
        $id = $entry[$table->id_field];
        file_put_contents("{$this->path}/{$table->id}/{$id}.json", json_readable_encode($entry) . "\n");
      }
    }

    exec("cd " . escapeShellArg($this->path) . "; git add . ; git ", $out);
  }
}
