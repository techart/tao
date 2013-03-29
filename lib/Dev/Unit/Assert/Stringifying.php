<?php
/// <module name="Dev.Unit.Assert.Stringifying" maintainer="timokhin@techart.ru" version="0.2.0">

/// <class name="Dev.Unit.Assert.Stringifying">
///   <implements interface="Dev.Unit.AssertBundleModuleInterface" />
class Dev_Unit_Assert_Stringifying implements Dev_Unit_AssertBundleModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="bundle" returns="Dev.Unit.Assert.Stringifying.Bundle" scope="class">
///     <body>
  static public function bundle() { return new Dev_Unit_Assert_Stringifying_Bundle(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Assert.Stringifying.Bundle" extends="Dev.Unit.AssertBundle">
class Dev_Unit_Assert_Stringifying_Bundle extends Dev_Unit_AssertBundle {

///   <protocol name="testing">

///   <method name="assert_string" returns="Dev.Unit.Assert.Stringifying.Bundle">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="value" type="string" />
///     </args>
///     <body>
    public function assert_string($object, $value) {
      if (($object instanceof Core_StringifyInterface) &&
          ($object->as_string() === (string) $value) &&
          ($object->__toString() === (string) $value))
        return $this;
      else
        throw new Dev_Unit_FailureException(sprintf("failed: strings don't match: %s != %s",
          $this->stringify($value), $this->stringify($object)));
    }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
?>