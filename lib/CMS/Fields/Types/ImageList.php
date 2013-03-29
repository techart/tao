<?php

Core::load('CMS.Fields');

class CMS_Fields_Types_ImageList extends CMS_Fields_AbstractField implements Core_ModuleInterface {
  const VERSION = '0.1.0';

  protected function get_widget($data) {
    return $widget = empty($data['widget']) ? 'tiny_mce' : $data['widget'];
  }

  public function valid_extensions($name, $data) {
    return array_merge(array('jpg','jpeg','gif','png','bmp'), !empty($data['valid images extensions']) ?
        $data['valid images extensions'] : array());
  }

  public function allow_images_field_types($name, $data) {
  	return isset($data['allow images field types']) ? $data['allow images field types'] : array('attaches', 'image', 'gallery');
  }

  public function action($name, $data, $action, $item = false, $fields = array()) {
    if (!empty($action) && method_exists($this, $method = 'action_' . $action)) {
      $args = func_get_args();
      return call_user_func_array(array($this, $method), $args);
    }
    return false;
  }
  
  protected function action_imagelist($name, $data, $action, $item = false, $fields = array()) {
    $image_fields = array();
    if (!empty($data['images fields']))
      $image_fields = $data['images fields'];
    else {
      foreach ($fields as $name => $f)
        if (in_array($f['type'], $this->allow_images_field_types($name, $data)))
          $image_fields[] = $name;
    }
    $res = array();
    foreach ($image_fields as $name) {
    $field = $fields[$name];
    $type_object = CMS_Fields::type($field);
    if (method_exists($this, $method = 'imagelist_from_' . $field['type']))
      $this->$method($res, $name, $field, $type_object, $item);
    }
    foreach ($res as &$f) $f = trim($f, ' .');
    if (!empty($data['add images'])) $res = array_merge($res, $data['add images']);
    return $this->get_widget($data) == 'tiny_mce' ? $this->mce_imagelist($res) : json_encode($res);
  }
  
  protected function imagelist_from_attaches(&$res, $name, $field, $type_object, $item) {
    $files = $type_object->filelist($type_object->dir_path($item,$type_object->temp_code(),$name,$field));
    foreach ($files as $f) {
      if (in_array(pathinfo($f, PATHINFO_EXTENSION), $this->valid_extensions($name, $data)))
        $res[] = trim($f, '.');
    }
  }
  
  protected function imagelist_from_gallery(&$res, $name, $field, $type_object, $item) {
    $res = array_merge($res,$type_object->container($name, $field, $item)->filelist());
  }
  
  protected function imagelist_from_image(&$res, $name, $field, $type_object, $item) {
    $res[] = $type_object->container($name, $field, $item)->url();
  }
  
  protected function mce_imagelist($files) {
    $res = 'var tinyMCEImageList = new Array(';
    $list = array();
    foreach ($files as $f) {
      $name = pathinfo($f, PATHINFO_BASENAME);
      $list[] = "[" . "'$name'" . ',' . "'$f'" .  "]";
    }
    $res .= implode(',', $list) .  ');';
    return $res;
  }
}