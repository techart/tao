<?php
/// <module name="Templates.HTML.Forms" maintainer="svistunov@techart.ru" version="0.2.1">
///   <brief>Хелпер для формирования форм в HTML-шаблоне</brief>
Core::load('Templates.HTML', 'Forms');

//TODO: тут в details надо будет запихнуть схему построения полей формы и ссылку на пример css к примеру
/// <class name="Templates.HTML.Forms" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="Templates.HelperInterface" />
class Templates_HTML_Forms implements Core_ModuleInterface, Templates_HelperInterface, Core_PropertyAccessInterface {
///   <constants>
  const VERSION = '0.2.1';
///   </constants>

  protected $form;
  protected $field;

///   <protocol name="building">

///   <method name="instance" returns="Templates.HTML.Forms" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.HTML.Forms</brief>
///     <body>
  static public function instance() { return new Templates_HTML_Forms(); }
///     </body>
///   </method>

///   <method name="initialize" scope="class">
///     <body>
  static public function initialize() {
    Templates_HTML::use_helper('forms', 'Templates.HTML.Forms');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="generating">

///   <method name="begin_form">
///     <brief>Формирует HTML-форму с помощью объекта формы $form</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="form" type="Forms.Form" brief="форма" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function begin_form(Templates_HTML_Template $t, $form, array $attrs = array()) {
    $this->form = $form;

    $method = Net_HTTP::method_name_for($form->options['method']);

    $result = $t->tag('form', array_merge(array(
        'action'  => $form->action,
        'method'  => ($method == 'post' || $method == 'get' ? $method : 'post'),
        'id'      => "{$form->name}_form",
        'enctype' => $form->options['enctype']
        ), $attrs), false).
      $this->begin_fieldset($t, Core_Arrays::pick($attrs, 'fieldset', array()));

    if ($method == 'put' || $method == 'delete')
      $result .= $t->tag('input', array('type' => 'hidden', 'name' => '_method', 'value' => $method ));

    return $result;
  }
///     </body>
///   </method>

///   <method name="begin_fieldset">
///     <brief>Формирует таг fieldset</brief>
///     <details>
///       Массив опций может содержать элемент c ключом 'legend', тогда будет сформирован соответствующий таг
///     </details>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="attributes" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function begin_fieldset(Templates_HTML_Template $t, array $attrs = array()) {
    $legend = Core_Arrays::pick($attrs, 'legend');
    return
      $t->tag('fieldset', $attrs, false).
        ($legend ? $t->content_tag('legend', "<span>$legend</span>") : '');
  }
///     </body>
///   </method>

///   <method name="end_fieldset">
///     <brief>Закрывате таг fieldset</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///     </args>
///     <body>
  public function end_fieldset(Templates_HTML_Template $t) { return "</fieldset>"; }
///     </body>
///   </method>

///   <method name="end_form">
///     <brief>Закрывает таг form</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///     </args>
///     <body>
  public function end_form(Templates_HTML_Template $t) {
    $this->form = null;
    return $this->end_fieldset($t).'</form>';
  }
///     </body>
///   </method>

///   <method name="begin_field">
///     <brief>Формирует поле формы ввиде div-тага, автоматически валидируя соответствующее поле формы</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function begin_field(Templates_HTML_Template $t, $name, array $attrs = array()) {
    $this->field = $name;
    $attrs['class'] = Core::if_not_set($attrs, 'class', '').' field';
    $attrs['id'] = 'field_'.$this->field_id_for($name);

    if ($this->form->validator &&
        $this->form->validator->errors->has_error_for($name)) $attrs['class'] .= ' error';

    return $t->tag('div', $attrs, false);
  }
///     </body>
///   </method>

///   <method name="end_field">
///     <brief>Закрывает таг поля + выводит ошибку, если она есть</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///     </args>
///     <body>
  public function end_field(Templates_HTML_Template $t) {
    $r =
      (($this->form->validator &&
             $this->form->validator->errors->has_error_for($this->field)) ?
        $t->content_tag('p', $this->form->property_errors[$this->field], array('class' => 'error')) : '').
      '</div>';
     $this->field = null;
     return $r;
  }
