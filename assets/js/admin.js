var INKGO_JSON_URL = 'https://json.inkgo.io/';
var INKGO_API 	= '';
var inkgo 	= {
	init: function(){
	},
	getData: function(type, callback, no_api){
		if(no_api == undefined)
		{
			var url = INKGO_JSON_URL +'/'+ INKGO_API +'/'+ type;
		}
		else
		{
			var url = INKGO_JSON_URL +'/'+ type;
		}
		jQuery.getJSON(url +'.json', function(result) {
			if(result.data != undefined)
			{
				callback(result.data);
			}
		});
	},
	getSRC: function(url){
		if(url.indexOf('http') == 0)
			var src = url;
		else
			var src = 'https://cdn.inkgo.io/'+url;

		return src;
	},
	variations: function(){
		jQuery('.inkgo-thumb').each(function(index, el) {
			var html = jQuery(this).html();
			var p = jQuery(this).parents('.woocommerce_variable_attributes').find('.form-row-first.upload_image');
			p.unbind();
			p.html(html);
			p.find('a').unbind();
			jQuery(this).remove();
		});
	}
}
jQuery(document).ready(function($) {
	INKGO_API = jQuery('#inkgo-apikey').val();
	inkgo.init();

	jQuery( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(){
		inkgo.variations();
	});
});