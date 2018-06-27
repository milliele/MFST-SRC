<?php
namespace MatthiasWeb\RealMediaLibrary\api;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Structure implementation for Real Media Library. It handles all SQL query which
 * reads all folders from the database and "collects" it into one tree. You can modify the
 * structure queries by RML/Tree* filters and extending the MatthiasWeb\RealMediaLibrary\attachment\Structure
 * class (implements IStructure).
 * 
 * @see wp_rml_structure_reset()
 * @see wp_rml_structure()
 * @since 3.3.1
 */
interface IStructure {
    /**
     * Start reading a structure. If you pass a $root parameter the parameter is not
     * automatically respected. You should then use your own implementation or filters
     * to respect the root. Use this constructor to add your filters and respect your
     * custom Structure class implementation.
     * 
     * @param integer $root The root folder defined for the structure
     * @param array $data Custom data for the structure
     */
    public function __construct($root = null, $data = null);
    
    /**
     * Checks, if the SQL result is available and load it if not.
     */
    public function initialLoad();
    
    /**
     * Resets the data of the structure.
     * 
     * @param integer $root The root folder
     * @param boolean $fetchData Determine, if the data should be refetched
     * @see wp_rml_structure_reset()
     */
    public function resetData($root = null, $fetchData = true);
    
    /*
     * Get a folder by id.
     * 
     * @param integer $id The id of the folder
     * @param boolean $nullForRoot If set to false and $id == -1 then the Root instance is returned
     * @returns IFolder The folder object or null if not found
     * @see wp_rml_get_object_by_id()
     * @see wp_rml_get_by_id()
     */
    public function byId($id, $nullForRoot = true);
    
    /*
     * Get a folder by absolute path.
     * 
     * @param string $path The path
     * @returns IFolder The folder object or null if not found
     * @see wp_rml_get_by_absolute_path
     */
    public function byAbsolutePath($path);
    
    /**
     * Get the SQL query result instead of IFolder objects.
     * 
     * @returns object[] The SQL result
     */
    public function getRows();
    
    /**
     * Get all SQL query results as IFolder objects.
     * 
     * @returns IFolder[] The folders
     */
    public function getParsed();
    
    /**
     * Get all SQL query results placed to a tree. That means it is a "hierarchical"
     * result where you work with ->getChildren(). The first level contains the top folders.
     * 
     * @return IFolder[] The folders
     */
    public function getTree();
    
    /**
     * Get the attachment count for this structure.
     * 
     * @returns integer Count
     */
    public function getCntAttachments();
    
    /**
     * Get the attachment count for the "/ Unorganized" folder for this structure.
     * 
     * @returns integer Count
     */
    public function getCntRoot();
    
    /**
     * Get the custom data.
     * 
     * @returns array Data
     */
    public function getData();
    
    /**
     * Set the custom data.
     * 
     * @param array $data The custom data
     */
    public function setData($data);
}