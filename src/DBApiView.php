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
  }

  function extend ($type, $def, $options=array()) {
    global $dbApiViewExtensions;
    $this->extensions[] = new $dbApiViewExtensions[$type]($this, $def, $options);
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
