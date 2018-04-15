<?php
class DBApiViewJSON extends DBApiView {
  function show ($dom) {
    $document = $dom->ownerDocument;
    emptyElement($dom);
    $dom->appendChild($document->createTextNode(json_readable_encode(iterator_to_array_deep($this->get()))));
  }
}

