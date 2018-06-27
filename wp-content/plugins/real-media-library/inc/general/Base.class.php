<?php
namespace MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Base class. All classes from RML should be a extension of this class.
 */
abstract class Base {
    private $debug = true;
    
    public function isDebug() {
        return (defined('RML_DEBUG') && RML_DEBUG) || (function_exists('get_option') && get_option("rml_debug"));
    }
    
    /*
     * Debug RML.
     * 
     * @param $message The message (string or object)
     * @param $methodOrFunction __METHOD__ OR __FUNCTION__
     */
    public function debug($message, $methodOrFunction = null) {
        if ($this->isDebug()) {
            $log = (empty($methodOrFunction) ? "" : "(" . $methodOrFunction . ")") . ": " . (is_string($message) ? $message : json_encode($message));
            if (function_exists('get_option') && get_option("rml_debug")) {
                global $wpdb;
                $tablename = $this->getTableName("debug");
                $wpdb->query($wpdb->prepare("INSERT INTO $tablename (`text`) VALUES(%s);", $log));
            }
            error_log("RML_DEBUG " . $log);
        }
    }
    
    /*
     * @see Core::getTableName()
     */
    public function getTableName($name = "") {
        return Core::getInstance()->getTableName($name);
    }
}