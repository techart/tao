// FIXME: MOVE TO require.js!!!!!!!!!!!!!
TAO.require([
	'tao/oop',
	'SlickGrid/slick.table',
	'SlickGrid/slick.tree.class',
	'SlickGrid/slick.remotestore'
	],
	function() {
		$(function () {
			var dataStore = new Slick.Data.RemoteStore();
			var grid = new Slick.Tree(".slick-grid", dataStore);
		});
});