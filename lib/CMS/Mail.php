<?php

Core::load('Mail');

class CMS_Mail implements Core_ModuleInterface {

	const MODULE = 'CMS.Mail';
	const VERSION = '0.0.0'; 

	static $attaches = array();
	static $multipart = false;

	static function with_images_cb($m) {
		$src = '.'.$m[2];
		self::$multipart = 'related';
		$id = md5($src);
		self::$attaches[$id] = Mail_Message::Part()->file($src)->content_id("<$id>")->content_disposition('inline');
		return $m[1]."=\"cid:$id\"";
	}


/**
 * @param string $body
 * @return Mail_Message
 * 
 */
	static function with_images($body) {
		self::$attaches = array();
		self::$multipart = false;
		$body = preg_replace_callback('{(src)="(/[^"]+)"}',array('CMS_Mail','with_images_cb'),$body);

		$mail = Mail::Message();
		
		if (!self::$multipart) {
			$mail->html($body);
		}

		else {
			if (self::$multipart=='mixed') $mail->multipart_mixed();
			if (self::$multipart=='related') $mail->multipart_related();
			$mail->html_part($body);
			foreach(self::$attaches as $id => $part) $mail->part($part);
		}
		return $mail;

	}


	static function create($tpl,$p1=false,$p2=false) {
		$parms = array();
		$layout = 'mail';
		if (is_string($p1)) $layout = $p1;
		if (is_string($p2)) $layout = $p2;
		if (is_array($p1)) $parms = $p1;
		if (is_array($p2)) $parms = $p2;
		$body = CMS::render($tpl,$parms,$layout);
		return self::with_images($body);
	}

} 

