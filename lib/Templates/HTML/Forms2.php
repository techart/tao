<?php
/// <module name="WebKit.Helpers.Forms" version="1.0.0" maintainer="timokhin@techart.ru">

Core::load('Templates.HTML', 'Templates.HTML.Tags');

/// <class name="WebKit.Helpers.Forms" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="WebKit.Views.HelperInterface" />
class Templates_HTML_Forms2 implements Core_ModuleInterface, Templates_HelperInterface  {

///   <constants>
  const VERSION = '1.0.0';
///   </constants>

  protected $form;

  protected $days;
  protected $months;
  protected $hours;
  protected $minutes;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <body>
  static public function initialize() {
    Templates_HTML::use_helper('tags', 'Templates.HTML.Tags');
    Templates_HTML::use_helper('forms', 'Templates.HTML.Forms2');
  }
///     </body>
///   </method>

///   <method name="__construct">
///     <body>
  public function __construct() {
    for ($i = 1; $i <= 31; $i++) $this->days[$i]    = sprintf('%02d', $i);
    for ($i = 1; $i <= 12; $i++) $this->months[$i]  = sprintf('%02d', $i);
    for ($i = 0; $i <  24; $i++) $this->hours[$i]   = sprintf('%02d', $i);
    for ($i = 0; $i <  60; $i++) $this->minutes[$i] = sprintf('%02d', $i);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="generating">

///   <method name="begin_form" returns="string">
///     <args>
///       <arg name="form" type="WebKit.Forms.Form" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function begin_form($t, $form, array $attributes = array()) {
    $this->form   = $form;
    $method       = $form->options['method'];

    $result = $t->tags->tag('form', Core_Arrays::merge(array(
      'action'   => $form->action,
      'method'   => ($method == 'post' || $method == 'get' ? $method : 'post'),
      'id'       => "{$form->name}_form",
      'enctype'  => $form->options['enctype']), $attributes), false)."\n";

    if ($method == 'put' || $method == 'delete')
      $result .= $t->tags->tag('input', array(
        'type'  => 'hidden',
        'name'  => '_method',
        'value' => $method ))."\n";

    return $result;
  }
///     </body>
///   </method>

///   <method name="begin_form_tag" returns="string">
///     <args>
///       <arg name="action" type="string" />
///       <arg name="method" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function begin_form_tag($t, $action, $method, array $attributes = array()) {

    $add_method_field = Core_Arrays::pick($attributes, 'add_method_field', false);

    $result = $t->tags->tag('form', Core_Arrays::merge(array(
      'action' => $action,
      'method' => ($method == 'post' || $method == 'get' ? $method : 'post')), $attributes), false)."\n";

    if ($method == 'put' || $method == 'delete' || $add_method_field)
      $result .= $t->tags->tag('input', array(
        'type'  => 'hidden',
        'name'  => '_method',
        'value' => $method ))."\n";

    return $result;
  }
///     </body>
///   </method>

///   <method name="label_tag" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="text" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function label_tag($t, $name, $text, array $attributes = array()) {
    return $t->tags->content_tag('label', $text, array('for' => $name) + $attributes);
  }
///     </body>
///   </method>

///   <method name="label" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="text" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function label($t, $name, $text, array $attributes = array()) {
    return $this->label_tag($t, $this->field_id_for($name), $text, $attributes);
  }
///     </body>
///   </method>

