<?php
/// <module name="CMS.Controller.PageView" maintainer="gusev@techart.ru" version="0.0.0">
/// <class name="CMS.Controller.PageView" extends="CMS.Controller" stereotype="module">

class CMS_Controller_PageView extends CMS_Controller implements Core_ModuleInterface { 

///   <constants>
	const MODULE = 'CMS.Controller.PageView'; 
	const VERSION = '0.0.0'; 
///   </constants>
	
	protected $perpage = 15; 
	protected $template_list = 'list'; 
	protected $template_item = 'item'; 

///   <protocol name="creating">

///   <method name="setup" returns="CMS.Controller.PageView">
///     <body>
	public function setup() { 
		return parent::setup()->render_defaults('perpage'); 
	} 
///     </body>
///   </method>

///   </protocol>	
	
	
///   <protocol name="actions">

///   <method name="view_list" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="pagenum" type="int" />
///     </args>
///     <body>
	public function view_list($pagenum) { 
		return $this->page($pagenum); 
	} 
///     </body>
///   </method>
	

///   <method name="view_item" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
	public function view_item($id) { 
		return $this->view($id); 
	} 
///     </body>
///   </method>
	
	

///   </protocol>	
	

	
///   <protocol name="datasource">

///   <method name="count_all" returns="int">
///     <body>
	protected function count_all() { 
		return 0; 
	} 
///     </body>
///   </method>
	
///   <method name="select_all" returns="iterable">
///     <args>
///       <arg name="offset" type="int" />
///       <arg name="limit" type="int" />
///     </args>
///     <body>
	protected function select_all($offset,$limit) { 
		return array(); 
	} 
///     </body>
///   </method>
	
///   <method name="select_one" returns="entity">
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
	protected function select_one($id) { 
		return array(); 
	} 
///     </body>
///   </method>

///   </protocol>	
	
	
	
///   <protocol name="supporting">
	
	
	
///   <method name="page" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="pagenum" type="int" />
///     </args>
///     <body>
	public function page($pagenum) { 
		$pagenum = (int)$pagenum; 
		if ($pagenum<1) $page_num = 1; 
		$count = $this->count_all(); 
		$numpages = $count/$this->perpage; 
		
		if (floor($numpages)!=$numpages) $numpages = floor($numpages)+1; 
		if ($numpages<1||$pagenum>$numpages) $numpages = 1; 
		
		if ($pagenum<1||$pagenum>$numpages) return $this->page_not_found();
		
		$rows = $this->select_all(($pagenum-1)*$this->perpage,$this->perpage); 
		
		return $this->render_list($this->template_list,array( 
			'pagenum' => $pagenum, 
			'numpages' => $numpages, 
			'count' => $count, 
			'rows' => $rows, 
			'page_navigator'=> CMS::page_navigator($pagenum,$numpages,$this->page_url('%')), 
		)); 
	} 
///     </body>
///   </method>
	
///   <method name="render_list" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="template" type="string" />
///       <arg name="parms" type="array" />
///     </args>
///     <body>
	public function render_list($tpl,$parms) { 
		return $this->render($tpl,$parms); 
	} 
///     </body>
///   </method>
	
///   <method name="view" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
	public function view($id) { 
		$item = $this->select_one($id); 
		if (!$item) return $this->page_not_found(); 
		return $this->render_item($this->template_item,array( 
			'id' => $id, 
			'item' => $item, 
		)); 
	} 
///     </body>
///   </method>
	
///   <method name="render_item" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="template" type="string" />
///       <arg name="parms" type="array" />
///     </args>
///     <body>
	public function render_item($tpl,$parms) { 
		return $this->render($tpl,$parms); 
	} 
///     </body>
///   </method>
	
///   </protocol>	

	
	
} 
/// </class>

/// </module>
