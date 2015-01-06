<?php class NGG_pup_options extends option_class {
		
	public function build_options () {
		
		$this->image_extensions = array("jpg","png","gif","jpeg","bmp",'tiff');
		$this->form_builder = new ngg_formz();
		$this->form_builder->render_manual('on');	
		$this->form_wrapper = "ngg_options_wrap";
		
		$this->options_array["plugin_db_version"]="2.0";	
		$this->options_array['plugin_db'] = "ngg_upload_queue";
		$this->options_array['ngg_pic_table'] = "ngg_pictures";
		$this->options_array['ngg_gal_table'] = "ngg_gallery";	
		
		$this->ngg_options = get_option('ngg_options');
		
		$wp_roles = new WP_Roles();
		$this->roles = $wp_roles->get_names();
		
		if ($this->ngg_options['gallerypath'] != "") 
			$this -> options_array['Nextgen_base_directory'] = $this->ngg_options['gallerypath']; // we're trying to auto-populate the directory here if possible.
		
	}

	public function donate_page () {
		
	}
	
	public function define_option_arrays() {
		$this->section[]=array(
		'group'=>"Group 1",
		'section_header'=>" ",
		'callback'=>"display",
		'tab'=>"Settings",
		);
		
		
		$this->setting[]=array(
		'id'=>'Upload_Directory',
		'title'=>'Upload Directory',
		'callback'=>'settings_1',
		'group'=>'Group 1'
		);

		$this->setting[]=array(
		'id'=>'Upload_Access',
		'title'=>'Upload Access',
		'callback'=>'settings_2',
		'group'=>'Group 1'
		);

		$this->setting[]=array(
		'id'=>'Moderation_Queue',
		'title'=>'Moderation Queue',
		'callback'=>'settings_3',
		'group'=>'Group 1'
		);

		$this->setting[]=array(
		'id'=>'Image_Extensions',
		'title'=>'Extensions Allowed',
		'callback'=>'settings_4',
		'group'=>'Group 1'
		);

		$this->setting[]=array(
		'id'=>'Image_Size_Limit',
		'title'=>'Image Size Limit',
		'callback'=>'settings_5',
		'group'=>'Group 1'
		);

		$this->setting[]=array(
		'id'=>'NGG_Path',
		'title'=>'NextGen Gallery Directory',
		'callback'=>'settings_6',
		'group'=>'Group 1'
		);

		$this->setting[]=array(
		'id'=>'Misc Settings',
		'title'=>'Misc Settings',
		'callback'=>'misc_settings',
		'group'=>'Group 1'
		);
		
		$this->section[]=array(
		'group'=>"Group 2",
		'section_header'=>" ",
		'callback'=>"display",
		'tab'=>"Moderation Queue",
		'surpress_form'=>true,
		);

		$this->setting[]=array(
		'id'=>'Moderation_Queue',
		'title'=>'',
		'callback'=>'moderation_queue',
		'group'=>'Group 2'
		);

		$this->section[]=array(
		'group'=>"Group 3",
		'section_header'=>" ",
		'callback'=>"display",
		'tab'=>"Moderation Queue",
		);

		$this->section[]=array(
		'group'=>"Group 3",
		'section_header'=>" ",
		'callback'=>"donate_page",
		'tab'=>"Donate",
		);
	
	}
	
	public function self_define() {
		// sets things up for uniformity across the plugin. We can use this class to set options, and also to easily access the options with a simple 
		// class call without the $optional if we just want to get the settings wherever.

		$this->menu_title = "NextGen Public Uploader"; // this is the text that will show up in the settings section when it's selected. 	
		$this->page_title = "NextGen Public Uploader"; // this is what will show up on the Settings page. 
		$this->options_name = "NGG_User_upload_option_array";	//Used to name the field the settings are stored in. 
		$this->options_page = "NGG_user_upload_settings"; // Used to bind settings, fields and registered setting. This MUST be set.  
		$this->sanitize = "sanitize_func"; // Field sanitizing function name.
			
	}
	
	public function sanitize_func($input) { //process/validate data. 
				
			$input['email_notification_address'] = sanitize_email( $input['email_notification_address']); 
			
			if ($input['email_notification_address'] =="") 
				$input['email_notification_address'] = get_option('admin_email');
			
			$input['Upload_directory'] = preg_replace('~[^A-Za-z0-9/-]~','',$input['Upload_directory']);
			$input['Nextgen_base_directory'] = preg_replace('~[^A-Za-z0-9/-]~','',$input['Nextgen_base_directory']);
	        
	        $input['Nextgen_base_directory'] = rtrim($input['Nextgen_base_directory'], '/\\');
	        $input['Nextgen_base_directory'] = ltrim($input['Nextgen_base_directory'], '/\\');
	      	
	      	// $input['Nextgen_full_directory'] = ngg_build_abs_path()."/".$input['Nextgen_base_directory']."/"; 
			// $input['Nextgen_url_path'] = site_url()."/".$input['Nextgen_base_directory']."/";
			// $input['Upload_directory_base'] = ngg_pup_get_temporary_upload_dir();
			// once we've figured out where the user wants to store files, we check and see if the directory
	        // exists, and if it's not make it.
	        
	        
			if ($input['Upload_directory'] == "") {
					
				$input['Upload_directory'] = "queue/"; //in case nothings set, we set a default option. Also useful for when we initialize plugin for the first time.
				 
			} else {
				
				$input['Upload_directory'] = rtrim($input['Upload_directory'], '/\\');
		        $input['Upload_directory'] = ltrim($input['Upload_directory'], '/\\');
			
			}
	
	        
			$path = $input['Upload_directory_base']."/".$input['Upload_directory'];
			$input['Upload_full_directory']=$path."/";
	        
			if ((!is_dir($path)) && (!file_exists($path)))  {
	    
               if(!mkdir($path, 0777, true))
	               echo "Sorry, could not create $path . Please check to make sure the directory
	               does not exist, and you have proper access to create directories.";    
	            
            }
			         
			$input['enable_moderation_queue'] = $this->check_form_box($input['enable_moderation_queue']); //checks status of option first, because we use it below
			$input['Limit_Upload_by_user_role'] = $this->check_form_box($input['Limit_Upload_by_user_role']); //checks status of option first, because we use it below
			$input['Allow_user_moderation'] = $this->check_form_box($input['Allow_user_moderation']);  //checks status of option first, because we use it below
			$input['email_notification_on_upload'] = $this->check_form_box($input['email_notification_on_upload']);
	        $input['enable_NGG_support'] = $this->check_form_box($input['enable_NGG_support']);
	        $input['Allow_user_gal_create'] = $this->check_form_box($input['Allow_user_gal_create']);
	        $input['unlogged_users_can_upload'] = $this->check_form_box($input['unlogged_users_can_upload']);            
			
	        foreach ($this->roles as $role) {
			    
		        if (!$input['Allow_user_gal_create']) {
		        	
		        $input[$role."_can_create_gal"] = "";
					                
		        } else {
		        	
		        $input[$role."_can_create_gal"] = $this->check_form_box($input[$role."_can_create_gal"]);    
		        
			        if (!$input["Administrator_can_create_gal"]) {
			        
						$input["Administrator_can_create_gal"] = True;
						    
			        }
		        
				}  
		         
				if ($input[$role."_can_create_gal"])  // checks to see if role can create galleries. If so stores it in array. 
		        	$allowed_roles_create_gal[]=$role;
				
				if(!$input['Limit_Upload_by_user_role']) { //if enabled, disables uploads, except for admin
		        	
		        	$input[$role."_can_upload"] = "";
				
				} else { //if option is checked, then we proceed with normal role checking
				
		        	$input[$role."_can_upload"] = $this->check_form_box($input[$role."_can_upload"]);
				
				}			
		
		        if(!$input['Allow_user_moderation']) { // if enabled, disables moderation, except for admin
					
					$input[$role."_can_moderate"] = "";
				
				} else { //if option is checked, then we proceed with normal role checking
				
					$input[$role."_can_moderate"] = $this->check_form_box($input[$role."_can_moderate"]);
				}
				
				if ($role=="Administrator")  // because admin should ALWAYS be allowed. Just in case we miss something, re-enables options for admins only 
					$input["Administrator_can_moderate"] = $input["Administrator_can_upload"] = TRUE;
							
				if ($input[$role."_can_upload"])  // checks to see if role can upload, if so stores the role in an array we can check against on the front end. 
					$allowed_roles_upload[]=$role;
		                    					
				if ($input[$role."_can_moderate"])  // checks to see if role can moderate, if so stores the role in an array we can check against on the front end. 
					$allowed_roles_moderate[]=$role;
					
			}
			
	        if ($input['unlogged_users_can_upload']) 
	        	$allowed_roles_upload[] = "unlogged_users";
	        
			$input['allowed_roles_upload'] = $allowed_roles_upload; //updates option array after loop
			$input['allowed_roles_moderate'] = $allowed_roles_moderate; //updates option array after loop 
			$input['allowed_roles_create_gal'] = $allowed_roles_create_gal;
			
			foreach ($this->image_extensions as $ext) {
					
				$input[$ext."_allowed"] = $this->check_form_box($input[$ext."_allowed"]);
				
				if (isset($input[$ext."_allowed"])&& ($input[$ext."_allowed"])) 
					$allowed_extensions[]=$ext;
				
			
			}
			
			$input['allowed_extensions'] = $allowed_extensions;
			
			if((!is_numeric($input['Upload_Size_Limit']) OR ($input['Upload_Size_Limit'] <= 0))) {
			
				$input['Upload_Size_Limit'] = 100000; // if not a number, or lower than 100kb then set it to 100kb(100000)
			
			} else {
				
			$input['Upload_Size_Limit'] = ($input['Upload_Size_Limit'] * 1000);
			 
			}
			$input['first_run'] = "yes"; //used for activation/deactivation check. Don't change this.
		
		return $input;	//updates options in db.
		
	}

		public function settings_1(){ //upload directory setting
			
			echo "<div class='options_box'>"; 
				 
				echo "<span class='options_box_header'>";
				
				echo $this->form_builder->add(array(
										'elem'=>'input', 
										'type'=>'text',
										'name_array'=>'Upload_directory',
										'name'=>$this->options_name,
										'value'=>$this->options_array['Upload_directory']
									));
									
				echo "</span>";
				
				echo "Please choose where you want uploads to be stored. This is a temporary directory, until they are moved from the 
				moderation queue. If this folder does not already exist, it will be created for you automatically when you hit save.";
				
			echo "</div>";
		}
		
	public function settings_2(){ //user upload settings
	
		echo  "<div class='options_box'>";
		
			echo "<span class='options_box_header'>";		
				
				echo $this->form_builder->add(array(
										'elem'=>'input', 
										'type'=>'checkbox',
										'name_array'=>'Limit_Upload_by_user_role',
										'name'=>$this->options_name,
										'value'=>$this->options_array['Limit_Upload_by_user_role']
									));
				echo "Set Upload Access.";
									
			echo "</span>";
													
			echo "Enabling this option will allow you to set uploading privledges per user level. If this is disabled, only administrator level 
			users will be allowed to upload."; 
			
		echo "</div>";
		
		if ($this->options_array['Limit_Upload_by_user_role']) {	
			
			echo "<div class='options_box'>";
			
				echo "<span class='options_box_header'> Select which users are allowed to upload </span>";
				
				foreach ($this->roles as $role) {
					
					echo "<div class='options_checkbox_wrap'>";
					
						echo $this->form_builder->add(array(
												'elem'=>'input', 
												'type'=>'checkbox',
												'name_array'=>$role."_can_upload",
												'name'=>$this->options_name,
												'value'=>$this -> options_array[$role . '_can_upload']
											));
											
						echo $role; 
						
					echo "</div>";
					
				} 
				
			echo "</div>";
		
		} 
	
		echo "<div class='options_box'>";

				echo "<span class='options_box_header'>";
					
					echo $this->form_builder->add(array(
											'elem'=>'input', 
											'type'=>'checkbox',
											'name_array'=>"unlogged_users_can_upload",
											'name'=>$this->options_name,
											'value'=>$this -> options_array['unlogged_users_can_upload']
										));
					
					echo "Enable users who aren't logged in to upload.";
					
				echo "</span>";
			
			echo "<p>Please note, enabling this option will allow un-logged in users to upload files to your site. It should 
			be noted that while every step is taken to ensure that this plugin is secure, you are basically allowing ANYONE 
			to upload whatever pictures they want. The ownice will on YOU to take altenate steps to filter or moderate who can 
			upload pictures, such as adding password protection, or other means.</p>";  
			
			echo "<p><span class='warning'>";
			
				echo "By enabling this option, you agree that I, the author of this plugin, will not be held responsible if 
				users upload illicit images, including but not limited to pornography, or any other offensive or illict 
				material. Furthermore, you agree that you are solely responsible for ensuring you take proper steps to moderate
				the content uploaded by your users.";
			 
		    echo "</span></p>";
			
		echo "</div>";
	
	}

	public function settings_3(){ //moderation queue settings
		
		echo "<div class='options_box'>";
			
			echo "<span class='options_box_header'>";
					
				echo  $this->form_builder->add(array(
										'elem'=>'input', 
										'type'=>'checkbox',
										'name_array'=>"enable_moderation_queue",
										'name'=>$this->options_name,
										'value'=>$this -> options_array['enable_moderation_queue']
																							));
				
				echo "Enable Moderation queue.";
				
			echo "</span>";
				
			echo "If this is disabled, all uploads will just be stored in the upload directory you specify.";
			
		echo "</div>";
		
		if ($this -> options_array['enable_moderation_queue']) {
				
			echo "<div class='options_box'>";
				
				echo "<span class='options_box_header'>";			
				
					echo  $this->form_builder->add(array(
												'elem'=>'input', 
												'type'=>'checkbox',
												'name_array'=>'Allow_user_gal_create',
												'name'=>$this->options_name,
												'value'=>$this->options_array['Allow_user_gal_create']
																		));
					echo "Allow Users to create Galleries";
																						
				echo "</span>";
				
				echo "<p>Enabling this option will allow you to set which user levels are allowed to create new galleries in the moderation queue. 
					If this is unchecked, gallery creation will be disabled.</p>";
				
				echo "<p><b>As with everything else, please be careful with this option, as users will be able to create as many new 
					galleries as they like.</b></p>";
			
			echo "</div>";
			
		//if ($this->options_array['Allow_user_gal_create']) {
					
				echo "<div class='selection_box'>";
					
					echo "<span class='options_box_header'>Select which users can use create galleries. </span>";
					
					foreach ($this->roles as $role) {
						
						echo "<div class='options_checkbox_wrap'>";
							
							echo  $this->form_builder->add(array(
													'elem'=>'input', 
													'type'=>'checkbox',
													'name_array'=>$role."_can_create_gal",
													'name'=>$this->options_name,
													'value'=>$this -> options_array[$role . '_can_create_gal']
																			));
							
							echo $role;
						 
						echo "</div>";
				
					} 
				
				echo "</div>";
		//	}
					
				echo "<div class='options_box'>";
					
					echo "<span class='options_box_header'>";			
						
						echo $this->form_builder->add(array(
															'elem'=>'input', 
															'type'=>'checkbox',
															'name_array'=>"Allow_user_moderation",
															'name'=>$this->options_name,
															'value'=>$this -> options_array['Allow_user_moderation']
														));
														
					echo "Allow Users to Manage Moderation Queue";
																	
					echo "</span>";
					
						echo "<p>Enabling this option will allow you to set which user levels are allowed to access the moderation queue.
						The moderation queue is where all uploads go until they are reviewed and moved. Access to this queue
						will allow users to move pictures into different galleries, edit galleries, tag images and more.
						If this is left unchecked, only administrators will be allowed to moderate the queue.</p>";
					
					echo "<p><b>Please choose who can access this feature carefully, as while it is secure, you are give your users
					access to remove or move pictures as they see fit. </b></p>";
					
				echo "</div>";
				
			//	if ($this->options_array['Allow_user_moderation']) {
					echo "<div class='selection_box'>";
						
						echo "<span class='options_box_header'> Select which users can use moderation queue.</span>";
					
						foreach ($this->roles as $role) {
							
							echo "<div class='options_checkbox_wrap'>";
								echo $this->form_builder->add(array(
													'elem'=>'input', 
													'type'=>'checkbox',
													'name_array'=>$role."_can_moderate",
													'name'=>$this->options_name,
													'value'=>$this -> options_array[$role . '_can_moderate']
																			));
									
								echo $role; 
								
							echo "</div>";
							
						} 
						
					echo "</div>";
			//	} 
			
		}
	
	}


	public function settings_4(){ //upload directory setting
	
		echo "<div class='options_box'>" 
				. 
				"<span class='options_box_header'>Select which extensions are allowed.</span>";

			foreach ($this->image_extensions as $ext) {
				
				echo "<div class='options_checkbox_wrap'>";
				
					echo  $this->form_builder->add(array(
											'elem'=>'input', 
											'type'=>'checkbox',
											'name_array'=>$ext."_allowed",
											'name'=>$this->options_name,
											'value'=>$this -> options_array[$ext . '_allowed']
																	));
				
					echo $ext;
					 
				echo "</div>";
				
			}
			 
		echo "</div>";

	}
	
	public function settings_5(){ //upload directory setting
	
		echo "<div class='options_box'>";
			
				echo"<span class='options_box_header'>";
					
					echo  $this->form_builder->add(array(
											'elem'=>'input', 
											'type'=>'text',
											'name_array'=>'Upload_Size_Limit',
											'name'=>$this->options_name,
											'value'=>($this->options_array['Upload_Size_Limit']/1000)
																	));
																									
					echo " KB";
				
				echo "</span>";
			
			echo "<p> This field is numerical, and is measured in kilobytes. For example, 50 = 50kb, 1000 = 1mb. Default 
				is 100( 100 KB ).</p>";
				
			echo "<p> Please note, many servers have an upper limit on upload sizes. If you set this option above that 
				limit, the server limit will override this setting.</p>";
			
		echo "</div>";

	}
	
	public function settings_6(){ //NGG's upload directory setting

		echo "<div class='options_box'>";

			echo "<span class='options_box_header'>";
			
			echo  $this->form_builder->add(array(
									'elem'=>'input', 
									'type'=>'text',
									'name_array'=>'Nextgen_base_directory',
									'name'=>$this->options_name,
									'value'=>$this->options_array['Nextgen_base_directory']
															));
			
			echo "</span>";
			
			echo "<p>This plugin attempts to populate this field automatically based on NextGens settings, but if it's 
				blank you will have to enter it manually.</p>";
			
			echo "<p>If it's blank, please enter the directory in which NextGen Gallery stores its galleries. You can 
				find this setting in NextGens option pages.</p>";
			
			echo "<p><b>This field is mandatory, without it you will not be able to use the upload to gallery function 
				or moderation part of this plugin.</b></p>";
			
		echo "</div>";
		
}
		


	
	
	public function moderation_queue () {
		
	}
	
	public function misc_settings(){ //Enable/Disable Gallery/Moderation queue.
	/*
		echo "<div class='options_box'>";
		
			echo "<span class='options_box_header'>";
			
				echo  $this->form_builder->add(array(
												'elem'=>'input', 
												'type'=>'checkbox',
												'name_array'=>"enable_NGG_support",
												'name'=>$this->options_name,
												'value'=>$this -> options_array['enable_NGG_support']
																									));
																									
				
				echo "Enable NextGen Gallery Uploading.";
			
			echo "</span>";
			
			echo "This option enables direct to gallery uploading. If you do not have NextGen Gallery Installed, do not enable this option.";
		
		echo "</div>";
	*/
		echo "<div class='options_box'>";
		
			echo "<span class='options_box_header'>";
			
				echo  $this->form_builder->add(array(
												'elem'=>'input', 
												'type'=>'checkbox',
												'name_array'=>"email_notification_on_upload",
												'name'=>$this->options_name,
												'value'=>$this -> options_array['email_notification_on_upload']
																									));
				
				echo "Notify Me by e-mail when a user uploads images.";
				
			echo "</span>";
			
			
			
				echo  $this->form_builder->add(array(
												'elem'=>'input', 
												'type'=>'text',
												'name_array'=>"email_notification_address",
												'name'=>$this->options_name,
												'value'=>$this -> options_array['email_notification_address']
																									));
																									
																										
			
			echo "<p>Please enter an e-mail address. If you do not enter a valid e-mail address, and this option is enabled, then e-mails 
			will be sent to the admin e-mail address configured in wordpress's options instead.</p>";
		
		echo "</div>";
		
	}

	public function donate() { 
	
	}
			
}?>