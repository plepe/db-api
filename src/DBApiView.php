<?php
$dbApiViewExtensions = array(
  'Dummy' => 'DBApiExtDummy',
);

class DBApiView extends Evenement\EventEmitter {
  function __construct ($dbApi, $def, $options=array()) {
    $this->api = $dbApi;
    $this->def = $def;
    $this->options = $options;
    $this->extensions = array();

    if (array_key_exists('extensions', $this->def)) {
      foreach ($this->def['extensions'] as $ext) {
        $this->extend($ext, $options);
      }
    }
  }

  function extend ($def, $options=array()) {
    global $dbApiViewExtensions;
    $this->extensions[] = new $dbApiViewExtensions[$def['type']]($this, $def, $options);
  }

  function set_query ($query) {
    $this->query = $query;
  }

  function get () {
    return iterator_to_array($this->api->do(array($this->query)))[0];
  }

  function show ($dom, $options=array()) {
    $document = $dom->ownerDocument;
    emptyElement($dom);
    $dom->appendChild($document->createTextNode(print_r(iterator_to_array_deep($this->get()), 1)));
  }
}
