<?php
class DBApiViewJSON extends DBApiView {
  function show () {
    return json_readable_encode(iterator_to_array_deep($this->get()));
  }
}

