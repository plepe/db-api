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
      'id' => $value,
    ))));

    if (sizeof($result[0])) {
      return $result[0][0];
    }
  }));
});

class DBApiViewTwig extends DBApiView {
  function __construct ($dbApi, $def, $options=array()) {
    parent::__construct($dbApi, $def, $options);

    if (is_array($this->def['each'])) {
      $this->template = implode("\n", $this->def['each']);
    }
    else {
      $this->template = $this->def['each'];
    }
  }

  function render ($data) {
    return twig_render_custom($this->template, $data);
  }

  function show ($dom, $options=array()) {
    global $_dbApiViewTwigApi;
    $document = $dom->ownerDocument;
    $renderedResult = array();

    foreach ($this->get() as $entry) {
      $data = array(
        'entry' => $entry
      );

      $newDom=new DOMDocument();
      $_dbApiViewTwigApi = $this->api;
      $r = $this->render($data);
      $renderedResult[] = $r;
      $newDom->loadHTML("<?xml encoding='UTF-8'><html><body><div>{$r}</div></body></html>");
      $node = $document->importNode($newDom->lastChild->lastChild->lastChild, true);

      $dom->appendChild($node);

      $this->emit('showEntry', [array(
        'dom' => $node,
        'entry' => $entry,
        'error' => null,
      )]);
    }

    $this->emit('show', [array(
      'result' => $renderedResult,
      'error' => null,
    )]);
  }
}

