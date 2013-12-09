<?php
/**
 * @package CMS\Lang\Vars\En
 */


class CMS_Lang_Vars_En implements Core_ModuleInterface {

	const MODULE  = 'CMS.Lang.Vars.En';
	const VERSION = '0.0.0';

	public $title			= 'Config';
	public $root_dir 		= 'Root directory'; 
	
	public $dir 			= 'Subdirectory'; 
	public $int		 		= 'Integer'; 
	public $string 			= 'String'; 
	public $text 			= 'Plain text'; 
	public $html			= 'HTML text'; 
	public $array 			= 'Array data'; 
	public $file 			= 'Uploaded file'; 
	public $mail 			= 'E-Mail message'; 
	public $htmp 			= 'HTML with parameters';

	public $help 			= 'Help';

	public $invalid_int		= 'Invalid integer value';
	public $levelup			= 'Parent level';
	public $add_file		= 'Add file';
	public $creation		= 'Create new config entry';
	public $dump_gen		= 'Dump downloading';
	public $dump_get		= 'Download dump';
	public $dump_uploading		= 'Dump uploading';
	public $dump_upload		= 'Open your dump file';
	public $dump_about		= 'Dump is a plain text file used for saving config and for moving it between hosings. This file includes a text data and attached files (in Base64 format).';
	
	public $type			= 'Type';
	public $identifier		= 'Identifier';
	public $comment			= 'Comment';
	public $restricted		= 'Restricted access';
	
	public $no_vars			= 'No entries';
	public $no_attaches		= 'No attached files';
	
	public $submit_edit		= 'Update';
	public $submit_add		= 'Add';
	public $submit_create		= 'Create';
	public $submit_upload		= 'Upload';
	
	public $confirm_delete_var		= 'Do you want to delete this config entry?';
	public $confirm_delete_file		= 'Do you want to delete this file?';
	
	public $tab_text 		= 'Content';
	public $tab_attaches	= 'Attached files';
	public $tab_parms 		= 'Parameters';
	
}


