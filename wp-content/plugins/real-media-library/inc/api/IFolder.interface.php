<?php
namespace MatthiasWeb\RealMediaLibrary\api;

/**
 * This interface provides elementary getter and setter methods for folder objects. All folder
 * types (Folder, Collection, Gallery, ...) have implemented this interface.
 * Also the root ("Unorganized") is a folder and implements this interface. Usually,
 * the root acts as "-1" but you should use the {@link _wp_rml_root} function to get the
 * root id. If this interface does not provide an expected method, yet, have a look at the
 * other API files. For example to create a folder use {@link wp_rml_create}.
 * 
 * <strong>Check if a variable is surely a IFolder interface object:</strong>
 * <code>$folder = wp_rml_get_object_by_id(5);
 * if (is_rml_folder($folder)) {
 *      // It is an interface implementation of IFolder
 * }</code>
 * 
 * <h3>Register own folder type:</h3>
 * You can create your own implementation of a folder type (Gallery, Collection, Root, ...)
 * just have a look at the wp-content/plugins/real-media-library/inc/folder files. Here is a very basic example
 * and the static methods you must create for your class:
 * <code>/*
 *  * ABSTRACT METHODS YOU MUST IMPLEMENT IN YOUR FOLDER CLASS!
 *  *
 * /*
 *  * Creates an instance for this folder type. This line is commented out,
 *  * because PHP does not support abstract static functions. Please implement
 *  * this function in your folder class.
 *  * 
 *  * @param $rowData The row data from the database row
 *  * @return Instance or null
 *  *
 * /* public abstract static function instance($rowData); *
 * 
 * /*
 *  * (optional) If you use wp_rml_register_creatable() with the parameter $onRegister = true then
 *  * this function is called in your folder type class.
 *  *
 * /* public abstract static function onRegister(); *
 * 
 * /*
 *  * Creates a new instance for this folder type. This line is commented out,
 *  * because PHP does not support abstract static functions. Please implement
 *  * this function in your folder class.
 *  * 
 *  * @param $rowData The row data from the database row
 *  * @throws Exception when something went wrong by creating
 *  * @return Instance or null
 *  * @see Creatable::persist
 *  *
 * /* public abstract static function create($rowData); *</code>
 * 
 * Also have a look at the {@link wp_rml_register_creatable} function to register your class
 * (RML_TYPE_FOLDER is an unique defined integer for your folder type):
 * <code>wp_rml_register_creatable(RML_NS . '\\folder\\Folder', RML_TYPE_FOLDER);</code>
 * 
 * @see wp_rml_root_childs
 * @see wp_rml_get_object_by_id
 * @see wp_rml_get_by_id
 * @see wp_rml_get_by_absolute_path
 * @see wp_rml_objects
 * @see is_rml_folder
 * @see IFolderActions
 */
interface IFolder extends IFolderActions, IFolderContent {
    /**
     * Get all parents which meets a given column value or column value is not empty.
     * 
     * @param string $column The column name for the wp_realmedialibrary SQL table. "slug", "name", "absolutePath", ... This string is not escaped when you pass it through this function
     * @param mixed $value The value the column should have
     * @param string $valueFormat The value format for $value ($wpdb->prepare) This string is not escaped when you pass it through this function
     * @param boolean $includeSelf Set true to add self to list
     * @param int $until The highest allowed folder id. If null _wp_rml_root() is used
     * @returns array folderId => columnValue, first id is the first found parent
     * @since 3.3
     */
    public function anyParentHas($column, $value = null, $valueFormat = "%s", $includeSelf = false, $until = null);
    
    /**
     * Get all parents which meets a given meta key value or meta key value is not empty.
     * 
     * @param string $meta_key The meta key name for the wp_realmedialibrary_meta SQL table. This string is not escaped when you pass it through this function
     * @param mixed $meta_value The value the meta key should have
     * @param string $valueFormat The value format for $value ($wpdb->prepare) This string is not escaped when you pass it through this function
     * @param boolean $includeSelf Set true to add self to list
     * @param int $until The highest allowed folder id. If null _wp_rml_root() is used
     * @returns array Array with keys: id (meta_id), folderId, value (meta_value), first id is the first found parent
     * @since 3.3
     */
    public function anyParentHasMetadata($meta_key, $meta_value = null, $valueFormat = "%s", $includeSelf = false, $until = null);
    
    /**
     * Get all children which meets a given column value or column value is not empty.
     * 
     * @param string $column The column name for the wp_realmedialibrary SQL table. "slug", "name", "absolutePath", ... This string is not escaped when you pass it through this function
     * @param mixed $value The value the column should have
     * @param string $valueFormat The value format for $value ($wpdb->prepare) This string is not escaped when you pass it through this function
     * @param boolean $includeSelf Set true to add self to list
     * @returns array folderId => columnValue, first id is the first found child
     * @since 3.3
     */
    public function anyChildrenHas($column, $value = null, $valueFormat = "%s", $includeSelf = false);
    
    /**
     * Get all chilren which meets a given meta key value or meta key value is not empty.
     * 
     * @param string $meta_key The meta key name for the wp_realmedialibrary_meta SQL table. This string is not escaped when you pass it through this function
     * @param mixed $meta_value The value the meta key should have
     * @param string $valueFormat The value format for $value ($wpdb->prepare) This string is not escaped when you pass it through this function
     * @param boolean $includeSelf Set true to add self to list
     * @returns array Array with keys: id (meta_id), folderId, value (meta_value), first id is the first found child
     * @since 3.3
     */
    public function anyChildrenHasMetadata($meta_key, $meta_value = null, $valueFormat = "%s", $includeSelf = false);
    
