<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * When we want to set parent of a folder and the given name
 * already exists in the parent folder.
 * 
 * @see Creatable::setParent()
 * @see Creatable::setName()
 */
class FolderAlreadyExistsException extends \Exception {
    private $parent; // Parent ID
    private $name; // The name which exists in this folder

    public function __construct($parent, $name, $code = 0, Exception $previous = null) {
        parent::__construct(sprintf(__("'%s' already exists in this folder.", RML_TD), htmlentities($name)), $code, $previous);
        $this->parent = $parent;
        $this->name = $name;
    }
    
    /*
     * Get the folder of the children in this parent.
     */
    public function getFolder() {
        $parent = wp_rml_get_object_by_id($this->getParentId());
        return $parent->hasChildren($this->getName(), true);
    }
    
    public function getParentId() {
        return $this->parent;
    }
    
    public function getName() {
        return $this->name;
    }
}