///   <method name="input" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function input($t, $name, array $attributes = array()) {
    $this->add_error_class($name, $attributes);
    return
      (($label = Core_Arrays::pick($attributes, 'label', false)) ?
        $this->label($name, $label).' ' : '').
      $t->tags->
        tag('input', Core_Arrays::merge(array(
         'type'  => 'text',
         'name'  => $this->field_name_for($name),
         'value' => $this->form[$name],
         'id'    => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>


///   <method name="password" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function password($t, $name, array $attributes = array()) {
    $this->add_error_class($name, $attributes);
    return $t->tags->
      tag('input', Core_Arrays::merge(array(
        'type' => 'password',
        'name' => $this->field_name_for($name),
        'id'   => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="upload" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function upload($t, $name, array $attributes = array()) {
    $this->add_error_class($name, $attributes);
    return
      (($label = Core_Arrays::pick($attributes, 'label', false)) ?
        $this->label($name, $label).' ' : '').
      $t->tags->tag('input', Core_Arrays::merge(array(
        'type' => 'file',
        'name' => $this->field_name_for($name),
        'id'   => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="textarea" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function textarea($t, $name, array $attributes = array()) {
    $this->add_error_class($name, $attributes);
    return
      (($label = Core_Arrays::pick($attributes, 'label', false)) ?
        $this->label($name, $label).' ' : '').
      $t->tags->
        content_tag('textarea', htmlspecialchars($this->form[$name]), Core_Arrays::merge(array(
         'name'  => $this->field_name_for($name),
         'id'    => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="checkbox" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function checkbox($t, $name, $attributes = array())  {
    $this->add_error_class($name, $attributes);
    $checkbox = $t->tags->
      tag('input', Core_Arrays::merge(array(
        'type'    => 'checkbox',
        'value'   => 1,
        'checked' => $this->form[$name] ? true : false,
        'name'    => $this->field_name_for($name),
        'id'      => $this->field_id_for($name)), $attributes));

    return ($label = Core_Arrays::pick($attributes, 'label')) ?
      $this->label($name, "$checkbox $label") :
      $checkbox;
  }
///     </body>
///   </method>

///   <method name="hidden" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function hidden($t, $name, $attributes = array()) {
    return $t->tags->
      tag('input', Core_Arrays::merge(array(
        'type'  => 'hidden',
        'name'  => $this->field_name_for($name),
        'value' => (string) $this->form[$name],
        'id'    => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="select_tag" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="items" />
///       <arg name="selected" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function select_tag($t, $name, $items, $selected, array $attributes = array()) {
    $options    = array();
    $allow_null = false;

    $this->add_error_class($name, $attributes);

    foreach ($items as $k => $v)
      $options[]  = $t->tags->content_tag('option',
        $v,
        array('value' => $k, 'selected' => ($selected !== null && $selected == $k)));

    return $t->tags->
      content_tag('select',
        (($allow_null = Core_Arrays::pick($attributes, 'allow_null', false)) ? $t->tags->content_tag('option', '', array('value' => '')) : '').
        Core_Arrays::join_with(' ', $options),
        Core_Arrays::merge(array('name' => $name), $attributes));
  }
///     </body>
///   </method>

///   <method name="object_select" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function object_select($t, $name, array $attributes = array()) {
    $options    = array();
    $field      = $this->form->fields[$name];
    $key        = $field->key;
    $attribute  = $field->attribute;

    $this->add_error_class($name, $attributes);

    foreach ($field->items as $item)
      $options[] = $t->tags->
        content_tag('option',
          (string) $item->$attribute,
          array(
            'value'    => $item->$key,
            'selected' => $this->form[$name]->$key == $item->$key));

    return
      (($label = Core_Arrays::pick($attributes, 'label', false)) ? $this->label($name, $label).' ' : '').
      $t->tags->
        content_tag('select',
          (($allow_null = Core_Arrays::pick($attributes, 'allow_null', false)) ? $t->tags->content_tag('option', '') : '').
          Core_Arrays::join_with('', $options),
          Core_Arrays::merge(array(
            'name' => $this->field_name_for($name),
            'id'   => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="input_tag" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function input_tag($t, $name, $value, array $attributes = array()) {
    return $t->tags->tag('input',
      Core_Arrays::merge(array(
        'type'  => 'text',
        'name'  => $name,
        'value' => $value ), $attributes));
  }
///     </body>
///   </method>

///   <method name="datetime_select" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function datetime_select($t, $name, $attributes = array()) {
    $value     = $this->form[$name];
    $show_time = Core_Arrays::pick($attributes, 'show_time', false);

    $this->add_error_class($name, $attributes);

    return
      (($label = Core_Arrays::pick($attributes, 'label', false)) ? $this->label_tag($this->field_id_for($name).'_day', $label).' ' : '').
      $this->select_tag(
        $this->field_name_for($name).'[day]',
        $this->days,
        $value ? $value->day : null,
        Core_Arrays::merge(
          array('id' => $this->field_id_for($name).'_day'),
          isset($attributes['day']) ?  Core_Arrays::merge($attributes, (array)$attributes['day']) : $attributes)).' '.
      $this->select_tag(
        $this->field_name_for($name).'[month]',
        $this->months,
        $value ? $value->month : null,
        Core_Arrays::merge(
          array('id' => $this->field_id_for($name).'_month'),
          isset($attributes['month']) ?  Core_Arrays::merge($attributes, (array)$attributes['month']) : $attributes)).' '.
      $this->input_tag(
        $this->field_name_for($name).'[year]',
        $value && $value->year > 0 ? $value->year : '',
        Core_Arrays::merge(
          array('id' => $this->field_id_for($name).'_year', 'size' => 4),
          isset($attributes['year']) ?  Core_Arrays::merge($attributes, (array)$attributes['year']) : $attributes)).' '.
      ($show_time ?
        $this->select_tag(
          $this->field_name_for($name).'[hour]',
          $this->hours,
          $value ? $value->hour : null,
        Core_Arrays::merge(
          array('id' => $this->field_id_for($name).'_hour'),
          isset($attributes['hour']) ?  Core_Arrays::merge($attributes, (array)$attributes['hour']) : $attributes)).' '.
        $this->select_tag(
          $this->field_name_for($name).'[minute]',
          $this->minutes,
          $value ? $value->minute: null,
        Core_Arrays::merge(
          array('id' => $this->field_id_for($name).'_minute'),
          isset($attributes['minute']) ?  Core_Arrays::merge($attributes, (array)$attributes['minute']) : $attributes)) : '');
  }
///     </body>
///   </method>

///   <method name="submit_button" returns="string">
///     <args>
///       <arg name="value" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function submit_button($t, $value, array $attributes = array()) {
    return $t->tags->tag('input',
      Core_Arrays::merge(array(
        'type'   => 'submit',
        'value'  => $value,
        'id'     => "{$this->form->name}_form_submit"), $attributes))."\n";
  }
///     </body>
///   </method>

///   <method name="submit_image" returns="string">
///     <args>
///       <arg name="source" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function submit_image($t, $src, array $attributes = array()) {
    return $t->tags->tag('input',
      Core_Arrays::merge(array(
        'type' => 'image',
        'src'  => Templates_HTML::helper('assets')->image_path_for($src),
        'id'   => "{$this->form->name}_form_submit"), $attributes))."\n";
  }
///     </body>
///   </method>

///   <method name="end_form" returns="string">
///     <body>
  public function end_form($t) {
    $this->form = null;
    return "</form>\n";
  }
///     </body>
///   </method>

///   <method name="end_form_tag" returns="string">
///     <body>
  public function end_form_tag($t) { return "</form>\n"; }
///     </body>
///   </method>

///  </protocol>

///   <protocol name="supporting">

///   <method name="field_name_for" returns="string" access="protected">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function field_name_for($name) { return "{$this->form->name}[$name]"; }
///     </body>
///   </method>

///   <method name="field_id_for" returns="string" access="protected">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function field_id_for($name) { return "{$this->form->name}_$name"; }
///     </body>
///   </method>

///   <method name="add_error_class" access="protected">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" />
///     </args>
///     <body>
  protected function add_error_class($name, array &$attributes) {
    if ($this->form->validator && $this->form->validator->errors->has_error_for($name))
       $attributes['class'] = (isset($attributes['class']) ? $attributes['class'].' ' : '').'error';
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
