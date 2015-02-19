<?php

Core::load('Text.Process');

/**
 * @package CMS\Fields\Types\Textarea
 */
class CMS_Fields_Types_Textarea extends CMS_Fields_AbstractField implements Core_ModuleInterface
{

	const VERSION = '0.0.0';

	public function enable_multilang()
	{
		return true;
	}

	public function form_fields($form, $name, $data)
	{
		$rc = parent::form_fields($form, $name, $data);
		if (isset($data['value'])) {
			$form[$name] = $data['value'];
		}
		return $rc;
	}

	protected function stdunset($data)
	{
		$res = parent::stdunset($data);
		return $this->punset($res, 'value', 'htmlpurifier');
	}

	public function preprocess($template, $name, $data)
	{
		$template->use_scripts(CMS::stdfile_url('scripts/jquery/tabby.js'));
		parent::preprocess($template, $name, $data);
		$parms = $template->parms;
		$class = isset($parms['tagparms']['class']) ? $parms['tagparms']['class'] : '';
		$class .= ' use-tab-key';
		$class = trim($class);
		$template->update_parm('tagparms', array('class' => $class));
		if (empty($parms['tagparms']['style'])) {
			$template->update_parm('tagparms', array('style' => 'width: 300px;height:100px;'));
		}
		return $template;
	}

}

class CMS_Fields_Types_Textarea_ValueContainer extends CMS_Fields_ValueContainer
{
	public function teaser()
	{
		$spliter = $this->get_spliter();
		if (empty($spliter)) {
			return '';
		}
		$value = $this->value();
		$delimiter = strpos($value, $spliter);
		if ($delimiter !== false) {
			$teaser = substr($value, 0, $delimiter);
			if (!empty($teaser)) {
				$teaser = $this->nl2br($teaser);
				if ($this->is_htmlpurifier()) {
					$teaser = Text_Process::process($teaser, 'htmlpurifier');
				}
			}
			return $teaser;
		} else {
			return $this->nl2br($value);
		}
		return '';
	}

	protected function is_htmlpurifier()
	{
		return isset($this->data['htmlpurifier']) && $this->data['htmlpurifier'];
	}

	public function value()
	{
		$value = parent::value();
		if (empty($value)) {
			return '';
		}
		$value = (string) CMS::lang($value);
		return $value;
	}

	public function get_spliter()
	{
		return isset($this->data['teaser split']) ? $this->data['teaser split'] : null;
	}

	public function has_spliter()
	{
		$spliter = $this->get_spliter();
		if ($spliter) {
			return (boolean)strpos(parent::value(), $spliter);
		}
		return false;
	}

	public function clean_value()
	{
		$value = $this->value();
		$spliter = $this->get_spliter();
		if ($this->has_spliter()) {
			$find = preg_quote($spliter);
			$value = preg_replace("!(<br/?>)?{$find}(<br/?>)?!", '', $value);
			$value = $this->nl2br($value);
		} else {
			$value = $this->nl2br($value);
		}
		if ($this->is_htmlpurifier()) {
			$value = Text_Process::process($value, 'htmlpurifier');
		}
		return $value;
	}

	public function nl2br($value)
	{
		return str_replace( "\n", '<p>', $value );
	}

	public function render()
	{
		return $this->clean_value();
	}
}
