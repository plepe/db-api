<?php
class DBApi {
  function __construct ($db, $spec) {
    $this->db = $db;
    $this->spec = $spec;
  }

  function _build_load_query ($query) {
    $select = array();
    foreach ($this->spec['fields'] as $key => $field) {
      if (!array_key_exists('read', $field) || $field['read']) {
        if (array_key_exists('column', $field)) {
          $select[] = $this->db->quoteIdent($field['column']) . ' as ' . $this->db->quoteIdent($key);
        } else {
          $select[] = $this->db->quoteIdent($key);
        }
      }
    }

    $where = array();
    if (is_array($query)) {
      foreach ($query as $q) {
        switch ($q['op'] ?? '=') {
          case '=':
          default:
            $where[] = $this->db->quoteIdent($q['key']) . '=' . $this->db->quote($q['value']);
        }
      }
    }
    else {
      $where[] = $this->db->quoteIdent($this->spec['id_field'] ?? 'id') . '=' . $this->db->quote($query);
    }

    return 'select ' . implode(', ', $select) .
      ' from ' . $this->db->quoteIdent($spec['id']) .
      ' where ' . implode(' and ', $where);

  }

  function load ($query) {
    $ret = array();

    // build query
    // base data
    $res = $this->db->query($this->_build_load_query($query));
    while ($result = $res->fetch()) {
      if (!$result) {
        return null;
      }

      foreach ($this->spec['fields'] as $key => $field) {
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
          default:
        }
      }

      $id = $result[$this->spec['id_field'] ?? 'id'];
      $ret[$id] = $result;
    }

    return $ret;
  }

  function load_overview ($options, $anonym=true) {
    global $db;
    $result = array();

    $limit = '';
    if (array_key_exists('limit', $options) && preg_match("/^\d+$/", $options['limit'])) {
      $limit = "limit {$options['limit']}";
    }

    $offset = '';
    if (array_key_exists('offset', $options) && preg_match("/^\d+$/", $options['offset'])) {
      $offset = "offset {$options['offset']}";
    }

    $select[] = '(select date from map_comments where map_comments.marker=map_markers.id order by date desc limit 1) lastCommentDate';

    if (array_key_exists('dateStart', $options) && $options['dateStart']) {
      $where[] = 'date>=' . $db->quote($options['dateStart']);
    }

    if (array_key_exists('dateEnd', $options) && $options['dateEnd']) {
      $where[] = 'date<=' . $db->quote($options['dateEnd']);
    }

    if (array_key_exists('postcode', $options) && $options['postcode']) {
      $select[] = 'postcode';
      $where[] = 'postcode=' . $db->quote($options['postcode']);
    }

    if (array_key_exists('survey', $options) && $options['survey']) {
      $where[] = 'survey=' . $db->quote($options['survey']);
    }

    if (array_key_exists('status', $options) && $options['status']) {
      $select[] = 'status';
      $where[] = 'status=' . $db->quote($options['status']);
    }

    if (array_key_exists('lastCommentDateStart', $options) && $options['lastCommentDateStart']) {
      $where[] = 'lastCommentDate>=' . $db->quote($options['lastCommentDateStart']);
    }

    if (array_key_exists('lastCommentDateEnd', $options) && $options['lastCommentDateEnd']) {
      $where[] = 'lastCommentDate<=' . $db->quote($options['lastCommentDateEnd']);
    }

    if (array_key_exists('user', $options) && $options['user']) {
      if ($anonym) {
        $userq = "concat(firstname, ' ', substr(name, 1, 1), '.')";
      }
      else {
        $userq = "concat(firstname, ' ', name)";
      }

      $select[] = "(select {$userq} user from map_comments where map_comments.marker=map_markers.id and {$userq} like " . $db->quote("%{$options['user']}%") . 'limit 1) _matchUser';
      $where[] = "_matchUser is not null";
    }

    switch ($options['order'] ?? 'lastComment') {
      case 'id':
        $order = 'order by id desc';
        break;
      case 'likes':
        $select[] = 'likes';
        $order = 'order by likes desc';
        break;
      case 'commentsCount':
        $order = 'order by commentsCount desc';
        break;
      case 'lastComment':
      default:
        $order = 'order by lastCommentDate desc';
    }

    if (sizeof($select)) {
      $select = ', ' . implode(', ', $select);
    }
    else {
      $select = '';
    }

    if (sizeof($where)) {
      $where = 'where ' . implode(' and ', $where);
    }
    else {
      $where = '';
    }

    // base data
    $query = "select * from (select id, date, comments as commentsCount, lat, lng, survey $select from map_markers) t {$where} {$order} {$limit} {$offset}";
    //print $query;
    $res = $db->query($query);
    return $res->fetchAll();
  }

  /*
   * @return [string] queries
   */
  function update_data_struct ($entries, $struct) {
    global $db;
    $queries = array();

    foreach ($entries as $entry) {
      $set = array();

      if (!array_key_exists('id', $entry)) {
        return false;
      }

      foreach ($entry as $k => $d) {
        if ($k === 'id') {
          continue;
        }

        if (array_key_exists('sub_tables', $struct) &&
            array_key_exists($k, $struct['sub_tables'])) {
          $q = update_data_struct($d, $struct['sub_tables'][$k]);

          if (!is_array($q)) {
            return $q;
          }

          $queries = array_merge($queries, $q);

          continue;
        }

        if (!in_array($k, $struct['may_update'])) {
          return "may not update {$k}";
        }

        $set[] = $db->quoteIdent($k) . '=' . $db->quote($d);
      }

      if (sizeof($set)) {
        $queries[] = "update {$struct['table']} set " . implode(', ', $set) . ' where id=' . $db->quote($entry['id']);
      }
    }

    return $queries;
  }

  function update_data ($data) {
    global $db;
    global $rights;

    $queries = update_data_struct($data, $rights['marker_rights']);

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
