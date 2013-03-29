<?php

//TODO: виджеты кроме tinyMCE + возможность задовать произвольный виджет
//TODO: возможность переключения между виджетами

Core::load('CMS.Fields.Types.ImageList');

class CMS_Fields_Types_HTML extends CMS_Fields_Types_ImageList implements Core_ModuleInterface {
  const VERSION = '0.1.0';
  
  protected function layout_preprocess($l, $name, $data) {
    $widget = $this->get_widget($data);
    if ($widget != 'textarea') {
      if (empty($data['attach']['js'][$widget]))
        $data['attach']['js'][$widget] = "{$widget}/{$widget}.js";
      if (empty($data['attach']['js']["{$widget}_init"])) {
        $fname = "{$widget}_initialize.js";
        if (!IO_FS::exists('.' . Templates_HTML::js_path($fname)))
          $file = '/' . CMS::stdfile('scripts/' . $fname);
        else
          $file = $fname;
        $data['attach']['js']["{$widget}_init"] = $file;
      }
    }
    $class = $this->css_class_name($name, $data);
    //$class = 'mce';
    $imagelink = !empty($data['imagelist']) ? $data['imagelist'] :
      CMS::$current_controller->field_action_url($name, 'imagelist', $data['__item']);
    $code = "\n init_mce({ editor_selector: '$class', external_image_list_url: '$imagelink' });\n";
    $l->append_to('js', $code); 
    //$l->with('url_class', $this->url_class());
    parent::layout_preprocess($l, $name, $data);
  }
  
  protected function preprocess($template, $name, $data) {
    parent::preprocess($template, $name, $data);
    if ($this->get_widget($data) == 'tiny_mce') {
      $class_name = 'mce-advanced ' . $this->css_class_name($name, $data);
      $parms = $template->parms;
      if (empty($parms['tagparms']['class']) || !Core_Strings::contains('mce', $parms['tagparms']['class']))
        $template->update_parm('tagparms', array('class' => (isset($parms['tagparms']['class'])? $parms['tagparms']['class']:'') . ' ' .  $class_name));
      $template->update_parm('tagparms', array('id' => $name. '-' . $this->url_class()));
    }
  }
  
  protected function css_class_name($name, $data) {
    if ($this->get_widget($data) == 'tiny_mce') {
      $id = $this->url_class();
      return "{$id}-mce-$name";
    }
    return '';
  }
  
  protected function stdunset($data) {
    $res = parent::stdunset($data);
    return $this->punset($res, 'widget', 'imagelist', 'images fields', 'add images', 'valid images extensions', 'allow images field types');
  }
}