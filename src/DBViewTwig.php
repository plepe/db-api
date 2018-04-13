<?php
class DBViewTwig extends DBView {
  function show () {
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

