<?php

Core::load('Net.HTTP.Session');

class WebKit_Session extends Net_HTTP_Session {

	static public function initialize() {}

	static function SessionObject() {
		return Net_HTTP_Session::Store();
	}

}

