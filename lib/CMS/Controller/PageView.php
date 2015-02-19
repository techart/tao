<?php
/**
 * CMS.Controller.PageView
 *
 * @package CMS\Controller\PageView
 * @version 0.0.0
 */

/**
 * @package CMS\Controller\PageView
 */
class CMS_Controller_PageView extends CMS_Controller implements Core_ModuleInterface
{

	const MODULE = 'CMS.Controller.PageView';
	const VERSION = '0.0.0';

	protected $perpage = 15;
	protected $template_list = 'list';
	protected $template_item = 'item';

	/**
	 * @return CMS_Controller_PageView
	 */
	public function setup()
	{
		return parent::setup()->render_defaults('perpage');
	}

	/**
	 * @param int $pagenum
	 *
	 * @return WebKit_Views_TemplateView
	 */
	public function view_list($pagenum)
	{
		return $this->page($pagenum);
	}

	/**
	 * @param int $id
	 *
	 * @return WebKit_Views_TemplateView
	 */
	public function view_item($id)
	{
		return $this->view($id);
	}

	/**
	 * @return int
	 */
	protected function count_all()
	{
		return 0;
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return iterable
	 */
	protected function select_all($offset, $limit)
	{
		return array();
	}

	/**
	 * @param int $id
	 *
	 * @return entity
	 */
	protected function select_one($id)
	{
		return array();
	}

	/**
	 * @param int $pagenum
	 *
	 * @return WebKit_Views_TemplateView
	 */
	public function page($pagenum)
	{
		$pagenum = (int)$pagenum;
		if ($pagenum < 1) {
			$page_num = 1;
		}
		$count = $this->count_all();
		$numpages = $count / $this->perpage;

		if (floor($numpages) != $numpages) {
			$numpages = floor($numpages) + 1;
		}
		if ($numpages < 1 || $pagenum > $numpages) {
			$numpages = 1;
		}

		if ($pagenum < 1 || $pagenum > $numpages) {
			return $this->page_not_found();
		}

		$rows = $this->select_all(($pagenum - 1) * $this->perpage, $this->perpage);

		return $this->render_list($this->template_list, array(
				'pagenum' => $pagenum,
				'numpages' => $numpages,
				'count' => $count,
				'rows' => $rows,
				'page_navigator' => CMS::page_navigator($pagenum, $numpages, $this->page_url('%')),
			)
		);
	}

	/**
	 * @param string $template
	 * @param array  $parms
	 *
	 * @return WebKit_Views_TemplateView
	 */
	public function render_list($tpl, $parms)
	{
		return $this->render($tpl, $parms);
	}

	/**
	 * @param int $id
	 *
	 * @return WebKit_Views_TemplateView
	 */
	public function view($id)
	{
		$item = $this->select_one($id);
		if (!$item) {
			return $this->page_not_found();
		}
		return $this->render_item($this->template_item, array(
				'id' => $id,
				'item' => $item,
			)
		);
	}

	/**
	 * @param string $template
	 * @param array  $parms
	 *
	 * @return WebKit_Views_TemplateView
	 */
	public function render_item($tpl, $parms)
	{
		return $this->render($tpl, $parms);
	}

} 

