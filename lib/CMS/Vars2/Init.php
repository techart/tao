<?php

/**
 * @package CMS\Vars2\Init
 */
class CMS_Vars2_Init implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	static function run()
	{
		if (!CMS::vars()->exists('admin')) {
			CMS::vars()->storage()->create_dir('admin', array(
					'_title' => 'Настройки системы администрирования',
					'_access' => 'full',
				)
			);
		}
		if (!CMS::vars()->exists('admin.title')) {
			$var = CMS::vars()->entity('string');
			$var['_name'] = 'admin.title';
			$var['_access'] = 'full';
			$var['_title'] = 'Заголовок';
			$var['value'] = 'Система управления сайтом';
			CMS::vars()->storage()->save($var);
		}
		if (!CMS::vars()->exists('admin.navigation')) {
			$var = CMS::vars()->entity('array');
			$var['_name'] = 'admin.navigation';
			$var['_access'] = 'full';
			$var['_title'] = 'Структура навигации';
			$var['array'] = array('Настройки' => '/admin/vars/');
			$var['array_src'] = "Настройки = /admin/vars/";
			CMS::vars()->storage()->save($var);
		}
		if (!CMS::vars()->exists('navigation')) {
			$var = CMS::vars()->entity('array');
			$var['_name'] = 'navigation';
			$var['_access'] = 'full';
			$var['_title'] = 'Структура навигации';
			$var['array'] = array();
			$var['array_src'] = '';
			CMS::vars()->storage()->save($var);
		}
		if (!CMS::vars()->exists('head')) {
			$var = CMS::vars()->entity('array');
			$var['_name'] = 'head';
			$var['_access'] = '';
			$var['_title'] = 'Мета-теги по умолчанию';
			$var['array'] = array();
			$var['array_src'] = '';
			CMS::vars()->storage()->save($var);
		}
	}
}