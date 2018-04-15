<?php
function emptyElement ($dom) {
  while ($dom->firstChild) {
    $dom->removeChild($dom->firstChild);
  }
}
