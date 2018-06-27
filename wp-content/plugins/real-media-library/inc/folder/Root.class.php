<?php
namespace MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\order;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class creates a root object. This root object can not be created! There exists
 * only once of this type.
 * 
 * @see Creatable
 * @type "4" (4 for backwards-compatibility)
 */
class Root extends order\Sortable {
    
    private static $me = null;
    
    public function __construct() {
        parent::__construct(-1, null, "/" . __('Unorganized', RML_TD), "/", "/");
    }
    
    public function persist() {
        throw new \Exception("You can not persist the root folder.");
    }
    
    public function getSlug($force = false, $fromSetName = false) {
        return $this->slug;
    }
    
    public function getAbsolutePath($force = false, $fromSetName = false) {
        return $this->absolutePath;
    }
    
    public function getCnt($forceReload = false) {
        return attachment\Structure::getInstance()->getCntRoot();
    }
    
    public function setParent($id, $ord = -1, $force = false) {
        throw new \Exception("You can not set a parent for the root folder.");
    }
    
    public function setName($name, $supress_validation = false) {
        throw new \Exception("You can not set a name for the root folder.");
    }
    
    public function setRestrictions($restrictions = array()) {
        throw new \Exception("You can not set permissions for the root folder.");
    }
    
    public function getChildren() {
        return attachment\Structure::getInstance()->getTree();
    }
    
    /*
     * Checks, if a children type is allowed here.
     * 
     * @return Array with allowed types or TRUE for all types allowed
     * @filter RML/Folder/Types/4
     */
    public function getAllowedChildrenTypes() {
        return apply_filters("RML/Folder/Types/" . $this->getType(), array(RML_TYPE_FOLDER, RML_TYPE_COLLECTION)); // already documentated
    }
    
    public function getType() {
        return RML_TYPE_ROOT;
    }
    
    public function getContentCustomOrder() {
        return "2";
    }
    
    public function getTypeName($default = null) {
        return parent::getTypeName($default === null ? __('Uncategorized', RML_TD) : $default);
    }
    
    public function getTypeDescription($default = null) {
        return parent::getTypeDescription($default === null ? __('Uncategorized is the same as a root folder. Here you can find all files which are not assigned to a folder.', RML_TD) : $default);
    }
    
    public function getTypeIcon($default = null) {
        return parent::getTypeIcon($default === null ? '<i class="fa fa-dot-circle-o"></i>' : $default);
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Root();
        }
        return self::$me;
    }
}

?>