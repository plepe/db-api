<?php
class DBApiExt {
  function __construct ($view, $options=array()) {
    $this->view = $view;
    $this->api = $view->api;
    $this->options = $options;
  }
}
