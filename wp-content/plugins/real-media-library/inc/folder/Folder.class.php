<?php
namespace MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\order;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class creates a folder object.
 * 
 * @see Creatable
 * @type "0" (0 for backwards-compatibility)
 */
class Folder extends order\Sortable {
    /*
     * Creates an instance for this folder type if the folder is newly
     * created and should be persisted.
     * 
     * @see Creatable::persist
     * @see Creatable::create
     * @throws Exception when something went wrong by creating
     * @return Folder
     */
    public static function create($rowData) {
        $result = new Folder($rowData->id);
        $result->setParent($rowData->parent);
        $result->setName($rowData->name, $rowData->supress_validation);
        $result->setRestrictions($rowData->restrictions);
        return $result;
    }
    
    /*
     * Creates an instance for this folder type if the folder is loaded
     * for the tree and already exists.
     * 
     * @see Creatable::instance
     * @return Folder
     */
    public static function instance($rowData) {
        return new Folder($rowData->id, $rowData->parent, $rowData->name, $rowData->slug, $rowData->absolute, 
                            $rowData->ord, $rowData->cnt_result, $rowData);
    }
    
    /*
     * Checks, if a children type is allowed here.
     * 
     * @return Array with allowed types or TRUE for all types allowed
     * @filter RML/Folder/Types/0
     */
    public function getAllowedChildrenTypes() {
        /*f
         * Get allowed children folder types for a given folder type. $type can be 
         * replaced with RML_TYPE_FOLDER for example.
         * 
         * @param {int[]} $allowed The allowed folder types
         * @filter RML/Folder/Types/$type
         * @returns {int[]} The allowed folder types
         */
        return apply_filters("RML/Folder/Types/" . $this->getType(), array(RML_TYPE_FOLDER, RML_TYPE_COLLECTION));
    }
    
    public function getType() {
        return RML_TYPE_FOLDER;
    }
}
?>