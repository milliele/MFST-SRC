<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\folder;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class REST extends Base {
	private static $me = null;
        
    private function __construct() {
        // Silence is golden.
    }
    
    /*
     * Register my endpoints
     */
    public function rest_api_init() {
        if (!_wp_rml_active()) {
            return;
        }
        
        register_rest_route( 'rml/v1', '/creatable/(?P<id>-?\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'creatable'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric( $param ) || $param == -1;
                    }
                ),
            )
        ));
        
        register_rest_route( 'rml/v1', '/creatable/(?P<id>-?\d+)/children', array(
            'methods' => 'GET',
            'callback' => array($this, 'creatable_children'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric( $param ) || $param == -1;
                    }
                ),
            )
        ));
        
        register_rest_route( 'rml/v1', '/root', array(
            'methods' => 'GET',
            'callback' => array($this, 'root')
        ));
    }
    
    /*
     * Get the root folder childrens
     */
    public function root() {
        $children = wp_rml_root_childs();
        $plainObjects = array();
        foreach ($children as $folder) {
            $plainObjects[] = $folder->getPlain();
        }
        return new \WP_REST_Response($plainObjects);
    } 
    
    /*
     * Get folder data
     */
    public function creatable($request) {
        $folder = wp_rml_get_object_by_id($request->get_param("id"));
        if ($folder === null) {
            return new \WP_Error( 'createable_not_found', __("The given folder was not found.", RML_TD), array( 'status' => 404 ) );
        }
        return new \WP_REST_Response($folder->getPlain());
    }
    
    /*
     * Get folder children
     */
    public function creatable_children($request) {
        $folder = wp_rml_get_object_by_id($request->get_param("id"));
        if ($folder === null) {
            return new \WP_Error( 'createable_not_found', __("The given folder was not found.", RML_TD), array( 'status' => 404 ) );
        }
        $plainObjects = array();
        $children = $folder->getChildren();
        foreach ($children as $folder) {
            $plainObjects[] = $folder->getPlain();
        }
        return new \WP_REST_Response($plainObjects);
    }
    
    /*
     * @return https://example.com/wp-json
     */
    public static function url($path = "", $prefix = "rml/v1") {
        return ""; //return site_url(rest_get_url_prefix()) . "/" . $prefix . "/" . $path;
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new REST();
        }
        return self::$me;
    }
}