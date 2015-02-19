<?php
/**
 * Dev.Unit.Assert.Iterating
 *
 * @package Dev\Unit\Assert\Iterating
 * @version 0.1.1
 */

/**
 * @package Dev\Unit\Assert\Iterating
 */
class Dev_Unit_Assert_Iterating implements Dev_Unit_AssertBundleModuleInterface
{

	const VERSION = '0.1.1';

	/**
	 * @return Dev_Unit_Assert_Iterating_Bundle
	 */
	static public function bundle()
	{
		return new Dev_Unit_Assert_Iterating_Bundle();
	}

}

/**
 * @package Dev\Unit\Assert\Iterating
 */
class Dev_Unit_Assert_Iterating_Bundle extends Dev_Unit_AssertBundle
{

	/**
	 * @param object $object
	 * @param array  $attrs
	 *
	 * @return Dev_Unit_Assert_Iterating_Bundle
	 */
	public function assert_read($object, $attrs)
	{
		foreach ($object as $k => $v) {
			if (!isset($attrs[$k])) {
				throw new Dev_Unit_FailureException("failed: additional value in iterator for key '$k'");
			}
			if (!Core::equals($v, $attrs[$k])) {
				throw new Dev_Unit_FailureException(sprintf("failed: unexpected value for key '$k': %s != %s",
					$this->stringify($v), $this->stringify($attrs[$k])
				));
			}
		}
		return $this;
	}

}