///     </body>
///   </method>

///   <method name="field" returns="string">
///     <brief>Формирует полностью поле формы</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="field" type="array|string" brief="тип поля + параметры" />
///       <arg name="attrs" type="array" default="array()" brief="массив опций поля" />
///     </args>
///     <details>
///       <p>Параметр $field может как строкой с название типа поля (input, texarea, checkbox ...),
///       так и массивом первый элемент которого - тип поля, а втрой массив параметров этого поля,
///       таких как 'class' и т.д.</p>
///       <p>Массив опций $attrs может содержать элемент с ключом 'label' для создания label тага и
///       и элемент с ключом 'help' для вывода информационного сообщения</p>
///     </details>
///     <body>
  public function field(Templates_HTML_Template $t, $name, $field, array $attrs = array()) {
    //TODO: брать тип поля из $this->form
    $r     = '';
    $label = isset($attrs['label']) ? Core_Arrays::pick($attrs, 'label') : null;
    $help  = isset($attrs['help']) ? Core_Arrays::pick($attrs, 'help') : null;

    $field   = (array) $field;
    $type    = array_shift($field);
    $r = $this->begin_field($t, $name, $attrs);
    if ($label !== null) $r .= $this->label($t, $name, $label);
    $r .= call_user_func_array(array($this, $type), array_merge(array($t, $name), $field));
    if ($help) $r .= $this->help($t, $help);
    return $r.$this->end_field($t);
  }
///     </body>
///   </method>

///   <method name="fields">
///     <brief>Формирует несколько полей формы</brief>
///     <brief>На вход подается набор массив с навтройками для метода field</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///     </args>
///     <body>
  public function fields(Templates_HTML_Template $t) {
    $r = '';

    $args = func_get_args(); array_shift($args);
    foreach ($args as $field) $r .= call_user_func_array(array($this, 'field'), array_merge(array($t), $field));
    return $r;
  }
///     </body>
///   </method>

///   <method name="help">
///     <brief>Формирует информационное сообщение</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="text" type="string" brief="текст сообщения" />
///     </args>
///     <body>
  public function help(Templates_HTML_Template $t, $text, array $attrs = array()) {
    $attrs['class'] = (isset($attrs['class']) ? $attrs['class'].' ' : '').'help';
    return $t->content_tag('p',(string) $text, $attrs);
  }
///     </body>
///   </method>

///   <method name="checkbox" returns="string">
///     <brief>Формирует checbox html-формы</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function checkbox(Templates_HTML_Template $t, $name, array $attrs = array())  {
    $label = isset($attrs['label']) ? Core_Arrays::pick($attrs, 'label') : null;

    $r = $t->
      tag('input', Core_Arrays::merge(array(
        'type'    => 'checkbox',
        'value'   => 1,
        'checked' => $this->form[$name] ? true : false,
        'name'    => $this->field_name_for($name),
        'id'      => $this->field_id_for($name)), $attrs));

    return $label !== null ? $this->label($t, '', "$r&nbsp;$label", array('class' => 'inline')) : $r;
  }
///     </body>
///   </method>

///   <method name="radio" returns="string">
///     <brief>Формирует radio-button html-формы</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function radio(Templates_HTML_Template $t, $name, array $attrs = array())  {
    $label = isset($attrs['label']) ? Core_Arrays::pick($attrs, 'label') : null;

    $r = $t->
      tag('input', Core_Arrays::merge(array(
        'type'    => 'radio',
        'value'   => 1,
        'checked' => $this->form[$name] ? true : false,
        'name'    => $this->field_name_for($name),
        'id'      => $this->field_id_for($name)), $attrs));

    return $label !== null ? $this->label($t, '', "$r&nbsp;$label", array('class' => 'inline')) : $r;
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
    return $t->content_tag('label', $text, array('for' => $name) + $attributes);
  }
