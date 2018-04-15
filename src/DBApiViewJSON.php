<?php
class DBApiViewJSON extends DBApiView {
  function show ($dom, $options=array()) {
    $document = $dom->ownerDocument;
    emptyElement($dom);
    $dom->appendChild($document->createTextNode(json_readable_encode(iterator_to_array_deep($this->get()))));
  }
}

