jQuery(document).ready(function($){
	$("span.speedguard-score").each(function() {        
	    $(this).data('score') > 0.7 ? $(this).children('span').addClass('score-green') : ($(this).data('score') > 0.4 ? $(this).children('span').addClass('score-yellow') : $(this).children('span').addClass('score-red'))  ;
       
    });     
	
	
	
	//All SpeedGuard pages  
	if ($("body").hasClass("post-type-guarded-page") || $("body").hasClass("speedguard_page_speedguard_clients")  || $("body").hasClass("toplevel_page_speedguard_tests") || $("body").hasClass("speedguard_page_speedguard_settings") || $("body").hasClass("speedguard_page_speedguard_settings")){
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
	
	
	}); 


