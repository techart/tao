<?php
/**
 * Templates.HTML.Helpers.Forms
 * 
 * Хелпер для формирования форм в HTML-шаблоне
 * 
 * @package Templates\HTML\Forms
 * @version 0.2.1
 */
Core::load('Templates.HTML', 'Forms');

//TODO: тут в details надо будет запихнуть схему построения полей формы и ссылку на пример css к примеру
/**
 * @package Templates\HTML\Helpers\Forms
 */
class Templates_HTML_Helpers_Forms implements Core_ModuleInterface, Templates_HelperInterface, Core_PropertyAccessInterface {
  const VERSION = '0.2.1';

  protected $form;
  protected $field;


/**
 * Фабричный метод, возвращает объект класса Templates.HTML.Forms
 * 
 * @return Templates_HTML_Forms
 */
  static public function instance() { return new Templates_HTML_Helpers_Forms(); }

/**
 */
  static public function initialize() {
    Templates_HTML::use_helper('forms', 'Templates.HTML.Helpers.Forms');
  }



/**
 * Формирует HTML-форму с помощью объекта формы $form
 * 
 * @param Templates_HTML_Template $t
 * @param Forms_Form $form
 * @param array $attributes
 */
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

/**
 * Формирует таг fieldset
 * 
 * @param Templates_HTML_Template $t
 * @param array $attributes
 */
  public function begin_fieldset(Templates_HTML_Template $t, array $attrs = array()) {
    $legend = Core_Arrays::pick($attrs, 'legend');
    return
      $t->tag('fieldset', $attrs, false).
        ($legend ? $t->content_tag('legend', "<span>$legend</span>") : '');
  }

/**
 * Закрывате таг fieldset
 * 
 * @param Templates_HTML_Template $t
 */
  public function end_fieldset(Templates_HTML_Template $t) { return "</fieldset>"; }

/**
 * Закрывает таг form
 * 
 * @param Templates_HTML_Template $t
 */
  public function end_form(Templates_HTML_Template $t) {
    $this->form = null;
    return $this->end_fieldset($t).'</form>';
  }

/**
 * Формирует поле формы ввиде div-тага, автоматически валидируя соответствующее поле формы
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 */
  public function begin_field(Templates_HTML_Template $t, $name, array $attrs = array()) {
    $this->field = $name;
    $attrs['class'] = Core::if_not_set($attrs, 'class', '').' field';
    $attrs['id'] = 'field_'.$this->field_id_for($name);

    if ($this->form->validator &&
        $this->form->validator->errors->has_error_for($name)) $attrs['class'] .= ' error';

    return $t->tag('div', $attrs, false);
  }

/**
 * Закрывает таг поля + выводит ошибку, если она есть
 * 
 * @param Templates_HTML_Template $t
 */
  public function end_field(Templates_HTML_Template $t) {
    $r =
      (($this->form->validator &&
             $this->form->validator->errors->has_error_for($this->field)) ?
        $t->content_tag('p', $this->form->property_errors[$this->field], array('class' => 'error')) : '').
      '</div>';
     $this->field = null;
     return $r;
  }

/**
 * Формирует полностью поле формы
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array|string $field
 * @param array $attrs
 * @return string
 */
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

/**
 * Формирует несколько полей формы
 * 
 * @param Templates_HTML_Template $t
 */
  public function fields(Templates_HTML_Template $t) {
    $r = '';

    $args = func_get_args(); array_shift($args);
    foreach ($args as $field) $r .= call_user_func_array(array($this, 'field'), array_merge(array($t), $field));
    return $r;
  }

/**
 * Формирует информационное сообщение
 * 
 * @param Templates_HTML_Template $t
 * @param string $text
 */
  public function help(Templates_HTML_Template $t, $text, array $attrs = array()) {
    $attrs['class'] = (isset($attrs['class']) ? $attrs['class'].' ' : '').'help';
    return $t->content_tag('p',(string) $text, $attrs);
  }

/**
 * Формирует checbox html-формы
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attrs
 * @return string
 */
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

/**
 * Формирует radio-button html-формы
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attrs
 * @return string
 */
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

/**
 * @param string $name
 * @param string $text
 * @param array $attributes
 * @return string
 */
  public function label_tag($t, $name, $text, array $attributes = array()) {
    return $t->content_tag('label', $text, array('for' => $name) + $attributes);
  }

/**
 * Формирует label
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param string $text
 * @param array $attrs
 */
  public function label(Templates_HTML_Template $t, $name = '', $text = '&nbsp', array $attrs = array()) {
    return $t->content_tag(
      'label',
      $text,
      Core_Arrays::merge($name ? array('for' => $this->field_id_for($name)) : array('class' => 'inline'), $attrs));
  }

/**
 * формирует input html-формы
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attrs
 */
  public function input(Templates_HTML_Template $t, $name, array $attrs = array()) {
    return $t->
      tag('input', Core_Arrays::merge(array(
       'type'  => 'text',
       'name'  => $this->field_name_for($name),
       'value' => $this->form[$name],
       'id'    => $this->field_id_for($name)), $attrs));
  }

/**
 * Формирует html-таг input
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param  $value
 * @param array $attrs
 * @return string
 */
  public function input_tag(Templates_HTML_Template $t, $name, $value, array $attrs = array()) {
    return $t->tag('input',
      Core_Arrays::merge(array(
        'type'  => 'text',
        'name'  => $name,
        'value' => $value ), $attrs));
  }

/**
 * Формирует textarea
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attrs
 */
  public function textarea(Templates_HTML_Template $t, $name, array $attrs = array()) {
    return $t->
        content_tag('textarea', htmlspecialchars($this->form[$name]), Core_Arrays::merge(array(
         'name'  => $this->field_name_for($name),
         'id'    => $this->field_id_for($name)), $attrs));
  }

/**
 * Формирует div внутри которого button, с зарание заданнами классами
 * 
 * @param Templates_HTML_Template $t
 * @param array $attrs
 */
  public function submit(Templates_HTML_Template $t, $text = 'Submit') {
    return $t->content_tag('div', $t->content_tag('button', $text, array('class' => 'submit', 'type' => 'submit')), array('class' => 'submit'));
  }

/**
 * @param string $value
 * @param array $attributes
 * @return string
 */
  public function submit_button($t, $value, array $attributes = array()) {
    return $t->tag('input',
      Core_Arrays::merge(array(
        'type'   => 'submit',
        'value'  => $value,
        'id'     => "{$this->form->name}_form_submit"), $attributes))."\n";
  }

/**
 * @param string $source
 * @param array $attributes
 * @return string
 */
  public function submit_image($t, $src, array $attributes = array()) {
    return $t->tag('input',
      Core_Arrays::merge(array(
        'type' => 'image',
        'src'  => Templates_HTML::helper('assets')->image_path_for($src),
        'id'   => "{$this->form->name}_form_submit"), $attributes))."\n";
  }

/**
 * Формирует password
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 * @return string
 */
  public function password(Templates_HTML_Template $t, $name, array $attributes = array()) {
    return $t->
      tag('input', Core_Arrays::merge(array(
        'type' => 'password',
        'name' => $this->field_name_for($name),
        'id'   => $this->field_id_for($name)), $attributes));
  }

/**
 * Формирует uplod
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 * @return string
 */
  public function upload(Templates_HTML_Template $t, $name, array $attributes = array()) {
    return $t->tag(
        'input', Core_Arrays::merge(array(
        'type' => 'file',
        'name' => $this->field_name_for($name),
        'id'   => $this->field_id_for($name)), $attributes));
  }

/**
 * Формирует hidden
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 * @return string
 */
  public function hidden(Templates_HTML_Template $t, $name, $attributes = array()) {
    return $t->
      tag('input', Core_Arrays::merge(array(
        'type'  => 'hidden',
        'name'  => $this->field_name_for($name),
        'value' => (string) $this->form[$name],
        'id'    => $this->field_id_for($name)), $attributes));
  }

/**
 * Формирует таги для ввода и выбора даты
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 * @return string
 */
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

/**
 * Формирует тег select
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param  $items
 * @param  $selected
 * @param array $attributes
 * @return string
 */
  public function select_tag(Templates_HTML_Template $t, $name, $items, $selected, array $attributes = array()) {
    $options    = array();

    $allow_null         = false;
    $key                = Core_Arrays::pick($attributes, 'key', '');
    $attribute          = Core_Arrays::pick($attributes, 'attribute', '');
    $disabled_keys      = Core_Arrays::pick($attributes, 'disabled_keys', array());
    $disabled_property  = Core_Arrays::pick($attributes, 'disabled_property', '');

    $selected = $key ? $selected->$key : $selected;

    foreach ($items as $k => $v) {
      $k =  $key ? $v->$key : $k;
      $disabled = $disabled_property ? isset($v->$disabled_property) && $v->$disabled_property : in_array($k, $disabled_keys);
      $v = $attribute ? $v->$attribute : $v;

      $options[]  = $t->content_tag(
        'option',
        $v,
        array('value' => $k, 'selected' => ($selected !== null && (string) $selected == (string) $k), 'disabled' => $disabled));
    }

    return $t->
      content_tag('select',
        (($allow_null = Core_Arrays::pick($attributes, 'allow_null', false)) ? $t->content_tag('option', $allow_null === true ? '' : $allow_null, array('value' => '')) : '').
        Core_Arrays::join_with(' ', $options),
        Core_Arrays::merge(array('name' => $name), $attributes));
  }

/**
 * Формирует select
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 * @return string
 */
  public function select(Templates_HTML_Template $t, $name,  array $attributes = array()) {
    return $this->select_tag($t, $this->field_name_for($name),
      $this->form->fields[$name]->items, $this->form->fields[$name]->value,
        Core_Arrays::merge(array('id' => $this->field_id_for($name)), $attributes));
  }

/**
 * Формирует таги select и option для object_select и object_multi_select полей формы
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 * @return string
 */
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

/**
 * Формирует checkbox-ы для object_multi_select поля формы
 * 
 * @param Templates_HTML_Template $t
 * @param string $name
 * @param array $attributes
 * @return string
 */
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

/**
 * Формитуеи таг form
 * 
 * @param Templates_HTML_Template $t
 * @param string $action
 * @param string $method
 * @param array $attributes
 * @return string
 */
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

/**
 * Закрывает таг form
 * 
 * @param Templates_HTML_Template $t
 * @return string
 */
  public function end_form_tag(Templates_HTML_Template $t) { return "</form>\n"; }



/**
 * Возвращает имя тага по имени поля формы
 * 
 * @param string $name
 * @return string
 */
  protected function field_name_for($name) { return "{$this->form->name}[$name]"; }

/**
 * Возвращает id тага по имени поля формы
 * 
 * @param string $name
 * @return string
 */
  protected function field_id_for($name) { return "{$this->form->name}_$name"; }

/**
 * @param Templates_HTML_Template $t
 * @param int $from
 * @param int $to
 * @param stirng $name
 * @param string $type
 * @param Time_DateTime $value
 * @param array $attributes
 * @param string $tag
 */
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



/**
 * Возвращает значение свойства
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {return $this->$property;}

/**
 * Устанавливает значение свойства
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    if ($property == 'form' && ($value instanceof Forms_Form))
      $this->$property = $value;
    else
      throw new Core_ReadOnlyObjectException($this);
  }

/**
 * Проверяет установку значения свойства
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {return isset($this->$property);}

/**
 * Удаляет свойство объекта
 * 
 * @param string $property
 */
  public function __unset($property) {throw new Core_ReadOnlyObjectException($this);}


}