///     </body>
///   </method>

///   <method name="label">
///     <brief>Формирует label</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" default="null" brief="имя поля формы" />
///       <arg name="text" type="string" default="'&amp;nbsp'" brief="текст" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function label(Templates_HTML_Template $t, $name = '', $text = '&nbsp', array $attrs = array()) {
    return $t->content_tag(
      'label',
      $text,
      Core_Arrays::merge($name ? array('for' => $this->field_id_for($name)) : array('class' => 'inline'), $attrs));
  }
///     </body>
///   </method>

///   <method name="input">
///     <brief>формирует input html-формы</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function input(Templates_HTML_Template $t, $name, array $attrs = array()) {
    return $t->
      tag('input', Core_Arrays::merge(array(
       'type'  => 'text',
       'name'  => $this->field_name_for($name),
       'value' => $this->form[$name],
       'id'    => $this->field_id_for($name)), $attrs));
  }
///     </body>
///   </method>

///   <method name="input_tag" returns="string">
///     <brief>Формирует html-таг input</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="value" brief="занчение" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function input_tag(Templates_HTML_Template $t, $name, $value, array $attrs = array()) {
    return $t->tag('input',
      Core_Arrays::merge(array(
        'type'  => 'text',
        'name'  => $name,
        'value' => $value ), $attrs));
  }
///     </body>
///   </method>

///   <method name="textarea">
///     <brief>Формирует textarea</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function textarea(Templates_HTML_Template $t, $name, array $attrs = array()) {
    return $t->
        content_tag('textarea', htmlspecialchars($this->form[$name]), Core_Arrays::merge(array(
         'name'  => $this->field_name_for($name),
         'id'    => $this->field_id_for($name)), $attrs));
  }
///     </body>
///   </method>

