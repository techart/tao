<?php

class WebKit_Views {
  static public function TemplateView($tpl,$parms=array()) {
	return Templates_HTML::Template($tpl)->with($parms);
  }
}

