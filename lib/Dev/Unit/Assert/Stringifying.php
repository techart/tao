<?php
/**
 * Dev.Unit.Assert.Stringifying
 * 
 * @package Dev\Unit\Assert\Stringifying
 * @version 0.2.0
 */

/**
 * @package Dev\Unit\Assert\Stringifying
 */
class Dev_Unit_Assert_Stringifying implements Dev_Unit_AssertBundleModuleInterface {

  const VERSION = '0.2.1';


/**
 * @return Dev_Unit_Assert_Stringifying_Bundle
 */
  static public function bundle() { return new Dev_Unit_Assert_Stringifying_Bundle(); }

}


/**
 * @package Dev\Unit\Assert\Stringifying
 */
class Dev_Unit_Assert_Stringifying_Bundle extends Dev_Unit_AssertBundle {


/**
 * @param object $object
 * @param string $value
 * @return Dev_Unit_Assert_Stringifying_Bundle
 */
    public function assert_string($object, $value) {
      if (($object instanceof Core_StringifyInterface) &&
          ($object->as_string() === (string) $value) &&
          ($object->__toString() === (string) $value))
        return $this;
      else
        throw new Dev_Unit_FailureException(sprintf("failed: strings don't match: %s != %s",
          $this->stringify($value), $this->stringify($object)));
    }


}

?>