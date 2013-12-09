<?php
/**
 * @package CMS\Lang\Common\En
 */


class CMS_Lang_Common_En implements Core_ModuleInterface { 
	const MODULE = 'CMS.Lang.Common.En'; 
	const VERSION = '0.0.0';
	
	public $pages = 'Pages'; 
	public $next = 'Next'; 
	public $month_i = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'); 
	public $month_r = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
	public $name = 'Name'; 
	public $components = 'Components';
	
	public $ta_list			= "List of items";
	public $ta_norows		= 'No items';
	public $ta_title_edit		= 'Edit';
	public $ta_title_add		= 'Add';
	public $ta_del_confirm		= 'Do you wand to delete this item?';
	public $ta_submit_add		= 'Add';
	public $ta_submit_edit		= 'Update';
	public $ta_save_button		= 'Save and stay edit';
	public $ta_submit_mass_edit	= 'Update items';
	public $ta_button_add		= 'Add item';
	public $ta_button_update	= 'Update';
	public $ta_button_list		= 'View list';
	public $ta_WYSIWYG		= 'WYSIWYG-editor';
	
	public $unable_in_add		= 'Unable in add';
	public $ta_file			= "File";
	public $ta_addfile		= "Add file";
	public $ta_addimg		= "Add image";
	public $ta_adddoc		= "Add document";
	public $ta_delfile		= "Delete";
	public $ta_download		= "Download";
	public $ta_reupload		= "Replace";
	public $ta_upload		= "Upload";
	public $ta_browse		= "Browse";
	public $no_file_uploaded	= "No file uploaded!";
	public $error_file_upload	= "Upload error!";
	public $ta_dfconfirm		= 'Are you sure want to delete this file?';
	public $ta_diconfirm		= 'Are you sure want to delete this image?';
	public $ta_image_error		= 'Image error';
	public $file_not_found		= 'File not found';
	public $a_dir_undef		= '<b>attaches_dir</b> undefined';
	public $no_attachements		= 'No attached files';
	public $captcha			= 'Enter code';
	public $captcha_error		= 'Invalid code';
	
	public $all_languages		= 'All languages';
	public $flush_caches		= 'Flush all caches';

	public $latitude		 = 'Latitude';
	public $longitude		 = 'Longitude';
	public $zoom			 = 'Zoom';
	public $decimal			= 'decimal';
	public $degree			= 'degree';
	public $to_marker		= 'To marker';
	
	public function date_in_human_language($idate) { 
		return $this->month_i[date('m',$idate)-1].' '.date('d',$idate).', '.date('Y',$idate); 
	} 
	
} 

