<?php
class DBApi {
  function __construct ($db, $spec) {
    $this->db = $db;
    $this->spec = $spec;
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

  function _build_load_query ($options=array(), $spec=null) {
    if ($spec === null) {
      $spec = $this->spec;
    }

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

    $where = array();
    if (!array_key_exists('query', $options) || ($options['query'] === null)) {
      $where[] = 'true';
    }
    elseif (is_array($options['query'])) {
      foreach ($options['query'] as $q) {
        switch ($q['op'] ?? '=') {
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

    return 'select ' . implode(', ', $select) .
      ' from ' . $this->db->quoteIdent($spec['id']) .
      ' where ' . implode(' and ', $where) .
      $limit_offset;
  }

  function load ($options=array(), $spec=null) {
    $ret = array();

    if ($spec === null) {
      $spec = $this->spec;
    }

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

        if (array_key_exists('read', $field) && is_callable($field['read'], false, $callable_name)) {
          $result[$key] = call_user_func($callable_name, $result[$key], $this);
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
            $result[$key] = $this->load(array('query' => array(array('key' => $field['parent_field'], 'op' => '=', 'value' => $id))), $field);
          default:
        }
      }

      $id = $result[$spec['id_field'] ?? 'id'];
      $ret[$id] = $result;
    }

    return $ret;
  }
  /*
   * @return [string] queries
   */
  function _update_data ($data, $spec) {
    global $db;
    $queries = array();

    foreach ($data as $id => $elem) {
      $set = array();

      foreach ($elem as $key => $d) {
        $field = $spec['fields'][$key];

        if (array_key_exists('write', $field) && $field['write'] === false) {
          return 'permission denied';
        }

        if (array_key_exists('select', $field)) {
          return 'can\'t update "select" field';
        }

        if (array_key_exists('type', $field) && $field['type'] === 'sub_table') {
          $q = $this->_update_data($d, $field);

          if (!is_array($q)) {
            return $q;
          }

          $queries = array_merge($queries, $q);
          continue;
        }

        $set[] = $db->quoteIdent($field['column'] ?? $key) . '=' . $db->quote($d);
      }

      if (sizeof($set)) {
        $queries[] = 'update ' .
          $this->db->quoteIdent($spec['id']) .
          ' set ' . implode(', ', $set) .
          ' where ' . ($spec['id_field'] ?? 'id') . '=' . $db->quote($id);
      }
    }

    return $queries;
  }

  function save ($data) {
    global $db;

    $queries = $this->_update_data ($data, $this->spec);

    if (!is_array($queries)) {
      return $queries;
    }

    $db->beginTransaction();
    foreach ($queries as $query) {
      $db->query($query);
    }
    $db->commit();

    return $queries;
  }
}
