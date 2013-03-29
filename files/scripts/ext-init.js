Ext.require([
    'Ext.tip.*',
]);


Ext.onReady(function() {

	Ext.QuickTips.init();

	Ext.state.Manager.setProvider(new Ext.state.CookieProvider({
			expires: new Date(new Date().getTime()+(1000*60*60*24*7)), //7 days from now
	}));
	
});
