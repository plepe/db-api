<?php
class DBViewJSON extends DBView {
  function show () {
    return json_readable_encode(iterator_to_array_deep($this->get()));
  }
}

