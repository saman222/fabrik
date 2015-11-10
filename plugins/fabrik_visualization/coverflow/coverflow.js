/**
 * Coverflow Visualization
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbVisCoverflow = my.Class({
	options: {},
	constructor: function (json, options) {
		json = eval(json);
		this.setOptions(options);

		widget = Runway.createOrShowInstaller(
			document.getElementById("coverflow"),
			{
				// examples of initial settings
				// slideSize: 200,
				// backgroundColorTop: "#fff",

				// event handlers
				onReady: function () {
					widget.setRecords(json);
				}
			}
		);
	}
});
