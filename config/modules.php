<?php

return array(
	'Templates.HTML' => array(
		'template_class' => 'CMS.Views.View',
		'add_timestamp' => true
	),
	'WS.DSL' => array(
		'middleware' => array(
			'cms_std' => 'CMS.Handlers.StdControlsHandler',
			'cms_configure' => 'CMS.Handlers.Configure',
			'cms_restricted' => 'CMS.Handlers.RestrictedRealms',
			'cms_static' => 'CMS.Handlers.Static',
			'cms_realm_auth' => 'CMS.Handlers.RealmAuth',
		),
		'handlers' => array(
			'cms_action' => 'CMS.Handlers.ActionHandler'
		),
	)
);