<?php
/**
 * @package CMS\Fields\Types\HTML
 */


Core::load('CMS.Fields.Types.ImageList', 'CMS.Redactor', 'Text.Process');

class CMS_Fields_Types_HTML extends CMS_Fields_Types_ImageList implements Core_ModuleInterface {
  const VERSION = '0.1.0';

  
  protected function layout_preprocess($l, $name, $data) {
    $selector = '.' . $this->css_class_name($name, $data);
    $imagelink = !empty($data['imagelist']) ? $data['imagelist'] :
      CMS::$current_controller->field_action_url($name, 'imagelist', $data['__item']);
    $editor = $this->get_editor($name, $data);
    $editor->set_images_link($imagelink);
    $l->redactor->add($editor, $selector);
    // $editor->process_template($l, $selector);
    parent::layout_preprocess($l, $name, $data);
  }
  
  protected function preprocess($template, $name, $data) {
    parent::preprocess($template, $name, $data);
      $class_name = $this->css_class_name($name, $data);
      $parms = $template->parms;
      if (empty($parms['tagparms']['class']) || !Core_Strings::contains('mce', $parms['tagparms']['class']))
        $template->update_parm('tagparms', array('class' => (isset($parms['tagparms']['class'])? $parms['tagparms']['class']:'') . ' ' .  $class_name));
    $template->update_parm('tagparms', array('id' => $name. '-' . $this->url_class()));
  }
  
  protected function css_class_name($name, $data) {
    $id = $this->url_class();
    return "{$id}-$name";
  }
  
  protected function stdunset($data) {
    $res = parent::stdunset($data);
    return $this->punset($res, 'widget', 'redactor', 'imagelist', 'images fields', 'add images', 'valid images extensions', 'allow images field types');
  }

  public function assign_to_object($form, $object, $name, $data)
  {
    parent::assign_to_object($form, $object, $name, $data);
    $value = $object[$name];
    if (!empty($value)) {
      $object[$name] = Text_Process::process($value, 'htmlpurifier');
    }
  }
}