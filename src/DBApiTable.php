<?php
class DBApiTable {
  function __construct ($api, $schema) {
    $this->api = $api;
    $this->db = $api->db;
    $this->schema = $schema;
    $this->id = $this->schema['id'];
    $this->sub_tables = array();

    foreach ($this->schema['fields'] as $key => $field) {
      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        $this->sub_tables[$key] = new DBApiTable($this->api, $field);
      }
    }

    if (!array_key_exists('table', $this->schema)) {
      $this->schema['table'] = $this->schema['id'];
    }

    // PHP7
    // $this->id_field = $this->schema['id_field'] ?? 'id';
    // $this->old_id_field = $this->schema['old_id_field'] ?? '__id';
    $this->id_field = array_key_exists('id_field', $this->schema) ? $this->schema['id_field'] : 'id';
    $this->old_id_field = array_key_exists('old_id_field', $this->schema) ? $this->schema['old_id_field'] : '__id';
    if (array_key_exists('parent_field', $this->schema)) {
      $this->parent_field = $this->schema['parent_field'];
      $this->parent_field_quoted = $this->db->quoteIdent($this->schema['parent_field']);
    }

    $this->id_field_quoted = $this->db->quoteIdent($this->id_field);
    $this->old_id_field_quoted = $this->db->quoteIdent($this->old_id_field);
    $this->table_quoted = $this->db->quoteIdent($this->schema['table']);
  }

  function schema ($action) {
    yield $this->schema;
  }

  function _build_column ($key) {
    $field = $this->schema['fields'][$key];

    if (array_key_exists('select', $field)) {
      return "({$field['select']})";
    }
    else if (array_key_exists('column', $field)) {
      return $this->db->quoteIdent($field['column']);
    } else {
      return $this->db->quoteIdent($key);
    }
  }

  function _build_where_expression ($query) {
    if (is_array($query) && !array_key_exists('key', $query) && array_key_exists(0, $query)) {
      $query = array('key' => $query[0], 'op' => $query[1], 'value' => sizeof($query) > 2 ? $query[2] : null);
    }

    if (is_array($query['key'])) {
      $key = array_shift($query['key']);
      $field = $this->schema['fields'][$key];

      if (array_key_exists('read', $field) && $field['read'] === false) {
        throw new Exception("permission denied, order by '{$key}'");
      }

      if (sizeof($query['key'])) {
        $sub_table = $this->sub_tables[$key];

        if (sizeof($query['key'])) {
          $sub_select = array(
            'fields' => array(true),
            'query' => array($query),
          );

          return '(' . $sub_table->_build_select_query($sub_select) . " and {$sub_table->table_quoted}.{$sub_table->parent_field_quoted}={$this->table_quoted}.{$this->id_field_quoted} limit 1)=true";
        }
      } else {
        $query['key'] = $key;
      }
    }

    $field = $this->schema['fields'][$query['key']];

    if (array_key_exists('read', $field) && $field['read'] === false) {
      throw new Exception("permission denied, order by '{$query['key']}'");
    }

    $key_quoted = $this->_build_column($query['key']);

    // switch ($query['op'] ?? '=') { // PHP7
    switch (array_key_exists('op', $query) ? $query['op'] : '=') {
      case 'in':
        if (sizeof($query['value'])) {
          return "{$key_quoted} in (" . implode(', ', array_map(function ($v) { return $this->db->quote($v); }, $query['value'])) . ')';
        }
        else {
          return 'false';
        }
        break;
      case '>':
      case '>=':
      case '<=':
      case '<':
        return "{$key_quoted}{$query['op']}" . $this->db->quote($query['value']);
      case '=':
        if ($query['value'] === null) {
          return "{$key_quoted} is null";
        }
        else {
          return "{$key_quoted}=" . $this->db->quote($query['value']);
        }
      case 'strsearch':
        $q = array();
        foreach (explode(' ', $query['value']) as $v) {
          $q[] = "{$key_quoted} like " . $this->db->quote("%{$v}%");
        }
        return implode(' and ', $q);
      default:
        throw new Exception("Unknown query operation '{$query['op']}'");
    }
  }

  function _build_where ($action) {
    $where = array();

    if (array_key_exists('id', $action)) {
      if (is_array($action['id'])) {
        $where[] = $this->_build_where_expression(array(
          'key' => $this->id_field,
          'op' => 'in',
          'value' => $action['id'],
        ));
      }
      else {
        $where[] = "{$this->id_field_quoted}=" . $this->db->quote($action['id']);
      }
    }

    if (array_key_exists('query', $action)) {
      if (is_array($action['query'])) {
        foreach ($action['query'] as $q) {
          $where[] = $this->_build_where_expression($q);
        }
      }
      else {
        trigger_error('query with id is no longer supported, use id=>x instead', E_USER_ERROR);
      }
    }

    if (array_key_exists('query-visible', $this->schema)) {
      $where[] = $this->schema['query-visible'];
    }

    return sizeof($where) ? 'where ' . implode(' and ', $where) : '';
  }

  function _build_query ($action) {
    $limit_offset = '';
    if (array_key_exists('limit', $action) && is_int($action['limit'])) {
      $limit_offset .= " limit {$action['limit']}";
    }
    if (array_key_exists('offset', $action) && is_int($action['offset'])) {
      $limit_offset .= " offset {$action['offset']}";
    }

    return $this->_build_where($action) .
        $this->_build_order($action) .
        $limit_offset;
  }

  function _build_set ($data) {
    $set = array();

    foreach ($data as $key => $d) {
      $field = $this->schema['fields'][$key];

      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        $update_sub_table = false;
        continue;
      }

      if (!array_key_exists('write', $field) || $field['write'] === false) {
        throw new Exception("permission denied, writing {$this->id} field {$key}");
      }

      // $set[] = $this->db->quoteIdent($field['column'] ?? $key) . '=' . $this->db->quote($d); // PHP7
      $set[] = $this->db->quoteIdent(array_key_exists('column', $field) ? $field['column'] : $key) . '=' . $this->db->quote($d);
    }

    if (sizeof($set)) {
      return implode(', ', $set);
    }
    return '';
  }

  function _default_fields ($action) {
    $fields = array();

    foreach ($this->schema['fields'] as $field_id => $field) {
      if (!array_key_exists('include', $field) || $field['include'] === true) {
        $fields[] = $field_id;
      }
    }

    return $fields;
  }

  function _build_select_query (&$action) {
    if (!array_key_exists('fields', $action)) {
      $action['fields'] = $this->_default_fields($action);
    }

    if (!in_array($this->id_field, $action['fields'])) {
      $action['fields'][] = $this->id_field;
    }

    $select = array();
    foreach ($action['fields'] as $key) {
      if ($key === true) {
        $select[] = 'true';
        continue;
      }

      $field = $this->schema['fields'][$key];

      if (array_key_exists('type', $field) && in_array($field['type'], array('fun', 'sub_table'))) {
        continue;
      }

      if (!array_key_exists('read', $field) || $field['read']) {
        $select[] = $this->_build_column($key) . ' as ' . $this->db->quoteIdent($key);
      }
    }

    // if ($action['old_id'] ?? false) { // PHP7
    if (array_key_exists('old_id', $action) ? $action['old_id'] : false) {
      $select[] = $this->id_field_quoted . ' as ' . $this->old_id_field_quoted;
    }

    return 'select ' . implode(', ', $select) .
      " from {$this->table_quoted} " .
      $this->_build_query($action);
  }

  function _build_order ($action) {
    $res = array();

    // $order = $action['order'] ?? $this->schema['order'] ?? array(); // PHP7
    $order = array_key_exists('order', $action) ? $action['order']
           : (array_key_exists('order', $this->schema) ? $this->schema['order']
           : array());

    foreach ($order as $key) {
      $dir = 'asc';
      if ($key[0] === '+') {
        $key = substr($key, 1);
      }
      elseif ($key[0] === '-') {
        $dir = 'desc';
        $key = substr($key, 1);
      }

      $field = $this->schema['fields'][$key];

      if (array_key_exists('read', $field) && $field['read'] === false) {
        throw new Exception("permission denied, order by '{$key}'");
      }

      $res[] = $this->_build_column($key) . ' ' . $dir;
    }

    if (sizeof($res) === 0) {
      return '';
    }

    return ' order by ' . implode(', ', $res);
  }

  function select ($action=array()) {
    $ret = array();

    // build query
    // base data
    $q = $this->_build_select_query($action);
    $res = $this->db->query($q);
    while ($result = $res->fetch()) {
      if (!$result) {
        return null;
      }

      foreach ($action['fields'] as $key) {
        $field = $this->schema['fields'][$key];

        if (array_key_exists('read', $field) && $field['read'] === false) {
          continue;
        }

        // switch ($field['type'] ?? 'string') { // PHP7
        switch (array_key_exists('type', $field) ? $field['type'] : 'string') {
          case 'string':
            break;
          case 'boolean':
            $result[$key] = (boolean)$result[$key];
            break;
          case 'float':
            $result[$key] = (float)$result[$key];
            break;
          case 'int':
            $result[$key] = (int)$result[$key];
            break;
          case 'sub_table':
            $id = $result[$this->id_field];
            $result[$key] = iterator_to_array($this->sub_tables[$key]->select(array(
              'query' => array(
                array('key' => $field['parent_field'], 'op' => '=', 'value' => $id)
              ),
              // 'old_id' => $action['old_id'] ?? false, // PHP7
              'old_id' => array_key_exists('old_id', $action) ? $action['old_id'] : false,
            )));
            break;
          case 'fun':
            $result[$key] = $field['fun']($result);
            break;
          default:
        }
      }

      yield $result;
    }
  }

  /*
   * @return [string] queries
   */
  function update ($action, $changeset=null) {
    if (!$changeset) {
      $changeset = new DBApiChangeset($this->api);
    }

    $queries = array();

    $update_sub_table = false;

    $set = $this->_build_set($action['update']);

    $query = $this->_build_query($action);

    //if ($update_sub_table) {
      $ids = array();
      $qry = "select {$this->id_field_quoted} as `id` " .
             "from {$this->table_quoted} {$query}";
      $res = $this->db->query($qry);
      while ($elem = $res->fetch()) {
        $ids[] = $elem['id'];
        $changeset->add($this, $elem['id']);
      }
    //}

    if ($set !== '') {
      $qry = "update {$this->table_quoted} set {$set} {$query}";
      $this->db->query($qry);
    }

    foreach ($action['update'] as $key => $d) {
      $field = $this->schema['fields'][$key];

      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        $this->_update_sub_table($ids, $d, $key, $field, $changeset);
      }
    }

    return isset($ids) ? $ids : true;
  }

  function _update_sub_table($ids, $data, $key, $field, $changeset) {
    $sub_table = $this->sub_tables[$key];

    foreach ($ids as $id) {
      $sub_id_field = $sub_table->id_field;
      $sub_old_id_field = $sub_table->old_id_field;

      $current_sub_ids = array_map(
        function ($el) use ($sub_id_field) {
          return $el[$sub_id_field];
        },
        iterator_to_array($this->sub_tables[$key]->select(array(
          'query' => array(array($field['parent_field'], '=', $id)),
          'fields' => array($sub_id_field),
        ), $field))
      );

      foreach ($data as $i1 => $d1) {
        // id field in sub field specified
        if (array_key_exists($sub_id_field, $d1)) {
          $pos_in_current_sub_ids = array_search($d1[$sub_id_field], $current_sub_ids);

          // not yet member of parent object -> add parent_field
          if ($pos_in_current_sub_ids === false) {
            $data[$i1][$field['parent_field']] = $id;
          } else {
            unset($current_sub_ids[$pos_in_current_sub_ids]);
          }
        }
        // old_id field in sub field specified
        if (array_key_exists($sub_old_id_field, $d1)) {
          $pos_in_current_sub_ids = array_search($d1[$sub_old_id_field], $current_sub_ids);

          // not yet member of parent object -> add parent_field
          if ($pos_in_current_sub_ids !== false) {
            unset($current_sub_ids[$pos_in_current_sub_ids]);
          }
        }
        // id field not specified -> new entry, add parent field
        else {
          $data[$i1][$field['parent_field']] = $id;
        }
      }
      $sub_table->insert_update($data, $changeset);

      // delete sub fields which are no longer part of parent field
      foreach ($current_sub_ids as $sub_id) {
        $sub_table->delete(array(
          'id' => $sub_id
        ), $changeset);
      }
    }
  }

  function delete ($action, $changeset=null) {
    if (!array_key_exists('delete', $this->schema) || $this->schema['delete'] !== true) {
      throw new Exception("permission denied, may not delete");
    }

    if (!$changeset) {
      $changeset = new DBApiChangeset($this->api);
    }

    $query = $this->_build_query($action);

    $ids = array();
    $qry = "select {$this->id_field_quoted} as `id` " .
           "from {$this->table_quoted} {$query}";
    $res = $this->db->query($qry);
    while ($elem = $res->fetch()) {
      $ids[] = $elem['id'];
      $changeset->remove($this, $elem['id']);
    }

    $res = $this->db->query("delete from {$this->table_quoted} {$query}");

    return array('count' => $res->rowCount());
  }

  /*
   * @return [string] queries
   */
  function insert_update ($action, $changeset=null) {
    if (!$changeset) {
      $changeset = new DBApiChangeset($this->api);
    }

    $queries = array();

    foreach ($action as $elem) {
      $insert = false;
      $idchange = false;

      if (array_key_exists($this->old_id_field, $elem)) {
        $id = $elem[$this->old_id_field];
        unset($elem[$this->old_id_field]);
      }
      elseif (!array_key_exists($this->id_field, $elem)) {
        $insert = true;
      }
      else {
        $id = $elem[$this->id_field];

        $res = $this->db->query("select 1 from {$this->table_quoted}" .
          " where {$this->id_field_quoted}=" . $this->db->quote($elem[$this->id_field]));
        if (!$res->rowCount()) {
          $insert = true;
        }
        else {
          unset($elem[$this->id_field]);
        }
        $res->closeCursor();
      }

      $set = $this->_build_set($elem);

      if ($insert) {
        if ($set !== '') {
          $this->db->query("insert into {$this->table_quoted} set {$set}");
          $id = $this->db->lastInsertId();
        }
        else {
          $this->db->query("insert into {$this->table_quoted} " .
            '() values ()');
          $id = $this->db->lastInsertId();
        }
        $changeset->add($this, $id);
      }
      else {
        if ($set !== '') {
          $changeset->remove($this, $id);
          $this->db->query(
            "update {$this->table_quoted} " .
            ' set ' . $set .
            " where {$this->id_field_quoted}=" . $this->db->quote($id));
          $id = array_key_exists($this->id_field, $elem) ? $elem[$this->id_field] : $id;
          $changeset->add($this, $id);
        }
      }

      foreach ($elem as $key => $d) {
        $field = $this->schema['fields'][$key];

        if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
          $this->_update_sub_table(array($id), $d, $key, $field, $changeset);
        }
      }

      $ret[] = $id;
    }

    return $ret;
  }

  function addField ($def) {
    $this->schema['fields'][$def['id']] = $def;
  }
}
