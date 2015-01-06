<?php
// Version 1.0 6/15/2014
// Version 1.1 1/1/2015 - Fixed bugs with submit option not showing. Also added in dummy function hide_section. It doesn't, nor does it need to do anything. 
// Class designed by Justin L of hybrid web dev. Major thanks to:
// http://www.chipbennett.net/2011/02/17/incorporating-the-settings-api-in-wordpress-themes/ 
// for helping me finally get a full understanding of the WP settings API.
 
class option_class {
	
	public function __construct($array = array()) {
	
		$this->self_define();
	
		$this->options_array = get_option($this->options_name); //our options are here. 
		
		$this->build_options();
	
		if($array['just_options']) 
			return; // if all we want is the plugin options, we can forgo the rest.
		
		$this->build_arrays(); //Where you enter the arrays to populate forms. 
		$this->tab_builder();
		
		$this->hook_menus();
	
	}
	

	
	public function hook_menus () {
			
		if ( is_admin() ){
			
			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) ); //Hooks our register settings function. 
		
		}	
		
	}
	
	public function tab_builder () {
			
		// this little bit here, so we know which tab settings to hook. 
		
		$this->tab_selected =  (($_GET['tab'])&& (in_array($_GET['tab'],$this->tab_list)))  //self validation, woohoo
		? $_GET['tab']	
		: $this->tab_list[0]; // There should ALWAYS be at least ONE tab defined. 
		
	}

	public function check_form_box($mid) { // checking if a box is checked or not
	
		 return (isset($mid) ) ? "true" : "";
		 
	}

	

	public function add_admin_menus() {
		
		$this->hook = add_options_page( $this->page_title, $this->menu_title, 'manage_options', $this->options_page, array( &$this, 'plugin_options_page' ) );
		
	}
	
		
	public function hide_section () { // Dummy function. Does nadda. 
		
	}
	
	public function register_settings() {
		/** 
		* @param Builds the settings list, conditional to the tab we're on. 
		* what this function does, is call the add_settings_section and add_settings_fields
		* based on the tab that is passed to it. It loops through each section and setting, and decides
		* if it should be placed on the current tabbed page. It also build $this->group list, which checks
		* each setting field, to decide if it should be shown based on the group it belongs to. Essentially
		* each section/setting is bound by the "Group", so only settings that have a group that is the same
		* as one of the sections is shown. This basically automatically keeps the arrays small, and allows  
		* full control of what settings are shown, based on the sections displayed. 
		*/
		
		$this->hide_section = array();
		$this->hide_setting = array();
		$this->group_list = array();
			
		register_setting(
			$this->options_name,
			$this->options_name, // the array we're using to store the options.
			array( $this, $this->sanitize )
		);
		
		// CB for sanitizing forms.
		foreach ($this->section as $section) {
		
			if($section['tab'] == $this->tab_selected) {
			
				add_settings_section(
					$section['group'],
					$section['section_header'],
					array(&$this, $section['callback']),
					$this->options_page
				);
				
				if($section['surpress_form']) 
					$this->surpress_form = true;
				
				
				if(!in_array($section['group'], $this->group_list)) 
					$this->group_list[] =$section['group'];
			
			} else {
				
				$this->hide_section[] = $section;
				
			}
		
		}
		
		
		foreach ($this->setting as $setting) {
			
			if(in_array($setting['group'], $this->group_list)) {
					
				add_settings_field(
					$setting['id'],   
					$setting['title'], 
					array(&$this, $setting['callback']), 
					$this->options_page,  
					$setting['group']
				); 
			
			} else {
				
				$this->hide_setting[] = $setting;
					
			}
		
		}
		
		// this is the unused sections by tab. We render these, but then hide them on the page. We have to do this, otherwise when 
		//we validate settings, the unused sections would be erased. 
		
		foreach($this->hide_section as $sect) 
		
			add_settings_section(
				$sect['group']."_hide_this_group_from_list",
				$sect['section_header'],
				array(&$this, 'hide_section'),
				$this->options_page
			);	
			
		
		foreach($this->hide_setting as $set) 
		
			add_settings_field(
				$set['id'],   
				$set['title'], 
				array(&$this, $set['callback']), 
				$this->options_page,  
				$set['group']."_hide_this_group_from_list"
			); 	
		

	}

	public function custom_do_settings_sections($page) { // in conjuction with marking unused sections as hidden, we check here and hide then. 
			// this function is a duplicate of the wordpress function. Unfortunately there's no hook for this, so we're forced to fudge it. 
	 
	    global $wp_settings_sections, $wp_settings_fields;
	 
	    if ( !isset($wp_settings_sections) || !isset($wp_settings_sections[$page]) )
	         return;
	 
	     foreach ( (array) $wp_settings_sections[$page] as $section ) {
	 		
	    	$pos = strpos($section['id'], '_hide_this_group_from_list');
			
		 	if($pos != false)
				echo "<div class='no_show' style='display:none;visibility: hidden'>";
	 		 
			    	if ($section['title']!=' ') echo "<h3>{$section['title']}</h3>";
			 
			        call_user_func($section['callback'], $section);
			
			if(!$section['surpress_form']) 
			        echo '<table class="form-table">';
			 
		       	 		do_settings_fields($page, $section['id']);
						
		    if(!$section['surpress_form'])
			        echo '</table>';
	 		
	 		if($pos != false) 
	 			echo "</div>";
			 
	    }
		 
	}


	public function plugin_options_page() {
	        	 
        echo "<div class='{$this->form_wrapper}'>";
	        
	        if (!$this->surpress_form) 
	        	echo "<form method='post' action='options.php' enctype='multipart/form-data'>";
		        
	        $this->plugin_options_tabs(); 
	       
		    wp_nonce_field( $this->options_page);
			        
	        settings_fields($this->options_name); 
			
	        $this->custom_do_settings_sections($this->options_page); 
		     
			             
	    	if (!$this->surpress_form) {
	    			
	    		echo "<div class='submit_wrapper'>";
					submit_button();
				echo "</div>";
				
	    	} 
				 
		                
	        if(!$this->surpress_form) 
	            echo "</form>";
	            
        echo "</div>";
		
    } 

	public function plugin_options_tabs() {
			
		if(count($this->tab_list) > 1) {
			echo "<div class='nav_wrapper'>";
				
			foreach ($this->tab_list as $tab) {?>
				<a class="nav-tab<?php if ($tab==$this->tab_selected) echo " selected";?>" href="<?php echo "?page=$this->options_page"."&tab=$tab"?>"><?php echo $tab;?></a>
			<?php }
			echo "</div>";
		}
	
	}
	
	public function build_arrays() {
		/**
		* @param This is where you enter the arrays for building the menus. 
		* 
		* - 'Group' binds the settings to the section. Any setting that has the same group as a section will be shown in that section.  
		* 
		* - 'section_header' is the header title for the section
		* 
		* - if supress_form is set to true, then the page that this tab shows on will have the form portion disabled. Again, useful if you want 
		* to create a splash screen, or page where you do not use the form.  
		* 
		* - 'callback' defines the functions names that display the data for those fields. 
		* For sections, it can be used to display extra text below the header. For settings, it renders the right section, ie:
		* where the form fiels for that setting would be. It's also worth noting, that if you just want to display extra data, you can define a section 
		* without a setting field, and then use the section callback to call in any extra data you want displayed. Useful for when you want to create
		* extra splash screens etc.  
		* 
		* - 'Tab' is used for grouping sections by page tab. This is also the text that will show up within the tab. 
		* Also note, that if only 1 'Tab' is defined, then No tabbed navigation will appear, and sections/settings will just
		* show up on the same page.  
		* 
		* - 'id' is what is added to the wrapper for the setting. It MUST be unique for each setting added. 
		* 
		* - 'title' is the text that appears on the left side of the setting.
		**/
		
		$this->define_option_arrays(); // Mandatory, this sets up the options for processing. 
		
		$this->tab_list = array();
		
		//Setting Group and section group MUST be the same. 
		foreach ($this->section as $section) 
			if(!in_array($section['tab'], $this->tab_list)) 
				$this->tab_list[] = $section['tab'];	
	
	}

} // End o class. 

