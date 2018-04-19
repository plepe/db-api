<?php
class DBApiViewModulekitForm extends DBApiView {
  function show ($dom, $options=array()) {
    $ret = '';

    foreach ($this->get() as $entry) {
      $data = array(
        'entry' => $entry
      );

      $ret .= twig_render_custom($this->def, $data);
    }

    return $ret;
  }
}