///   <method name="submit">
///     <brief>Формирует div внутри которого button, с зарание заданнами классами</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="attrs" type="array" brief="массив атрибутов" />
///     </args>
///     <body>
  public function submit(Templates_HTML_Template $t, $text = 'Submit') {
    return $t->content_tag('div', $t->content_tag('button', $text, array('class' => 'submit', 'type' => 'submit')), array('class' => 'submit'));
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
    return $t->tag('input',
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
    return $t->tag('input',
      Core_Arrays::merge(array(
        'type' => 'image',
        'src'  => Templates_HTML::helper('assets')->image_path_for($src),
        'id'   => "{$this->form->name}_form_submit"), $attributes))."\n";
  }
///     </body>
///   </method>

///   <method name="password" returns="string">
///     <brief>Формирует password</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function password(Templates_HTML_Template $t, $name, array $attributes = array()) {
    return $t->
      tag('input', Core_Arrays::merge(array(
        'type' => 'password',
        'name' => $this->field_name_for($name),
        'id'   => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="upload" returns="string">
///     <brief>Формирует uplod</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атриботов" />
///     </args>
///     <body>
  public function upload(Templates_HTML_Template $t, $name, array $attributes = array()) {
    return $t->tag(
        'input', Core_Arrays::merge(array(
        'type' => 'file',
        'name' => $this->field_name_for($name),
        'id'   => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="hidden" returns="string">
///     <brief>Формирует hidden</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function hidden(Templates_HTML_Template $t, $name, $attributes = array()) {
    return $t->
      tag('input', Core_Arrays::merge(array(
        'type'  => 'hidden',
        'name'  => $this->field_name_for($name),
        'value' => (string) $this->form[$name],
        'id'    => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="datetime_select" returns="string">
///     <brief>Формирует таги для ввода и выбора даты</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function datetime_select(Templates_HTML_Template $t, $name, $attributes = array()) {
    $value     = $this->form[$name];
    $show_time = Core_Arrays::pick($attributes, 'show_time', false);
    $res = '';
    foreach (array(
      'day'    => array('from' => 1, 'to' => 31),
      'month'  => array('from' => 1, 'to' => 12),
      'year'   => array('tag' => 'input', 'from' => null, 'to' => null, 'attr' => array('size' => 4, 'type' => 'text', 'value' => $value->year))
      ) + ($show_time ? array(
      'hour'   => array('from' => 0, 'to' => 23),
      'minute' => array('from' => 0, 'to' => 59)
      ) : array()) as $type => $v ) {
      $res .= $this->datetime_tag(
        $t, $v['from'], $v['to'], $name, $type, $value, isset($v['attr']) ?
          array_merge($v['attr'] , $attributes) :
          $attributes, Core::if_not_set($v, 'tag', 'select'));
    }
    return $res;
  }
///     </body>
///   </method>

///   <method name="select_tag" returns="string">
///     <brief>Формирует тег select</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="имя шаблона" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="items" brief="набор значений для выбора" />
///       <arg name="selected" brief="выбранное значение" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function select_tag(Templates_HTML_Template $t, $name, $items, $selected, array $attributes = array()) {
    $options    = array();

    $allow_null = false;
    $key        = Core_Arrays::pick($attributes, 'key', '');
    $attribute  = Core_Arrays::pick($attributes, 'attribute', '');

    $selected = $key ? $selected->$key : $selected;

    foreach ($items as $k => $v) {
      $k =  $key ? $v->$key : $k;
      $v = $attribute ? $v->$attribute : $v;

      $options[]  = $t->content_tag(
        'option',
        $v,
        array('value' => $k, 'selected' => ($selected !== null && (string) $selected == (string) $k)));
    }

    return $t->
      content_tag('select',
        (($allow_null = Core_Arrays::pick($attributes, 'allow_null', false)) ? $t->content_tag('option', $allow_null === true ? '' : $allow_null, array('value' => '')) : '').
        Core_Arrays::join_with(' ', $options),
        Core_Arrays::merge(array('name' => $name), $attributes));
  }
///     </body>
///   </method>

///   <method name="select" returns="string">
///     <brief>Формирует select</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function select(Templates_HTML_Template $t, $name,  array $attributes = array()) {
    return $this->select_tag($t, $this->field_name_for($name),
      $this->form->fields[$name]->items, $this->form->fields[$name]->value,
        Core_Arrays::merge(array('id' => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="object_select" returns="string">
///     <brief>Формирует таги select и option для object_select и object_multi_select полей формы</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function object_select(Templates_HTML_Template $t, $name, array $attributes = array()) {
    $options    = array();
    $field      = $this->form->fields[$name];
    $key        = $field->key;
    $attribute  = $field->attribute;
    $multi = array_search('multiple', $attributes, true) !== false;

    foreach ($field->items as $item) {
      $options[] = $t->
        content_tag('option',
          (string) $item->$attribute,
          array(
            'value'    => $item->$key,
            'selected' => $multi ? isset($this->form[$name][$item->$key]) :
                $this->form[$name] && ($this->form[$name]->$key == $item->$key)));}

    return
      $t->
        content_tag('select',
          (($allow_null = Core_Arrays::pick($attributes, 'allow_null', false)) ? $t->content_tag('option', '') : '').
          Core_Arrays::join_with('', $options),
          Core_Arrays::merge(array(
            'name' => $this->field_name_for($name).($multi ? '[]' : ''),
            'id'   => $this->field_id_for($name)), $attributes));
  }
///     </body>
///   </method>

///   <method name="object_multicheckbox" returns="string">
///     <brief>Формирует checkbox-ы для object_multi_select поля формы</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="name" type="string" brief="имя поля формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function object_multicheckbox(Templates_HTML_Template $t, $name, array $attributes = array()) {
    $field      = $this->form->fields[$name];
    $key        = $field->key;
    $attribute  = $field->attribute;
    $delim = Core_Arrays::pick($attributes, 'delimitr', '<br>');//&nbsp;
    $r = '';

    foreach ($field->items as $k => $item) {
      $checkbox = $t->
      tag('input', Core_Arrays::merge(array(
        'type'    => 'checkbox',
        'value'   => $item->$key,
        'checked' => isset($this->form[$name][$item->$key]),
        'name'    => $this->field_name_for($name)."[$k]",
        'id'      => $this->field_id_for($name)."_$k"), $attributes));
      $r .= $this->label($t, '', "$checkbox&nbsp;{$item->$attribute}", array('class' => 'inline')) . $delim;
    }

    return $r;
  }
///     </body>
///   </method>

///   <method name="begin_form_tag" returns="string">
///     <brief>Формитуеи таг form</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///       <arg name="action" type="string" brief="action тага формы" />
///       <arg name="method" type="string" brief="метод формы" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function begin_form_tag(Templates_HTML_Template $t, $action, $method, array $attributes = array()) {

    $add_method_field = Core_Arrays::pick($attributes, 'add_method_field', false);

    $result = $t->tag('form', Core_Arrays::merge(array(
      'action' => $action,
      'method' => ($method == 'post' || $method == 'get' ? $method : 'post')), $attributes), false)."\n";

    if ($method == 'put' || $method == 'delete' || $add_method_field)
      $result .= $t->tag('input', array(
        'type'  => 'hidden',
        'name'  => '_method',
        'value' => $method ))."\n";

    return $result;
  }
///     </body>
///   </method>

///   <method name="end_form_tag" returns="string">
///     <brief>Закрывает таг form</brief>
///     <args>
///       <arg name="t" type="Templates.HTML.Template" brief="шаблон" />
///     </args>
///     <body>
  public function end_form_tag(Templates_HTML_Template $t) { return "</form>\n"; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="field_name_for" returns="string" access="protected">
///     <brief>Возвращает имя тага по имени поля формы</brief>
///     <args>
///       <arg name="name" type="string" brief="имя формы поля" />
///     </args>
///     <body>
  protected function field_name_for($name) { return "{$this->form->name}[$name]"; }
///     </body>
///   </method>

///   <method name="field_id_for" returns="string" access="protected">
///     <brief>Возвращает id тага по имени поля формы</brief>
///     <args>
///       <arg name="name" type="string" brief="имя формы поля" />
///     </args>
///     <body>
  protected function field_id_for($name) { return "{$this->form->name}_$name"; }
///     </body>
///   </method>

///   <method name="datetime_tag">
///     <args>
///       <arg name="t" type="Templates.HTML.Template" />
///       <arg name="from " type="int" />
///       <arg name="to" type="int" />
///       <arg name="name" type="stirng" />
///       <arg name="type" type="string" />
///       <arg name="value" type="Time.DateTime" />
///       <arg name="attributes" type="array" />
///       <arg name="tag" type="string" />
///     </args>
///     <body>
  private function datetime_tag($t,$from, $to, $name, $type, Time_DateTime $value, $attributes, $tag = 'select') {
    $res = $t->tag(
      $tag,
      Core_Arrays::merge(
        array('name' => $this->field_name_for($name)."[$type]", 'id' => $this->field_id_for($name)."_$type"),
        isset($attributes[$type]) ?  Core_Arrays::merge($attributes, (array)$attributes[$type]) : $attributes),
        false
      );
    if ($from !== null && $to !== null)
    for ($i=$from;$i <= $to;$i++) {
      $res .= $t->content_tag('option',
         sprintf('%02d', $i),
         array('value' => $i, 'selected' => $value ? $value->$type == $i: false)
      );
    }
    $res .= "</$tag>";
    return $res;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {return $this->$property;}
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value"                  brief="значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if ($property == 'form' && ($value instanceof Forms_Form))
      $this->$property = $value;
    else
      throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {return isset($this->$property);}
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство объекта</brief>
///    <args>
///      <arg name="property" type="string" brief="имя свойства" />
///    </args>
///    <body>
  public function __unset($property) {throw new Core_ReadOnlyObjectException($this);}
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
