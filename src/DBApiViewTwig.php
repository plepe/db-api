<?php
class DBApiViewTwig extends DBApiView {
  function show ($dom, $options=array()) {
    $document = $dom->ownerDocument;

    foreach ($this->get() as $entry) {
      $data = array(
        'entry' => $entry
      );

      $newDom=new DOMDocument();
      $newDom->loadHTML("<?xml encoding='UTF-8'><html><body><div>" . twig_render_custom($this->def, $data) . "</div></body></html>");
      $node = $document->importNode($newDom->lastChild->lastChild->lastChild, true);

      $dom->appendChild($node);
    }
  }
}

