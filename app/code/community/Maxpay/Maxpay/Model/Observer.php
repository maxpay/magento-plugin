<?php
class Maxpay_Maxpay_Model_Observer
{
	public function addAutoloader()
	{
		spl_autoload_register(array($this, 'load'), true, true);
	}

	/**
	 * This function can autoloads classes starting with:
	 * - Maxpay
	 * - Psr
	 *
	 * @param string $class
	 */
	public static function load($class)
	{
		if (preg_match('/^(Maxpay|Psr)\b/', $class)) {
			$phpFile = Mage::getBaseDir('lib') . DS . str_replace('\\', DS, $class) . '.php';
			if (file_exists($phpFile)) {
				require_once($phpFile);
			}
		}
	}
}