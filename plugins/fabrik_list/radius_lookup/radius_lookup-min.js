/*! Fabrik */
var FbListRadiusLookup=my.Class(FbListPlugin,{options:{},constructor:function(a){this.parent(a),"null"===typeOf(this.options.value)&&(this.options.value=0);var b=this.listform.getElement(".clearFilters");return console.log("clear = ",b),b.addEvent("mouseup",function(){this.clearFilter()}.bind(this)),"null"!==typeOf(this.listform)&&(this.listform=this.listform.getElement("#radius_lookup"+this.options.renderOrder),"null"===typeOf(this.listform))?void fconsole("didnt find element #radius_lookup"+this.options.renderOrder):void("null"!==typeOf(this.listform)&&geo_position_js.init()&&geo_position_js.getCurrentPosition(function(a){this.setGeoCenter(a)}.bind(this),function(a){this.geoCenterErr(a)}.bind(this),{enableHighAccuracy:!0}))},setGeoCenter:function(a){this.geocenterpoint=a,this.geoCenter(a)},geoCenter:function(a){"null"===typeOf(a)?alert(Joomla.JText._("PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE")):(this.listform.getElement("input[name=radius_search_lat"+this.options.renderOrder+"]").value=a.coords.latitude.toFixed(2),this.listform.getElement("input[name=radius_search_lon"+this.options.renderOrder+"]").value=a.coords.longitude.toFixed(2))},clearFilter:function(){return this.listform.getElements("select").set("value",""),!0}});