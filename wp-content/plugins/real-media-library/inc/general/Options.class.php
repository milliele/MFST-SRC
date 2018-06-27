<?php
namespace MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles all hooks for the options.
 * 
 * If you want to extend the options for your plugin
 * please use the RML/Options/Register action. There are no
 * parameters. The settings section headline must start with
 * RealMediaLibrary:* (also in translation). The *-value will be
 * added as navigation label.
 */
class Options extends Base {
    
    private static $me = null;
    
    private function __construct() {
        // Silence is golden.
    }

    public function register_fields() {
        add_settings_section(
        	'rml_options_general',
        	__('RealMediaLibrary:General'),
        	array($this, 'empty_callback'),
        	'media'
        );
        
        register_setting( 'media', 'rml_hide_upload_preview', 'esc_attr' );
        add_settings_field(
            'rml_hide_upload_preview',
            '<label for="rml_hide_upload_preview">'.__('Hide upload preview' , RML_TD ).'</label>' ,
            array($this, 'html_hide_upload_preview'),
            'media',
            'rml_options_general'
        );
        
        register_setting( 'media', 'rml_all_folders_gallery', 'esc_attr' );
        add_settings_field(
            'rml_all_folders_gallery',
            '<label for="rml_all_folders_gallery">'.__('Allow all folders for folder gallery' , RML_TD ).'</label>' ,
            array($this, 'html_rml_all_folders_gallery'),
            'media',
            'rml_options_general'
        );
        
        register_setting( 'media', 'rml_load_frontend', 'esc_attr' );
        add_settings_field(
            'rml_load_frontend',
            '<label for="rml_load_frontend">'.__('Load RML functionality in frontend' , RML_TD ).'</label>' ,
            array($this, 'html_rml_load_frontend'),
            'media',
            'rml_options_general'
        );
        
        register_setting( 'media', 'rml_hide_info_links', 'esc_attr' );
        add_settings_field(
            'rml_hide_info_links',
            '<label for="rml_hide_info_links">'.__('Hide info links' , RML_TD ).'</label>' ,
            array($this, 'html_hide_info_links'),
            'media',
            'rml_options_general'
        );
        
        register_setting( 'media', 'rml_debug', 'esc_attr' );
        add_settings_field(
            'rml_debug',
            '<label for="rml_debug">Debug mode</label>' ,
            array($this, 'html_debug'),
            'media',
            'rml_options_general'
        );
        
        /*a
         * Allows you to register new options tabs and fields to the Real Media 
         * Library options panel (Settings > Media).
         * 
         * @example <caption>Create a new tab with a settings field</caption>
         * add_action( 'RML/Options/Register', function() {
         *  // Register tab
         *  add_settings_section(
         *  	'rml_options_custom',
         *  	__('RealMediaLibrary:My Tab'), // The label must begin with RealMediaLibrary:
         *  	array(MatthiasWeb\RealMediaLibrary\general\Options::getInstance(), 'empty_callback'),
         *  	'media'
         *  );
         * 
         *  add_settings_field(
         *      'rml_button_custom',
         *      '<label for="rml_button_custom">Your custom button</label>' ,
         *      'my_function_to_print_rml_button_custom',
         *      'media',
         *      'rml_options_custom' // The section
         *  );
         * } );
         * @action RML/Options/Register
         */
        do_action("RML/Options/Register");
        
        // Reset
        add_settings_section(
        	'rml_options_reset',
        	__('RealMediaLibrary:Reset'),
        	array($this, 'empty_callback'),
        	'media'
        );
        
        add_settings_field(
            'rml_button_wipe',
            '<label for="rml_button_wipe">'.__('Wipe all settings (folders, attachment relations)' , RML_TD ).'</label>' ,
            array($this, 'html_rml_button_wipe'),
            'media',
            'rml_options_reset'
        );
        
        add_settings_field(
            'rml_button_cnt_reset',
            '<label for="rml_button_wipe">'.__('Reset folder count cache' , RML_TD ).'</label>' ,
            array($this, 'html_rml_button_cnt_reset'),
            'media',
            'rml_options_reset'
        );
        
        add_settings_field(
            'rml_button_slug_reset',
            '<label for="rml_button_wipe">'.__('Reset names, slugs and absolute pathes' , RML_TD ).'</label>' ,
            array($this, 'html_rml_button_slug_reset'),
            'media',
            'rml_options_reset'
        );
        
        // Migrations
        add_settings_section(
        	'rml_options_migration',
        	__('RealMediaLibrary:Migration'),
        	array($this, 'empty_callback'),
        	'media'
        );
        
        add_settings_field(
            'rml_migration_placeholder',
            '&nbsp;' ,
            array($this, 'empty_callback'),
            'media',
            'rml_options_migration'
        );
    }
    
