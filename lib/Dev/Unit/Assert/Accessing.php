<?php
/// <module name="Dev.Unit.Assert.Accessing" maintainer="timokhin@techart.ru" version="0.2.3">

/// <class name="Dev.Unit.Assert.Accessing">
///   <implements interface="Dev.Unit.AssertBundleModuleInterface" />
class Dev_Unit_Assert_Accessing implements Dev_Unit_AssertBundleModuleInterface {

///   <constants>
  const VERSION = '0.2.3';
///   </constants>

///   <protocol name="building">

///   <method name="bundle" scope="class" returns="Dev.Unit.AssertBundle">
///     <body>
  static public function bundle() { return new Dev_Unit_Assert_Accessing_Bundle(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Assert.Accessing.Bundle" extends="Dev.Unit.AssertBundle">
class Dev_Unit_Assert_Accessing_Bundle extends Dev_Unit_AssertBundle {

///   <protocol name="testing">

///   <method name="assert_read" returns="Dev.Unit.Assert.Accessing.Bundle" access="protected">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs"  type="array" />
///     </args>
///     <body>
    public function assert_read($object, array $attrs) {
      foreach ($attrs as $k => $v) {
        if ($v === null && isset($object->$k))
          throw new Dev_Unit_FailureException("failed: non null value is set for Object->$k");
        if ($v !== null && !isset($object->$k))
          throw new Dev_Unit_FailureException("failed: null value is set for Object->$k");
        $this->set_trap();
        try {
          if (!Core::equals($object->$k, $v))
            throw new Dev_Unit_FailureException(sprintf("failed: unexpected value for Object->$k: %s != %s",
              $this->stringify($v), $this->stringify($object->$k)));
        } catch (Core_MissingPropertyException $e) {
          $this->trap($e);
        }
        if ($this->is_catch_prey()) throw new Dev_Unit_FailureException("failed: can't read Object->$k");
      }
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_exists" returns="Dev.Unit.Assert.Accessing.Bundle" access="protected">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs"  type="array" />
///     </args>
///     <body>
    public function assert_exists($object, array $attrs) {
      foreach ($attrs as $k) {
        if (!isset($object->$k))
          throw new Dev_Unit_FailureException("failed: value not exist: Object->{$k}");
        if (is_null($object->$k))
          throw new Dev_Unit_FailureException("failed: null value: Object->{$k}");
      }
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_exists_only" returns="Dev.Unit.Assert.Accessing.Bundle" access="protected">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs"  type="array" />
///     </args>
///     <body>
    public function assert_exists_only($object, array $attrs) {
      foreach ($attrs as $k) {
        if (!isset($object->$k))
          throw new Dev_Unit_FailureException("failed: value not exist: Object->{$k}");
        if (is_null($object->$k))
          throw new Dev_Unit_FailureException("failed: null value: Object->{$k}");

        $this->set_trap();
        try {
          $object->$k = rand();
        } catch (Core_ReadOnlyPropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyObjectException $e) {
          $this->trap($e);
        }
        if (!$this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: no exception when writing Object->$k");
      }
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_read_only" returns="Dev.Unit.Assert.Accessing.Bundle" access="protected">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs" type="array" />
///     </args>
///     <body>
    public function assert_read_only($object, array $attrs) {
      foreach ($attrs as $k => $v) {
        if ($v === null && isset($object->$k))
          throw new Dev_Unit_FailureException("failed: non null value is't set for Object->$k");

        if ($v !== null && !isset($object->$k))
          throw new Dev_Unit_FailureException("failed: null value is set for Object->$k");

        $this->set_trap();
        try {
          if (!Core::equals($object->$k, $v))
              throw new Dev_Unit_FailureException(sprintf("failed: unexpected value for Object->$k: %s != %s",
                $this->stringify($v), $this->stringify($object->$k)));
        } catch (Core_MissingPropertyException $e) {
          $this->trap($e);
        }
        if ($this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: exception when reading Object->$k");
      }

      foreach ($attrs as $k => $v) {
        $this->set_trap();
        try {
          $object->$k = $v;
        } catch (Core_ReadOnlyPropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyObjectException $e) {
          $this->trap($e);
        }
        if (!$this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: no exception when writing Object->$k");
      }

      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_write" returns="Dev.Unit.Assert.Accessing.Bundle" access="protected">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs" type="array" />
///     </args>
///     <body>
    public function assert_write($object, array $attrs) {
      foreach ($attrs as $k => $v) {
        $this->set_trap();
        try {
          $object->$k = $v;
          if (!Core::equals($object->$k, $v))
            throw new Dev_Unit_FailureException(sprintf("failed: can't change Object->$k: %s != %s",
              $this->stringify($v), $this->stringify($object->$k)));
        } catch (Core_MissingPropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyPropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyObjectException $e) {
          $this->trap($e);
        }
        if ($this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: can't write Object->$k");
      }

      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_undestroyable" returns="Dev.Unit.Assert.Accessing.Bundle">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs" type="array|string" />
///     </args>
///     <body>
    public function assert_undestroyable($object, $attrs) {
      foreach ((array) $attrs as $attr) {
        $this->set_trap();
        try {
          unset($object->$attr);
        } catch (Core_UndestroyablePropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyObjectException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyPropertyException $e) {
          $this->trap($e);
        }
        if (!$this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: no exception when unsetting Object->$attr");
      }
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_nullable" returns="Dev.Unit.Assert.Accessing.Bundle">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs" type="string|array" />
///     </args>
///     <body>
    public function assert_nullable($object, $attrs) {
      foreach ((array) $attrs as $attr) {
        $this->set_trap();
        try {
          unset($object->$attr);
          if (isset($object->$attr))
            if (!is_null($object->$attr))
              throw new Dev_Unit_FailureException("failed: can't nullify Object->$attr: value still exist");
        } catch (Core_UndestroyablePropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyPropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyObject $e) {
          $this->trap($e);
        }
        if ($this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: can't nullify Object->$attr");
      }
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_missing" returs="Dev.Unit.Assert.Accessing.Bundle">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs" type="array|string" default="undefined" />
///     </args>
///     <body>
    public function assert_missing($object, $attrs = 'undefined') {
      foreach ((array) $attrs as $attr) {
        if (isset($object->$attr) !== false)
          throw new Dev_Unit_FailureException("failed: missing property exists Object->$attr");

        $this->set_trap();
        try {
          $object->$attr;
        } catch (Core_MissingPropertyException $e) {
          $this->trap($e);
        }
        if (!$this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: no exception when reading missing Object->$attr");

        $this->set_trap();
        try {
          $object->$attr = rand();
        } catch (Core_MissingPropertyException $e) {
          $this->trap($e);
        } catch (Core_ReadOnlyObjectException $e) {
          $this->trap($e);
        }
        if (!$this->is_catch_prey())
          throw new Dev_Unit_FailureException("failed: no exception when writing missing Object->$attr");
      }
      return $this;
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
