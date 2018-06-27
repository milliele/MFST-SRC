<?php
// Don't load directly
if ( !defined('ABSPATH') ) { die('-1'); }	

/*
Description:  	Allows for asset generation/inclusion like css files and js. Also allows to combine files.
				Basic idea: allows us to enqueue scripts and styles and before the files get enqueued individually, 
				we try to generate a compressed version and enqueue this one instead.

Author: 		Kriesi
Since:			4.2.4
*/
	

if ( !class_exists( 'aviaAssetManager' ) ) {

	class aviaAssetManager
	{
		var $db_prefix = "aviaAsset_";
		var $which_files 	= array('css' => 'avia-module', 'js' => 'avia-module');
		var $compress_files = array('css' => true, 'js' => true);
		var $exclude_files	= array('css' => array('admin-bar','dashicons'), 'js' => array('admin-bar'));
		var $deregister_files = array('css' => array(), 'js' => array());
		var $testmode 		= false;
		
		public function __construct( $builder ) 
		{
			// allow to change the files that should be merged:
			// 'none' 		 	=> merging is deactivated
			// 'avia-module' 	=> only module files
			// 'avia'			=> all framework files
			// 'all'			=> all enqueued files
			$this->which_files  = apply_filters( 'avf_merge_assets'  , $this->which_files  );
			
			// allow to change the files that should be compressed:
			// true and false allowed
			$this->compress_files  = apply_filters( 'avf_compress_assets'  , $this->compress_files  );
			
			// files that are always excluded like admin bar files
			// files that are processed are added to this list as well, in case another file should be generated
			$this->exclude_files  = apply_filters( 'avf_exclude_assets'  , $this->exclude_files  );
			
			
			//before enqueuing the css and js files check if we can serve a compressed version (only frontend)
			add_action('wp_enqueue_scripts', 	array(&$this, 'try_minifying_scripts') , 999999 );
			
			//if we got any compressed/combined scripts remove the default registered files
			add_action('wp_enqueue_scripts', 	array(&$this, 'try_deregister_scripts') , 9999999 );
			
			
		}
		
		

		
		//default calling of the merging/compression script. the "merge" function can in theory also be called from outside
		public function try_minifying_scripts()
		{
			// check if we got a compressed version that includes all the elements that we need. 
			// generate a new version if the theme version changes or the css files that are included change
			// compare by hash if a new version is required
			
			
			//compresses css files and stores them in a file called avia-merged-styles-HASH_ID.css
			$this->merge('css', 'avia-merged-styles');
			
			//compresses JS files and stores them in a file called avia-head-scripts-HASH_ID.js/avia-footer-scripts-HASH_ID.js// - footer scripts attr: (group 1)
			
			//$this->merge('js',  'avia-head-scripts',   array('groups'=>0));
			$this->merge('js',  'avia-footer-scripts', array('groups'=>1));
		}

		//function that checks if we can merge a group of files based on the passed params
		public function merge( $file_type , $prefix , $conditions = array())
		{
			if($this->which_files[$file_type] == "none" ) return;
			
			if($this->which_files[$file_type] == "all" )
			{
				//we need a file for logged in and logged out users. 
				//this is only necessary when "all" files are included and we no longer have control over files
				$prefix .= is_user_logged_in() ? "-user" : "-guest";
			}
			
			
			//hook into all enqueued styles
			global $wp_styles, $wp_scripts;
			
			//get the data of the file we would generate
			$enqueued	= ($file_type == "css") ? $wp_styles : $wp_scripts;
			$data 		= $this->get_file_data( $file_type , $prefix, $enqueued , $conditions );
			
			//check if we got a db entry with this hash. if so, no further merging needed and we can remove the registered files from the enque array
			$file_exists = get_option( $this->db_prefix.$prefix.$file_type );
			
			/**
			 * User might have deleted the compressed files manually - in this case get_option returns a valid value 
			 * but we have to check that the files really exist
			 *
			 * Modified by Kriesi: currently disabled. Not sure if necessary and accessing the file system is so much slower than a simple db request
			 * also a manually deleted file can easily be restored by saving the admin page once
			 
			if( 'error-generating-file' != $file_exists )
			{
				$stylesheet_dir = $this->get_dyn_stylesheet_dir_path();
				$file = $stylesheet_dir . '/' . $data['hash'] . "." . $file_type;
				if( ! file_exists( $file ) )
				{
					$file_exists = false;
				}
			}
			*/

			//if the file does not exist try to generate it
			if(($file_exists != $data['hash'] && $file_exists !== 'error-generating-file') || $this->testmode)
			{
				$file_exists = $this->generate_file( $file_type, $data , $enqueued);
			}
			
			//if the file exists and was properly generated at one time in the past, enque the new file and remove all the others. otherwise do nothing
			if($file_exists && $file_exists !== "error-generating-file")
			{
				if(is_array($data['remove']))
				{
					foreach($data['remove'] as $remove)
					{
						//for future iterations, exlude all files we used here
						$this->exclude_files[$file_type][] = $remove['name'];
						$this->deregister_files[$file_type][] = $remove['name'];
					}
				}
				
				$avia_dyn_file_url = $this->get_file_url($data, $file_type);
				
				//if file exists enque it
				if($file_type == 'css')
				{
					wp_enqueue_style( $prefix , $avia_dyn_file_url, array(), false, 'all' );
				}
				else
				{
					$footer = isset($conditions['groups']) ? $conditions['groups'] : true;
					
					wp_enqueue_script( $prefix , $avia_dyn_file_url, array(), false, $footer );
				}
			}
			
			//store that we tried to generate the file but it did not work. therefore no more future tries but simple enqueuing of the single files
			if(!$file_exists)
			{
				update_option( $this->db_prefix.$prefix.$file_type , 'error-generating-file' );
			}
		}
		
		public function get_file_url($data, $file_type)
		{
			$avia_upload_dir = wp_upload_dir();
			if(is_ssl()) $avia_upload_dir['baseurl'] = str_replace("http://", "https://", $avia_upload_dir['baseurl']);

			$url = $avia_upload_dir['baseurl'] . '/dynamic_avia/'.$data['hash'].'.'.$file_type;
			
			return $url;
		}

		
		// returns a file data array with hash, version number and scripts we need to dequeue. 
		// the hash we generate consists of parent theme version, child theme version and files to include. if any of this changes we create a new file
		public function get_file_data( $file_type , $prefix, $enqueued , $conditions)
		{			
			$data = array('hash' => '' , 'version' => '' , 'remove' => array(), 'prefix' => $prefix);
			
			//generate the version number
			$theme 	 		= wp_get_theme();
			$data['version']= $theme->get( 'Version' );
			
			if( false !== $theme->parent() )
			{
				$theme 	 		 = $theme->parent();
				$data['version'] = $theme->get( 'Version' ) . '-' . $data['version'];
			}
			
			//set up the to_do array which has the proper dependencies
			$enqueued->all_deps( $enqueued->queue );
			
			
			//generate the name string for all the files included. store the data of those files so we can properly include them later and then dequeue them
			foreach($enqueued->to_do as $file)
			{
				// check which files to include based on the $which_files setting (all, none, modules only, all framework files)
				if( ('all' == $this->which_files[$file_type] ) || 
					('avia-module' == $this->which_files[$file_type] && strpos($file, 'avia-module') !== false ) ||
					('avia' == $this->which_files[$file_type] && strpos($file, 'avia') !== false ) )
					{
						//dont use excluded files like admin bar or already used files
						if(in_array($file, $this->exclude_files[$file_type])) continue;
						
						//dont use print stylesheets
						if($enqueued->registered[$file]->args == 'print') continue;
						
						//if a group condition is set check if the file matches
						if(isset($conditions['groups']) && $enqueued->groups[$file] != $conditions['groups']) continue;
						
						//the file string we need to generate the final hash
						$data['hash'] 	.= $file;
					
						//set up correct path
						//all the files we need to remove from the worpdress queue once we verified that a compressed version is available
						$data['remove'][] = array(
							'name' => $file,
							'url'  => $enqueued->registered[$file]->src,
							'path' => $this->set_path($enqueued->registered[$file]->src)
						);
						
					}
			}
			
			
			
			//clean up the todo list
			$enqueued->to_do = array();
			
			//generate a unique hash based on file name string and version number
			$data['hash'] 	= $prefix .'-'. md5( $data['hash'] . $data['version'] );
			
			return $data;
		}
		
		
		/**
		 * Return the path to the directory where compressed files are stored excluding / at end
		 * 
		 * @since 4.2.6
		 * @added_by Günter
		 * @return string	
		 */
		public function get_dyn_stylesheet_dir_path()
		{
			$wp_upload_dir  = wp_upload_dir();
		    $stylesheet_dir = $wp_upload_dir['basedir'] . '/dynamic_avia';
		    $stylesheet_dir = str_replace( '\\', '/', $stylesheet_dir );
		    $stylesheet_dir = apply_filters( 'avia_dyn_stylesheet_dir_path',  $stylesheet_dir );
			
			return $stylesheet_dir;
		}

		

		//generates the merged and compressed file
		public function generate_file( $file_type , $data , $enqueued)
		{
			$file_created = false;
			
			//try to create a new folder if necessary
			$stylesheet_dir = $this->get_dyn_stylesheet_dir_path();
		    $isdir = avia_backend_create_folder($stylesheet_dir);
			
			//check if we got a folder (either created one or there already was one). if we got one proceed
			if(!$isdir) return false;
			
			//clean up existing styles with the same prefix group
			foreach(glob($stylesheet_dir.'/'.$data['prefix']."*.".$file_type) as $file)
			{
				unlink($file);
			}
			
			$content = "";
			
			//iterate over existing styles and save the content so we can add it to the compressed file
			if(is_array($data['remove']))
			{
				foreach($data['remove'] as $remove)
				{
					if($remove['path'] != "")
					{
						$new_content = file_get_contents( trailingslashit( ABSPATH ) . $remove['path'] );
						$new_content = $this->compress_content($new_content , $file_type, $remove);
						$content 	.= $new_content;
					}
				}
			}
			
			//create a new file if we got any content
			if(trim($content) != "")
			{
				$file_path		= trailingslashit($stylesheet_dir).$data['hash'].".".$file_type;
				$file_created 	= avia_backend_create_file($file_path, $content);
				
				//double check if the file can be accessed
				if(is_readable($file_path))
				{
					$handle = fopen($file_path, "r");
					$filecontent = fread($handle, filesize($file_path));
					fclose( $handle );
					
					$file 		= $this->get_file_url($data, $file_type);
					$request 	= wp_remote_get($file);
					
					$file_created = false;
					if( ( ! $request instanceof WP_Error ) && is_array( $request ) && isset( $request['body'] ) )
					{
						$request['body'] = trim($request['body']);
						$filecontent = trim($filecontent);
					
						//if the content does not match the file is not accessible
						if($filecontent == $request['body'])
						{
							$file_created = true;
						}
					}
				}
			}
			
			//file creation failed
			if(!$file_created) return false;
			
			//file creation succeeded, store the url of the file
			update_option( $this->db_prefix.$data['prefix'].$file_type , $data['hash']);
			
			//if everything worked out return the new file hash, otherwise return false
			return true;
		}
		
		
		//function that removes whitespace and comments, fixes relative urls etc
		public function compress_content( $content , $file_type , $current_file)
		{
			if('css' == $file_type)
			{
				$content = $this->rel_to_abs_url($content, $current_file);
								
				if($this->compress_files[$file_type]) 
				{
					$content = $this->css_strip_whitespace($content);
				}
			}
			else 
			{
				if($this->compress_files[$file_type])
				{
					if(version_compare(phpversion(), '5.3', '>=')) 
					{
						include_once 'external/JSqueeze.php';
						
						$jz = new JSqueeze();
						
						$content = $jz->squeeze(
						    $content,
						    true,   // $singleLine
						    false,  // $keepImportantComments
						    false   // $specialVarRx
						);
					}
				}
			}
			
			return $content;
		}
		
		#################
		//switch relative urls in the stylesheet to absolute urls
		public function rel_to_abs_url($content, $current_file)
		{
			
			// test drive for the regexp : https://regexr.com/3kq8q
			
			$this->base_url = trailingslashit(dirname( get_site_url(NULL, $current_file['path']) ));
			$reg_exUrl 		= '/url\s*?\([\"|\'|\s|\/]*([^\:]+?)[\"|\'|\s]*\)/im';
			
			$content = preg_replace_callback($reg_exUrl, array($this, '_url_callback'), $content);
			
			return $content;
		}
		
		
				//callback function. todo once wp switches to 5.3: make it anonymous again
				//remove ../../ from urls and iterate into higher folder from the baseurl
				public function _url_callback( $match )
				{
					$current_base 	= $this->base_url;
					$segments 		= explode("../", $match[1]);
					$seg_count		= count($segments) - 1;
					
					for($i = $seg_count; $i > 0; $i--)
					{
						$current_base = dirname($current_base);
					}
					
					$new_url = trailingslashit($current_base) . end($segments);
					
					return "url('".$new_url."')";
				}
		
		#################
		
		
		
		public function css_strip_whitespace($css)
		{
		  $replace = array(
		    "#/\*.*?\*/#s" => "",  // Strip C style comments.
		    "#\s\s+#"      => " ", // Strip excess whitespace.
		    "#\t#"		   => ""
		  );
		  $search = array_keys($replace);
		  $css = preg_replace($search, $replace, $css);
		
		  $replace = array(
		    ": "  => ":",
		    "; "  => ";",
		    " {"  => "{",
		    " }"  => "}",
		    ", "  => ",",
		    "{ "  => "{",
		    "{ "  => "{",
		    ";\n"  => ";", // Put all rules from one selector into one line
		    ";}"  => "}", // Strip optional semicolons.
		    ",\n" => ",", // Don't wrap multiple selectors.
		    "\n}" => "}", // Don't wrap closing braces.
		    "{\n" => "{", // Don't wrap the first rule of a selector.
		    "} "  => "}\n", // Put each rule on it's own line.
		  );
		  $search = array_keys($replace);
		  $css = str_replace($search, $replace, $css);
		
		  return trim($css);
		}
		
		//remove all db keys starting with the $this->db_prefix - this way all files will be generated new on next pageload
		public function reset_files()
		{
			global $wpdb;
			$results = $wpdb->get_results( "SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE '{$this->db_prefix}%'", OBJECT );

			foreach($results as $result)
			{
				delete_option($result->option_name);
			}
			
		}
		
		//dequeue and deregister scripts
		public function try_deregister_scripts()
		{
			foreach($this->deregister_files as $file_type => $files)
			{
				foreach($files as $remove)
				{
					if($file_type == 'css')
					{
						wp_dequeue_style( $remove );
						wp_deregister_style( $remove );
					}
					else
					{
						wp_dequeue_script( $remove );
						wp_deregister_script( $remove );
					}
				}
			}
		}
		
		public function set_path( $registered_path )
		{
			$path =  str_replace(site_url(), "", $registered_path);
			
			if(strpos($path, "//") === 0) //if the path starts with // - eg: used by plugins like woocommerce
			{
				$remove = explode("//", site_url());
				$path = str_replace("//" . $remove[1], "", $registered_path);
			}
			
			return $path;
			
		}
		
		
		

	} // end class

} // end if !class_exists