<?php
class DBApiTable {
  function __construct ($db, $spec) {
    $this->db = $db;
    $this->spec = $spec;
    $this->id = $this->spec['id'];
  }

  function _prepare_options (&$options, $spec=null) {
    if ($spec === null) {
      $spec = $this->spec;
    }

    if (!array_key_exists('fields', $options)) {
      $options['fields'] = array_keys($spec['fields']);
    }

    if (!in_array($spec['id_field'] ?? 'id', $options['fields'])) {
      $options['fields'][] = $spec['id_field'] ?? 'id';
    }
  }

  function _prepare_spec (&$spec) {
    if (!array_key_exists('table', $spec)) {
      $spec['table'] = $spec['id'];
    }
  }

  function _build_where ($options=array(), $spec=null) {
    if ($spec === null) {
      $spec = $this->spec;
    }

    $this->_prepare_spec($spec);
    $this->_prepare_options($options, $spec);

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
      $where[] = $this->db->quoteIdent($spec['id_field'] ?? 'id') . '=' . $this->db->quote($options['query']);
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

  function _build_load_query ($options=array(), $spec=null) {
    if ($spec === null) {
      $spec = $this->spec;
    }

    $this->_prepare_spec($spec);
    $this->_prepare_options($options, $spec);

    $select = array();
    foreach ($options['fields'] as $key) {
      $field = $spec['fields'][$key];

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
      ' from ' . $this->db->quoteIdent($spec['table']) .
      $this->_build_where($options, $spec);
  }

  function load ($options=array(), $spec=null) {
    $ret = array();

    if ($spec === null) {
      $spec = $this->spec;
    }

    $this->_prepare_spec($spec);
    $this->_prepare_options($options, $spec);

    // build query
    // base data
    $q = $this->_build_load_query($options, $spec);
    $res = $this->db->query($q);
    while ($result = $res->fetch()) {
      if (!$result) {
        return null;
      }

      foreach ($options['fields'] as $key) {
        $field = $spec['fields'][$key];

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
            $id = $result[$spec['id_field'] ?? 'id'];
            $result[$key] = iterator_to_array($this->load(array('query' => array(array('key' => $field['parent_field'], 'op' => '=', 'value' => $id))), $field));
          default:
        }
      }

      yield $result;
    }
  }

  /*
   * @return [string] queries
   */
  function _update_data ($data, $spec) {
    global $db;
    $queries = array();
    $this->_prepare_spec($spec);

    $id_field = $spec['id_field'] ?? 'id';

    $set = array();
    $update_sub_table = false;

    foreach ($data['update'] as $key => $d) {
      $field = $spec['fields'][$key];

      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        $update_sub_table = false;
        continue;
      }

      if (!array_key_exists('write', $field) || $field['write'] === false) {
        return 'permission denied';
      }

      $set[] = $db->quoteIdent($field['column'] ?? $key) . '=' . $db->quote($d);
    }

    //if ($update_sub_table) {
      $ids = array();
      $qry = 'select ' . $this->db->quoteIdent($id_field) . ' as `id` from ' .
        $this->db->quoteIdent($spec['table']) . $this->_build_where($data, $spec);
      $res = $this->db->query($qry);
      while ($elem = $res->fetch()) {
        $ids[] = $elem['id'];
      }
    //}

    if (sizeof($set)) {
      $qry = 'update ' .
        $this->db->quoteIdent($spec['table']) .
        ' set ' . implode(', ', $set) .
        $this->_build_where($data, $spec);
      $this->db->query($qry);
    }

    foreach ($data['update'] as $key => $d) {
      $field = $spec['fields'][$key];

      if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
        foreach ($ids as $id) {
          foreach ($d as $i1 => $d1) {
            $d[$i1][$field['parent_field']] = $id;
          }
          $q = $this->insert_update($d, $field);

          if (!is_array($q)) {
            return $q;
          }
        }
      }
    }

    return isset($ids) ? $ids : true;
  }

  /*
   * @return [string] queries
   */
  function insert_update ($data, $spec=null) {
    global $db;
    $queries = array();

    if ($spec === null) {
      $spec = $this->spec;
    }
    $this->_prepare_spec($spec);

    $id_field = $spec['id_field'] ?? 'id';

    foreach ($data as $id => $elem) {
      $set = array();
      $insert = false;

      if ($elem[$id_field] === '__new') {
        unset($elem[$id_field]);
        $insert = true;
      }
      else {
        $id = $elem[$id_field];
        unset($elem[$id_field]);
      }

      foreach ($elem as $key => $d) {
        $field = $spec['fields'][$key];

        if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
          continue;
        }

        if (!array_key_exists('write', $field) || $field['write'] === false) {
          return 'permission denied';
        }

        $set[] = $db->quoteIdent($field['column'] ?? $key) . '=' . $db->quote($d);
      }

      if ($insert) {
        if (sizeof($set)) {
          $this->db->query('insert into ' .
            $this->db->quoteIdent($spec['table']) .
            ' set ' . implode(', ', $set));
          $id = $this->db->lastInsertId();
        }
        else {
          $this->db->query('insert into ' .
            $this->db->quoteIdent($spec['table']) .
            '() values ()');
          $id = $this->db->lastInsertId();
        }
      }
      else {
        if (sizeof($set)) {
          $this->db->query('update ' .
            $this->db->quoteIdent($spec['table']) .
            ' set ' . implode(', ', $set) .
            ' where ' . $db->quoteIdent($id_field) . '=' . $db->quote($id));
        }
        $id = array_key_exists($id_field, $elem) ? $elem[$id_field] : $id;
      }

      foreach ($elem as $key => $d) {
        $field = $spec['fields'][$key];

        if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
          foreach ($d as $i1 => $d1) {
            $d[$i1][$field['parent_field']] = $id;
          }
          $q = $this->insert_update($d, $field);

          if (!is_array($q)) {
            return $q;
          }
        }
      }

      $ret[] = $id;
    }

    return $ret;
  }

  function update ($data) {
    global $db;

    $db->beginTransaction();

    $ret = $this->_update_data ($data, $this->spec);

    $db->commit();

    return $ret;
  }
}
