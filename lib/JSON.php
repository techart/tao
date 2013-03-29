<?php
/// <module name="JSON" version="0.2.2" maintainer="timokhin@techart.ru">

Core::load('Time', 'Object');

/// <class name="JSON" stereotype="module">
class JSON implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.2';
///   </constants>

///   <protocol name="building">

///   <method name="Converter" returns="JSON.Converter" scope="class">
///     <body>
  static public function Converter() { return new JSON_Converter(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="JSON.AttributeConverter">
class JSON_AttributeConverter {

///   <protocol name="quering">

///   <method name="can_encode" returns="boolean">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
  public function can_encode($object) { return $this->can('encode', $object); }
///     </body>
///   </method>

///   <method name="can_decode" returns="boolean">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
  public function can_decode($object) { return $this->can('decode', $object); }
///     </body>
///   </method>

///   <method name="can" returns="boolean">
///     <args>
///       <arg name="operation" type="string" />
///       <arg name="object" />
///     </args>
///     <body>
  public function can($operation, $object) {
    return method_exists($this, $m = $operation.'_'.strtolower(Core_Types::real_class_name_for($object))) ? $m : false;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="JSON.Converter">
class JSON_Converter {

  protected $converters = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {
    $this->setup();
  }
///     </body>
///   </method>

///   <method name="setup" access="protected">
///     <body>
  protected function setup() {}
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="using" returns="JSON.Converter">
///     <body>
  public function using(JSON_AttributeConverter $converter) {
    $this->converters[] = $converter;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="converting">

///   <method name="from" returns="string">
///     <args>
///       <arg name="object" type="Object.AttrListInterface" />
///       <arg name="flavor" />
///     </args>
///     <body>
  public function from(Object_AttrListInterface $object, $flavor = null) {
    return $this->encode_object($object, $flavor);
  }
///     </body>
///   </method>

///   <method name="from_collection" returns="string">
///     <args>
///       <arg name="items" />
///       <arg name="flavor" default="null" />
///     </args>
///     <body>
  public function from_collection($items, $flavor = null) {
    $r = array();
    foreach ($items as $item)
      if ($item instanceof Object_AttrListInterface)
        $r[] = $this->encode_object($item, $flavour);
    return $r;
  }
///     </body>
///   </method>

///   <method name="to" returns="object">
///     <args>
///       <arg name="object" type="Object.AttrListInterface" />
///       <arg name="json"   type="string" />
///       <arg name="flavor" />
///     </args>
///     <body>
  public function to(Object_AttrListInterface $object, $json, $flavor = null) {
    return $this->decode_object(is_string($json) ? json_decode($json) : $json, $object, $flavor);
  }
///     </body>
///   </method>

///   <method name="encode" returns="object">
///     <args>
///       <arg name="data" type="array|stdObject" />
///     </args>
///     <body>
  public function encode($data) { return json_encode($data); }
///     </body>
///   </method>

///   <method name="encode_object" returns="object" access="protected">
///     <args>
///       <arg name="object" type="Object.AttrListInterface" />
///       <arg name="flavor" default="null" />
///     </args>
///     <body>
  protected function encode_object(Object_AttrListInterface $object, $flavor = null) {
    $r = new stdClass();
    foreach ($object->__attrs($flavor) as $attr) {
      switch (true) {
        case $attr->is_object():
          $r->{$attr->name} = $this->encode_object($object->{$attr->name}, $flavor);
          break;
        case $attr->is_collection():
          $r->{$attr->name} = $this->encode_collection($object, $attr, $flavor);
          break;
        case $attr->is_value():
          $r->{$attr->name} = $this->encode_value($object, $attr);
          break;
      }
    }
    return $r;
  }
///     </body>
///   </method>

///   <method name="encode_collection" returns="array" access="protected">
///     <args>
///       <arg name="object" type="Object.AttrListInterface" />
///       <arg name="attr" type="Object.Attribute" />
///       <arg name="flavor" default="null" />
///     </args>
///     <body>
  protected function encode_collection(Object_AttrListInterface $object, Object_Attribute $attr, $flavor = null) {
    $items = array();

    foreach ($object->{$attr->name} as $item)
      if (is_object($item) && $item instanceof Object_AttrListInterface)
        $items[] = $this->encode_object($item, $flavor);
      else
        $items[] = $this->encode_scalar($item, $attr);

    return $items;
  }
///     </body>
///   </method>

///   <method name="encode_value" returns="mixed" access="protected">
///     <args>
///       <arg name="object" type="Object.AttrListInterface" />
///       <arg name="attr" type="Object.Attribute" />
///     </args>
///     <body>
  protected function encode_value(Object_AttrListInterface $object, Object_Attribute $attr) {
    $value = $object->{$attr->name};

    if (isset($attr->type))
      switch ($attr->type) {
        case 'string':   return (string) $value;
        case 'int':      return (int) $value;
        case 'float':    return (float) $value;
        case 'boolean':  return  (boolean) $value;
        case 'datetime': return $this->encode_datetime($value, $attr);
        default:
          foreach ($this->converters as $c)
            if ($m = $c->can_encode($attr->type)) return $c->$m($value, $attr);
          return serialize($value);
      }
    else
      return $this->encode_scalar($value, $attrs);
  }
///   </body>
///   </method>

///   <method name="encode_scalar">
///     <args>
///       <arg name="value" type="mixed" />
///       <arg name="attrs" type="Object.Attribute" />
///     </args>
///     <body>
  protected function encode_scalar($value, Object_Attribute $attrs) {
    switch (true) {
      case is_string($value):
      case is_numeric($value):
      case is_bool($value):
        return $value;
      case $value instanceof Time_DateTime:
        return $this->encode_datetime($value);
      case is_object($value):
        foreach ($this->converters as $c)
          if ($m = $c->can_encode($value)) return $c->$m($value, $attr);
        return serialize($value);
    }
  }
///     </body>
///   </method>

///   <method name="encode_datetime" returns="Time.DateTime" access="protected">
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function encode_datetime($value, $attr = null) {
      return ($value = Time::DateTime($value)) ? $value->format(isset($attr->format) ? $attr->format : Time::FMT_ISO_8601) : null;
  }
///     </body>
///   </method>

///   <method name="decode" returns="object">
///     <args>
///       <arg name="json" type="string" />
///     </args>
///     <body>
  public function decode($json) {
    return json_decode($json);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="decode_object" returns="object" access="protected">
///     <args>
///       <arg name="json" type="object" />
///       <arg name="object" type="Object.AttrListInterface" />
///       <arg name="flavor" default="null" />
///     </args>
///     <body>
  public function decode_object($json, Object_AttrListInterface $object, $flavor = null) {
    foreach ($object->__attrs($flavor) as $attr) {
      if (isset($json->{$attr->name}))
        switch (true) {
          case $attr->is_object() && is_object($json->{$attr->name}):
            $object->{$attr->name} = $this->decode_object($json->{$attr->name}, Core::make($attr->type));
            break;
          case $attr->is_collection() && is_array($json->{$attr->name}):
            $this->decode_collection($json, $object, $attr, $flavor);
            break;
          case $attr->is_value():
            $this->decode_value($json, $object, $attr);
            break;
        }
    }
    return $object;
  }
///     </body>
///   </method>

///   <method name="decode_collection" returns="object" access="protected">
///     <args>
///       <arg name="json" type="array" />
///       <arg name="object" type="Object.AttrsListInterface" />
///       <arg name="attr" type="Object.Attribute" />
///       <arg name="flavor" default="null" />
///     </args>
///     <body>
  protected function decode_collection($json, Object_AttrListInterface $object, Object_Attribute $attr, $flavor = null) {
    $operation = isset($attr->operation) ? $attr->operation : 'append';
    foreach ($json->{$attr->name} as $v) {
      if (Core_Types::is_subclass_of('Object_AttrListInterface', $attr->items))
        $item = $this->decode_object($v, Core::make($attr->items));
      else
        $item = $this->decode_scalar($v, $attr->items);

      if (is_string($operation) && method_exists($object->{$attr->name}, $operation))
        $object->{$attr->name}->$operation($item);
      else if (is_array($operation)) {
       call_user_func($operation, $item);
      }
    }
    return $object;
  }
///     </body>
///   </method>


///   <method name="decode_scalar">
///     <args>
///       <arg name="value" type="mixed" />
///       <arg name="type" type="string" />
///     </args>
///     <body>
  protected function decode_scalar($value, $type) {
    if (!$type) return $value;
    switch ($type) {
      case 'datetime' :
        $value = $this->decode_datetime($value);
        break;
      default:
        settype($value, $type);
        break;
    }
    return $value;
  }
///     </body>
///   </method>

///   <method name="decode_collection" returns="object" access="protected">
///     <args>
///       <arg name="json" />
///       <arg name="object" type="Object.AttrsListInterface" />
///       <arg name="attr"   type="Object.Attribute" />
///     </args>
///     <body>
  protected function decode_value($json, Object_AttrListInterface $object, Object_Attribute $attr) {
    if (isset($attr->type))
      switch ($attr->type) {
        case 'string':
        case 'int':
        case 'float':
        case 'boolean':
        case 'datetime':
          $object->{$attr->name} = $this->decode_scalar($json->{$attr->name}, $attr->type);
          break;
        default:
          foreach ($this->converters as $c)
            if ($m = $c->can_decode($attr->type)) break;

          if ($m)
            $object->{$attr->name} = $c->$m($json->{$attr->name}, $attr);
          else
            if (is_string($json->{$attr->name}) &&
                is_object($restored = unserialize($json->{$attr->name})) &&
                ($restored instanceof $attr->type)) $object->{$attr->name} = $restored;
      }
    else
      $object->{$attr->name} = $json->{$attr->name};

    return $object;
  }
///     </body>
///   </method>

///   <method name="decode_datetime" returns="Time.DateTime" access="protected">
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function decode_datetime($value) {
    return Time::DateTime($value);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
