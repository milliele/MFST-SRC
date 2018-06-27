<?php
namespace MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\attachment;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Static class to perform CRUD operations on folders. Please do not use this directly
 * instead use the API functions.
 * The R (Read) is not implemented here because updates should be executed through the API functions.
 * It also holds the registered creatables.
 */
class CRUD extends general\Base {
    
    private static $me = null;
    
    /*
     * @see this::registerCreatable()
     */
    private $creatables = array();
    
    /*
     * @api wp_rml_create()
     */
    public function create($name, $parent, $type, $restrictions = array(), $supress_validation = false, $return_existing_id = false) {
        $this->debug("Try to create folder with name '$name'...", __METHOD__);
        
        try {
            // Create restrictions from parent
            if ($parent >= 0) {
                $parentFolder = wp_rml_get_by_id($parent, null, true);
                if (is_rml_folder($parentFolder)) {
                    $parentRestrictions = $parentFolder->getRestrictions();
                    foreach ($parentRestrictions as $parentRestriction) {
                        if (substr($parentRestriction, -1) == '>') {
                            $restrictions[] = $parentRestriction;
                        }
                    }
                }
            }
            
            // Create the new instance for the folder
            $result = call_user_func_array(array(folder\CRUD::getInstance()->getCreatables($type), 'create'), array( (object) (array(
                "id" => -1,
                "parent" => intval($parent),
                "name" => $name,
                "restrictions" => $restrictions,
                "supress_validation" => $supress_validation
            )) ));
            
            // Check if other fails are counted
            if (!$supress_validation) {
                /*f
                 * Checks if a creation of a folder is allowed.
                 * 
                 * @param {string[]} $errors An array of errors
                 * @filter RML/Validate/Create
                 * @returns {string[]} When the array has one or more items the creation is cancelled with the string message
                 */
                $errors = apply_filters("RML/Validate/Create", array(), $name, $parent, $type);
                if (count($errors) > 0) {
                    throw new \Exception(implode(" ", $errors));
                }
            }
            
            // Persist it!
            return $result->persist();
        } catch (general\FolderAlreadyExistsException $e) {
            $error = $e;
            if ($return_existing_id) {
                $existing_id = $e->getFolder()->getId();
                $this->debug("Found folder with same name, now return the already existing folder id $existing_id...", __METHOD__);
                return $existing_id;
            }
        } catch (\Exception $e) {
            $error = $e;
        }
        
        $this->debug("Error: " . $e->getMessage(), __METHOD__);
        return array($e->getMessage());
    }
    
    /*
     * @api wp_rml_rename()
     */
    public function update($name, $id, $supress_validation = false) {
        try {
            $folder = wp_rml_get_by_id($id, null, true);
            if ($folder !== null) {
                $folder->setName($name, $supress_validation);
            }else{
                throw new \Exception(__("The given folder does not exist or you can not rename this folder.", RML_TD));
            }
            return true;
        }catch (\Exception $e) {
            $this->debug("Error:" . $e->getMessage(), __METHOD__);
            return array($e->getMessage());
        }
    }
    
    /*
     * @api wp_rml_delete()
     */
    public function remove($id, $supress_validation = false) {
        $this->debug("Try to delete folder id $id...", __METHOD__);
        
        try {
            $folder = wp_rml_get_object_by_id($id);
            
            if ($folder !== null) {
                // Check if other fails are counted
                if ($supress_validation === false) {
                    $errors = apply_filters("RML/Validate/Delete", array(), $id, $folder);
                    if (count($errors) > 0) {
                        throw new \Exception(implode(" ", $errors));
                    }
                }
                
                // Delete folder
                /*a
                 * This action is fired before a folder gets deleted. It allows you
                 * for example to throw an exception if the deletion should be stopped with an
                 * error message.
                 * 
                 * @param {IFolder} $folder The folder object
                 * @action RML/Folder/Predeletion
                 */
                do_action("RML/Folder/Predeletion", $folder);
                global $wpdb;
                $table_name = general\Core::getInstance()->getTableName();
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %d", $id));
                
                $table_name = general\Core::getInstance()->getTableName("posts");
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE fid = %d", $id));
                
                // Do the action
                $rowData = $folder->getRowData();
                wp_rml_structure_reset();
                
                /*a
                 * This action is fired after a folder is deleted.
                 *
                 * @param {int} $id The folder id
                 * @param {object} $rowData The SQL row data (raw) data for the deleted folder
                 * @action RML/Folder/Deleted
                 */
                do_action("RML/Folder/Deleted", $id, $rowData);
                $this->debug("Successfully deleted folder id $id", __METHOD__);
                
                return true;
            }else{
                throw new \Exception(__("The given folder does not exist.", RML_TD));
            }
        }catch (\Exception $e) {
            $this->debug("Error: " . $e->getMessage(), __METHOD__);
            return array($e->getMessage());
        }
    }
    
    /*
     * @api wp_rml_register_creatable()
     */
    public function registerCreatable($qualified, $type, $onRegister = false) {
        $this->creatables[$type] = $qualified;
        if ($onRegister) {
            call_user_func(array($qualified, 'onRegister'));
        }
    }
    
    public function getCreatables($type = null) {
        return $type === null ? $this->creatables : $this->creatables[$type];
    }
    
    public static function getInstance() {
        return self::$me === null ? self::$me = new CRUD() : self::$me;
    }
}