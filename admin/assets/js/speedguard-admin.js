jQuery(document).ready(function($){
	//Feedback notice
	$( "#leave-feedback" ).click(function() {
		$( "#feedback-form" ).toggle( 'slow');
	});
	//On SG activation
	if ($("body").hasClass("plugins-php") && $("body").hasClass("speedguard-just-activated")) {
		//$('#toplevel_page_speedguard').focus();	
		setInterval(function() { 
			$("#toplevel_page_speedguard").animate({backgroundColor: "#0073aa;"});
			$("#toplevel_page_speedguard").animate({backgroundColor: "transparent" });
			$("#toplevel_page_speedguard").animate({backgroundColor: "#0073aa;"});
			$("#toplevel_page_speedguard").animate({backgroundColor: "transparent" }); 
			$("#toplevel_page_speedguard").animate({backgroundColor: "#0073aa;"});
			$("#toplevel_page_speedguard").animate({backgroundColor: "" }); 
		},3000);
	}	
	//Both SG pages
	if ($("body").hasClass("post-type-guarded-page") || $("body").hasClass("speedguard_page_speedguard_tests") || $("body").hasClass("speedguard_page_speedguard_settings")){
		postboxes.add_postbox_toggles( 'speedguard');	
		//Tips Attention Seeker
		if  (!$("body").hasClass("no-guarded-pages")) {
			setInterval(function() { 
				$("#speedguard-tips-meta-box").animate({backgroundColor: "#8ce6ff" });
				$("#speedguard-tips-meta-box").animate({backgroundColor: "transparent" });
				$("#speedguard-tips-meta-box").animate({backgroundColor: "#8ce6ff" });
				$("#speedguard-tips-meta-box").animate({backgroundColor: "" }); 
				
			},20000);  
		}		
	}	
	//SpeedGuard Tests Page
	if ($("body").hasClass("speedguard_page_speedguard_tests")){
		$("#quick_press_box").appendTo(".wrap");
		$("#poststuff").appendTo(".wrap");
		$("#posts-filter").appendTo("#place-here");
		$('#speedguard_new_url').focus();
		//No guarded pages yet
		if ($("body").hasClass("no-guarded-pages")){
			$( "#speedguard-results-meta-box" ).addClass( "closed" );
			setInterval(function() {
				$("#speedguard_new_url").animate({outlineColor: '#8ce6ff', }, 'slow');
				$("#speedguard_new_url").animate({outlineColor: 'transparent' }, 'slow');
				$("#speedguard_new_url").animate({outlineColor: '#8ce6ff' }, 'slow');
				$("#speedguard_new_url").animate({outlineColor: 'transparent' }, 'slow');
				$("#speedguard_new_url").animate({outlineColor: '#8ce6ff' }, 'slow');
				$("#speedguard_new_url").animate({outlineColor: '' }, 'slow');  
			},3000); 			
		}
	}

}); 


