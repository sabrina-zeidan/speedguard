document.addEventListener('DOMContentLoaded', function() {	
	//Mark tests according to scores
	var testscore = document.getElementsByClassName("speedguard-score");
	var i;
	for (i = 0; i < testscore.length; i++) {
		var datascore = testscore[i].getAttribute('data-score');
		if ( datascore > 0.7 ){
				testscore[i].classList.add("score-green");
		 }
		 else if ( datascore > 0.4 ){
				testscore[i].classList.add("score-yellow");
		 }
		 else {
			testscore[i].classList.add("score-red");
		 }  
	}


	//WP Metaboxes close toggle
	
var button = document.querySelector(".handlediv");
button.addEventListener('click', function() {
			   console.log('helloo');
		   });
		   
		   
		   
//console.log(button.closest(".inside"));
// prints HTMLElement



/**

// prints the HTMLDivElement

	var wppostbox = document.getElementsByClassName("postbox");
	
	wppostbox.addEventListener('click', function() {
			   console.log('helloo');
		   });
		   
		   
	var i;
	for (i = 0; i < wppostbox.length; i++) {
		   wppostbox.addEventListener('click', function() {
			   console.log('hello');
		   });
		
		var button = wppostbox.querySelector(".handlediv");
console.log('here');
console.log(button.closest("div"));

	//	document.querySelector('.handlediv').addEventListener("click", function () {
		//	alert("Hello! I am an alert box!!");
    //document.getElementById("button").style.display = "none";
 // });
   
	}




**/
	
	//Autocomplete for the search field
    const min_letters = 2; 
    var autocomplete_field = document.getElementById('speedguard_new_url');
	console.log(autocomplete_field);
    var awesomplete_field = new Awesomplete(autocomplete_field);
	
    // When the user presses and releases a key, get the input value
    autocomplete_field.addEventListener('keyup', function() {
        var user_input = this.value;  // Use another variable for developer clarity
        // If there's enough letters in the field
        if ( user_input.length >= min_letters ) {			
			fetch(   speedguardsearch.search_url + user_input, {
				method: 'GET',
				headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': speedguardsearch.nonce
					  }
			})
			.then( response => {
				  if (response.status !== 200) {
					console.log('Problem! Status Code: ' +
					  response.status);
					return;
				  }				
					response.json().then( posts => {
					var results = [];
					for(var key in posts) {
						var valueToPush = {}; 
						valueToPush["label"] = posts[key].label;	
						valueToPush["value"] = { id: posts[key].ID, permalink: posts[key].permalink, type: posts[key].type};
						results.push(valueToPush);
					}					
					
					awesomplete_field.list = results;  // Update the Awesomplete list
					awesomplete_field.evaluate();  // And tell Awesomplete that we've done so
					
				
					
					});
					
					
					
				})
				.catch(function(err) {
					console.log('Error: ', err);
				});
		
		
        }
    });


	awesomplete_field.replace = function(suggestion) {
	this.input.value = suggestion.value.permalink; //input field 
	document.getElementById("speedguard_new_url_permalink").value = suggestion.value.permalink;
	document.getElementById("speedguard_item_type").value = suggestion.value.type;
	document.getElementById("speedguard_new_url_id").value = suggestion.value.id;
	//document.getElementById("blog_id").value = suggestion.value.id; //Multisite TODO
	
		
    };

});



