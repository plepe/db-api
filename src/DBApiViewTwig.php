<?php
global $_dbApiViewTwigApi;

register_hook('twig_init', function () {
  global $twig;

  $twig->addFilter(new Twig_SimpleFilter('dbApiGet', function($value, $table) {
    global $_dbApiViewTwigApi;
    
    if ($value === null) {
      return null;
    }

    $result = iterator_to_array_deep($_dbApiViewTwigApi->do(array(array(
      'table' => $table,
      'query' => $value,
    ))));

    if (sizeof($result[0])) {
      return $result[0][0];
    }
  }));
});

class DBApiViewTwig extends DBApiView {
  function __construct ($dbApi, $def, $options=array()) {
    parent::__construct($dbApi, $def, $options);

  }

  function show ($dom, $options=array()) {
    global $_dbApiViewTwigApi;
    $document = $dom->ownerDocument;

    foreach ($this->get() as $entry) {
      $data = array(
        'entry' => $entry
      );

      $newDom=new DOMDocument();
      $_dbApiViewTwigApi = $this->api;
      $newDom->loadHTML("<?xml encoding='UTF-8'><html><body><div>" . twig_render_custom($this->def, $data) . "</div></body></html>");
      $node = $document->importNode($newDom->lastChild->lastChild->lastChild, true);

      $dom->appendChild($node);
    }
  }
}

