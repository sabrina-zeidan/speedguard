jQuery(function($){
    var getData = function (request, response) {
		jQuery.ajax({
            url: speedguardsearch.search_url + request.term,
            type: "GET",
            contentType: 'application/json; charset=utf-8',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', speedguardsearch.nonce ); //no need to verify that the nonce is valid inside your custom end point
			},
            success: function(data) {
				if ( data !== null ) {
					var results = [];
					console.log(results);
					for(var key in data) {
						var valueToPush = { }; // or "var valueToPush = new Object();" which is the same
						valueToPush["label"] = data[key].label;
						valueToPush["value"] = data[key].ID;
						valueToPush["permalink"] = data[key].permalink;
						valueToPush["type"] = data[key].type;
						results.push(valueToPush);	
					}					
				 var result_to_show = response(results.slice(0, 6));
				}      
            },
            error : function(xhr, textStatus, errorThrown) {
				 //console.log('error message here');
            },

            timeout: 5000,
        });
		
		
    };
 
 
    var selectItem = function (event, ui) {
		event.preventDefault();
		$("#speedguard_new_url").val(ui.item.label);
		$("#speedguard_new_url_permalink").val(ui.item.permalink);
		$("#speedguard_item_type").val(ui.item.type);
		$("#speedguard_new_url_id").val(ui.item.value);
		$("#blog_id").val(ui.item.blog_id);
		

    }
 
    $('input[name="speedguard_new_url"]').autocomplete({
        source: getData,
        select: selectItem,
        minLength: 2,
    });
});