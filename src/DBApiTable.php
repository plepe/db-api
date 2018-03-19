<?php
class DBApiTable {
  function __construct ($db, $spec) {
    $this->db = $db;
    $this->spec = $spec;
    $this->id = $this->spec['id'];
    $this->sub_tables = array();

    foreach ($this->spec['fields'] as $key => $field) {
      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        $this->sub_tables[$key] = new DBApiTable($this->db, $field);
      }
    }

    if (!array_key_exists('table', $this->spec)) {
      $this->spec['table'] = $this->spec['id'];
    }
  }


  function _prepare_options (&$options) {
    if (!array_key_exists('fields', $options)) {
      $options['fields'] = array_keys($this->spec['fields']);
    }

    if (!in_array($this->spec['id_field'] ?? 'id', $options['fields'])) {
      $options['fields'][] = $this->spec['id_field'] ?? 'id';
    }
  }

  function _build_where ($options=array()) {
    $this->_prepare_options($options);

    $where = array();
    if (!array_key_exists('query', $options) || ($options['query'] === null)) {
      $where[] = 'true';
    }
    elseif (is_array($options['query'])) {
      foreach ($options['query'] as $q) {
        if (is_array($q) && !array_key_exists('key', $q) && array_key_exists(0, $q)) {
          $q = array('key' => $q[0], 'op' => $q[1], 'value' => sizeof($q) > 2 ? $q[2] : null);
        }

        switch ($q['op'] ?? '=') {
          case 'in':
            $where[] = $this->db->quoteIdent($q['key']) . ' in (' . implode(', ', array_map(function ($v) { return $this->db->quote($v); }, $q['value'])) . ')';
            break;
          case '=':
          default:
            $where[] = $this->db->quoteIdent($q['key']) . '=' . $this->db->quote($q['value']);
        }
      }
    }
    else {
      $where[] = $this->db->quoteIdent($this->spec['id_field'] ?? 'id') . '=' . $this->db->quote($options['query']);
    }

    if (array_key_exists('query-visible', $this->spec)) {
      $where[] = $this->spec['query-visible'];
    }

    $limit_offset = '';
    if (array_key_exists('limit', $options) && is_int($options['limit'])) {
      $limit_offset .= " limit {$options['limit']}";
    }
    if (array_key_exists('offset', $options) && is_int($options['offset'])) {
      $limit_offset .= " offset {$options['offset']}";
    }

    return ' where ' . implode(' and ', $where) . $limit_offset;
  }

  function _build_set ($data) {
    $set = array();

    foreach ($data as $key => $d) {
      $field = $this->spec['fields'][$key];

      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        $update_sub_table = false;
        continue;
      }

      if (!array_key_exists('write', $field) || $field['write'] === false) {
        throw new Exception('permission denied');
      }

      $set[] = $this->db->quoteIdent($field['column'] ?? $key) . '=' . $this->db->quote($d);
    }

    if (sizeof($set)) {
      return implode(', ', $set);
    }
    return '';
  }

  function _build_load_query ($options=array()) {
    $this->_prepare_options($options);

    $select = array();
    foreach ($options['fields'] as $key) {
      $field = $this->spec['fields'][$key];

      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        continue;
      }

      if (!array_key_exists('read', $field) || $field['read']) {
        if (array_key_exists('select', $field)) {
          $select[] = "({$field['select']}) as " . $this->db->quoteIdent($key);
        }
        else if (array_key_exists('column', $field)) {
          $select[] = $this->db->quoteIdent($field['column']) . ' as ' . $this->db->quoteIdent($key);
        } else {
          $select[] = $this->db->quoteIdent($key);
        }
      }
    }

    return 'select ' . implode(', ', $select) .
      ' from ' . $this->db->quoteIdent($this->spec['table']) .
      $this->_build_where($options, $this->spec);
  }

  function load ($options=array()) {
    $ret = array();

    $this->_prepare_options($options);

    // build query
    // base data
    $q = $this->_build_load_query($options);
    $res = $this->db->query($q);
    while ($result = $res->fetch()) {
      if (!$result) {
        return null;
      }

      foreach ($options['fields'] as $key) {
        $field = $this->spec['fields'][$key];

        if (array_key_exists('read', $field) && $field['read'] === false) {
          continue;
        }

        switch ($field['type'] ?? 'string') {
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
            $id = $result[$this->spec['id_field'] ?? 'id'];
            $result[$key] = iterator_to_array($this->sub_tables[$key]->load(array('query' => array(array('key' => $field['parent_field'], 'op' => '=', 'value' => $id)))));
          default:
        }
      }

      yield $result;
    }
  }

  /*
   * @return [string] queries
   */
  function _update_data ($data) {
    global $db;
    $queries = array();

    $id_field = $this->spec['id_field'] ?? 'id';

    $update_sub_table = false;

    $set = $this->_build_set($data['update']);

    //if ($update_sub_table) {
      $ids = array();
      $qry = 'select ' . $this->db->quoteIdent($id_field) . ' as `id` from ' .
        $this->db->quoteIdent($this->spec['table']) . $this->_build_where($data);
      $res = $this->db->query($qry);
      while ($elem = $res->fetch()) {
        $ids[] = $elem['id'];
      }
    //}

    if ($set !== '') {
      $qry = 'update ' .
        $this->db->quoteIdent($this->spec['table']) .
        ' set ' . $set .
        $this->_build_where($data);
      $this->db->query($qry);
    }

    foreach ($data['update'] as $key => $d) {
      $field = $this->spec['fields'][$key];

      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        $this->_update_sub_table($ids, $d, $key, $field);
      }
    }

    return isset($ids) ? $ids : true;
  }

  function _update_sub_table($ids, $data, $key, $field) {
    foreach ($ids as $id) {
      $sub_id_field = $field['id_field'] ?? 'id';

      $current_sub_ids = array_map(
        function ($el) use ($sub_id_field) {
          return $el[$sub_id_field];
        },
        iterator_to_array($this->sub_tables[$key]->load(array(
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
        // id field not specified -> new entry, add parent field
        else {
          $data[$i1][$field['parent_field']] = $id;
        }
      }
      $q = $this->sub_tables[$key]->insert_update($data);

      // delete sub fields which are no longer part of parent field
      foreach ($current_sub_ids as $sub_id) {
        $this->sub_tables[$key]->delete(array(
          'query' => $sub_id
        ));
      }

      if (!is_array($q)) {
        return $q;
      }
    }
  }

  function delete ($action) {
    $id_field = $this->spec['id_field'] ?? 'id';

    $res = $this->db->query('delete from ' . $this->db->quoteIdent($this->spec['table']) .
      $this->_build_where($action));

    return array('count' => $res->rowCount());
  }

  /*
   * @return [string] queries
   */
  function insert_update ($data) {
    global $db;
    $queries = array();

    $id_field = $this->spec['id_field'] ?? 'id';

    foreach ($data as $id => $elem) {
      $insert = false;

      if (!array_key_exists($id_field, $elem)) {
        $insert = true;
      }
      else {
        $id = $elem[$id_field];

        $res = $db->query('select 1 from ' . $db->quoteIdent($this->spec['table']) . ' where ' . $db->quoteIdent($id_field) . '=' . $db->quote($elem[$id_field]));
        if (!$res->rowCount()) {
          $insert = true;
        }
        $res->closeCursor();
      }

      $set = $this->_build_set($elem);

      if ($insert) {
        if ($set !== '') {
          $this->db->query('insert into ' .
            $this->db->quoteIdent($this->spec['table']) .
            ' set ' . $set);
          $id = $this->db->lastInsertId();
        }
        else {
          $this->db->query('insert into ' .
            $this->db->quoteIdent($this->spec['table']) .
            '() values ()');
          $id = $this->db->lastInsertId();
        }
      }
      else {
        $this->db->query(
          'update ' .  $this->db->quoteIdent($this->spec['table']) .
          ' set ' . $set .
          ' where ' . $db->quoteIdent($id_field) . '=' . $db->quote($id));
        $id = array_key_exists($id_field, $elem) ? $elem[$id_field] : $id;
      }

      foreach ($elem as $key => $d) {
        $field = $this->spec['fields'][$key];

        if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
          $this->_update_sub_table(array($id), $d, $key, $field);
        }
      }

      $ret[] = $id;
    }

    return $ret;
  }

  function update ($data) {
    global $db;

    $db->beginTransaction();

    $ret = $this->_update_data ($data);

    $db->commit();

    return $ret;
  }
}
