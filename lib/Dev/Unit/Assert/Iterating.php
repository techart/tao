<?php
/// <module name="Dev.Unit.Assert.Iterating" maintainer="svistunov@techart.ru" version="0.1.1">

/// <class name="Dev.Unit.Assert.Iterating">
///   <implements interface="Dev.Unit.AssertBundleModuleInterface" />
class Dev_Unit_Assert_Iterating implements Dev_Unit_AssertBundleModuleInterface {

///   <constants>
  const VERSION = '0.1.1';
///   </constants>

///   <protocol name="building">

///   <method name="bundle" scope="class" returns="Dev.Unit.Assert.Iterating.Bundle">
///     <body>
  static public function bundle() { return new Dev_Unit_Assert_Iterating_Bundle(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Assert.Iterating.Bundle" extends="Dev.Unit.AssertBundle">
class Dev_Unit_Assert_Iterating_Bundle extends Dev_Unit_AssertBundle {

///   <protocol name="testing">

///   <method name="assert_read" returns="Dev.Unit.Assert.Iterating.Bundle" access="protected">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs"  type="array" />
///     </args>
///     <body>
    public function assert_read($object, $attrs) {
      foreach ($object as $k => $v) {
        if (!isset($attrs[$k]))
          throw new Dev_Unit_FailureException("failed: additional value in iterator for key '$k'");
        if (!Core::equals($v, $attrs[$k]))
          throw new Dev_Unit_FailureException(sprintf("failed: unexpected value for key '$k': %s != %s",
                        $this->stringify($v), $this->stringify($attrs[$k])));
      }
      return $this;
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
