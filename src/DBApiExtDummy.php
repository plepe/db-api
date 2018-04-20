<?php
class DBApiExtDummy extends DBApiExt {
  function __construct ($view, $options=array()) {
    parent::__construct($view, $options);

    $view->on('showEntry', function ($ev) {
      $document = $ev['dom']->ownerDocument;
      $div = $document->createElement('div');
      $div->appendChild($document->createTextNode($this->options['text'] ?? 'DUMMY'));
      $ev['dom']->appendChild($div);
    });
  }
}
