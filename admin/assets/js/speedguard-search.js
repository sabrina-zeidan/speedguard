jQuery(function($){
	/**
	jQuery.ajax({
            url: "/rest/abc",
            type: "GET",

            contentType: 'application/json; charset=utf-8',
            success: function(resultData) {
                //here is your json.
                  // process it

            },
            error : function(jqXHR, textStatus, errorThrown) {
            },

            timeout: 120000,
        });
		
	**/
    var getData = function (request, response) {
        $.getJSON(
			window.location.protocol + "//" + window.location.hostname + "/wp-json/speedguard/search?term=" + request.term,
            function (data) {
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
				//response(results); 
				 response(results.slice(0, 6));
				 
				}				
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


	
/**
	fetch('/wp-json/speedguard/search', {
    credentials: 'include',
    headers: {
      'content-type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    }
})
.then(response => response.json())
.then(console.log)
.catch(console.warn)
**/