    function empty_callback( $arg ) {
    }
    
    public function html_rml_button_wipe() {
        // Check if reinstall the database tables
        if (isset($_GET["rml_install"])) {
            echo "DB Update was executed<br /><br />";
            require_once(RML_PATH . "/inc/others/install.php");
            rml_install(true);
            echo "<br /><br />";
        }
        
        echo '<button class="rml-button-wipe button" data-nonce-key="wipe" data-action="rml_wipe" data-method="rel">' . __('Wipe attachment relations', RML_TD) . '</button>
        <button class="rml-button-wipe button" data-nonce-key="wipe" data-action="rml_wipe" data-method="all">' . __('Wipe all', RML_TD) . '</button>';
    }
    
    public function html_rml_button_cnt_reset() { 
        echo '<button class="button rml-button-wipe"
                data-nonce-key="wipe" 
                data-action="rml_cnt_reset" 
                data-method="">' . __('Reset count', RML_TD) . '</button>';
    }
    
    public function html_rml_button_slug_reset() { 
        echo '<button class="button rml-button-wipe"
                data-nonce-key="wipe" 
                data-action="rml_slug_reset" 
                data-method="">' . __('Reset', RML_TD) . '</button>';
    }
    
    public function html_rml_all_folders_gallery() {
        $value = get_option( 'rml_all_folders_gallery', '' );
        echo '<input type="checkbox" id="rml_all_folders_gallery"
                name="rml_all_folders_gallery" value="1" ' . checked(1, $value, false) . ' />';
    }
    
    public function html_hide_upload_preview() {
        $value = get_option( 'rml_hide_upload_preview', '' );
        echo '<input type="checkbox" id="rml_hide_upload_preview"
                name="rml_hide_upload_preview" value="1" ' . checked(1, $value, false) . ' />
                <label>' . __('Check this if your uploader does not work properly.', RML_TD) . '</label>';
    }
    
    public function html_hide_info_links() {
        $value = get_option( 'rml_hide_info_links', '' );
        echo '<input type="checkbox" id="rml_hide_info_links"
                name="rml_hide_info_links" value="1" ' . checked(1, $value, false) . ' />
                <label>' . __('Links on the sidebar (Version, Tips, ...)', RML_TD) . '</label>';
    }
    
    public function html_rml_load_frontend() {
        $value = get_option( 'rml_load_frontend', '1' );
        echo '<input type="checkbox" id="rml_load_frontend"
                name="rml_load_frontend" value="1" ' . checked(1, $value, false) . ' />
                <label>' . __('If you are using a front end page builder, for example Visual Composer', RML_TD) . '</label>';
    }
    
    public function html_debug() {
        $value = get_option( 'rml_debug' );
        echo '<input type="checkbox" id="rml_debug"
                name="rml_debug" value="1" ' . checked(1, $value, false) . ' />';
                
        if ($value) {
            global $wpdb;
            $tablename = $this->getTableName("debug");
            $dateFormat = get_option( 'date_format' ) . " H:i:s";
            $logs = $wpdb->get_results("SELECT * FROM $tablename ORDER BY id DESC LIMIT 75", ARRAY_A);
            echo '<br /><br /><p style="font-size:9px;">';
            $logs = array_reverse($logs);
            foreach ($logs as $value) {
                echo "[<strong>" . date_i18n( $dateFormat, strtotime($value["created"]) ) . "]</strong> " . $value["text"] . "<br />";
            }
            echo '</p>';
        }
    }
    
    /*
     * Getter for options
     */
    public static function load_frontend() {
        return get_option( 'rml_load_frontend', '1' ) === '1';
    }
    
    public static function getInstance() {
        if (self::$me == null) {
                self::$me = new Options();
        }
        return self::$me;
    }
}

?>