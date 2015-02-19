<?php
/**
 * @package Text\Filter\PlainText
 */

Core::load('Text.Process');

class Text_Filter_PlainText implements Core_ModuleInterface, Text_Process_ProcessInterface
{
	const VERSION = '0.1.0';

	public function configure($c)
	{
	}

	public function process($text)
	{
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}

}