    /**
     * Checks if this folder has a children with a given name.
     *  
     * @param string $name Name of folder
     * @param boolean $returnObject If set to true and a children with this name is found, then return the object for this folder
     * @returns boolean
     * @since 3.3 Now it checks for a given folder name instead the slug
     */
    public function hasChildren($name, $returnObject = false);
    
    /**
     * Return the type for the given folder. For example: 0 = Folder, 1 = Collection, 2 = Gallery
     * 
     * @returns int
     */
    public function getType();
    
    /**
     * Get all allowed children folder types.
     * 
     * @return boolean|int[] Array with allowed types or TRUE for all types allowed
     */
    public function getAllowedChildrenTypes();
    
    /**
     * Get the folder id.
     * 
     * @returns int
     */
    public function getId();
    
    /**
     * Get the parent folder id.
     * 
     * @returns int
     */
    public function getParent();
    
    /**
     * Get all parents of this folder.
     * 
     * @param int $until The highest allowed folder id. If null _wp_rml_root() is used
     * @returns int[] Folder ids, first id is the first parent
     * @since 3.3
     */
    public function getAllParents($until = null);
    
    /**
     * Get the folder name.
     * 
     * @param boolean $htmlentities If true the name is returned htmlentitied for output
     * @returns string
     */
    public function getName($htmlentities = false);
    
    /**
     * Returns a santitized title for the folder. If the slug is empty
     * or forced to, it will be updated in the database, too.
     * 
     * @param boolean $force Forces to regenerate the slug
     * @returns string
     */
    public function getSlug($force = false);
    
    /**
     * Creates a absolute path without slugging' the names.
     * 
     * @param string $implode Delimitter for the folder names
     * @param callable $map Map the names with this function. Pass null to skip this map function
     * @returns string htmlentitied path
     * @example <code>// Get valid physical folder name
     * $folder->getPath("/", "_wp_rml_sanitize_filename")</code>
     */
    public function getPath($implode = "/", $map = "htmlentities");
    
    /**
     * Get the creator/owner of the folder.
     * 
     * @returns int ID of the user
     * @since 3.3
     */
    public function getOwner();
    
    /**
     * Creates a absolute path. If the absolute path is empty
     * or forced to, it will be updated in the database, too.
     * 
     * @param boolean $force Forces to regenerate the absolute path
     * @returns string
     */
    public function getAbsolutePath($force = false);
    
    /**
     * Gets the count of the files in this folder.
     * 
     * @param boolean $forceReload If true the count cache gets reloaded
     * @returns int
     * @since 3.3.1
     */
    public function getCnt($forceReload = false);
    
    /**
     * Get children of this folder.
     * 
     * @return IFolder[]
     */
    public function getChildren();
    
    /**
     * Get the order number.
     * 
     * @returns int
     * @since 3.3.1
     */
    public function getOrder();
    
    /**
     * Get the maximal order number of the children.
     * 
     * @returns integer Max order number
     * @since 3.3.1
     */
    public function getMaxOrder();
    
    /**
     * Get the restrictions of this folder.
     * 
     * @returns string[]
     */
    public function getRestrictions();
	
	/**
	 * Get the count of the restrictions.
	 * 
	 * @returns int
	 */
    public function getRestrictionsCount();
    
    /**
     * Gets a plain array with folder properties.
     * 
     * @returns array
     */
    public function getPlain();
    
    /**
     * Get the full row of the SQL query.
     * 
     * @param string $field The field name
     * @returns mixed Any object or false
     * @since 3.3
     */
    public function getRowData($field = null);
    
    /**
     * Get the type name for this folder. For example: Folder, Collection, Gallery, Unorganized.
     * 
     * @param string $default The default (if null "Folder" is used as default)
     * @returns string
     * @since 3.3.1
     * @see Filter RML/Folder/Type/Name
     */
    public function getTypeName($default = null);
    
    /**
     * Get the type description for this folder.
     * 
     * @param string $default The default (if null folder description is used as default)
     * @returns string
     * @since 3.3.1
     * @see Filter RML/Folder/Type/Description
     */
    public function getTypeDescription($default = null);
    
    /**
     * Get the type icon for this folder.
     * 
     * @param string $default The default (if null folder icon is used as default)
     * @returns string
     * @since 3.3.1
     * @see Filter RML/Folder/Type/Icon
     */
    public function getTypeIcon($default = null);
    
    /**
     * Check if the folder object is a given type.
     * 
     * @param int $folder_type The folder type
     * @returns boolean
     */
    public function is($folder_type);
    
    /**
     * Checks if this folder has a special restriction.
     * 
     * @param string $restriction The restriction to check
     * @returns boolean
     * @see IFolder::setRestrictions()
     */
    public function isRestrictFor($restriction);
    
    /**
     * Checks if a given folder type is allowed in this folder.
     * 
     * @param int $type The type
     * @returns boolean
     * @see IFolder::getAllowedChildrenTypes()
     */
    public function isValidChildrenType($type);
}
?>