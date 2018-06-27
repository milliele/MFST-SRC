<?php
namespace MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\api;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles all hooks and functions for the structur.
 * If something will print out, this is a fasade-wrapper function
 * for the class general\View (stored in private $view).
 */
class Structure extends general\Base implements api\IStructure {
    
    private static $me = null;
    
    /*
     * The structure should be accessible within one
     * or more blogs. For those purposes use the wp standard
     * method switch_to_blog();
     * 
     * This is an array of Structure objects.
     * 
     * @see self::getInstance()
     */
    private static $blogs = array();
    
    /*
     * The root folder ID. Can only be set by the constructor!
     */
    private $root;
    
    /*
     * Array of Databased readed rows
     */
    private $rows;
    
    /*
     * $rows formed to folder\Folder objects
     */
    private $parsed;
    
    /*
     * Tree of folder\Folder objects. @see $childrens of folder\Folder object.
     */
    private $tree;
    
    /**
     * The view handler for this structure
     */
    private $view;
    
    /*
     * Checks, if the folder tree is already loaded and do the initial
     * load if needed by an function. So, RML can guarantee lazy loading.
     * 
     * @see this::initialLoad()
     */
    private $hasInitialLoad = false;
    
    /**
     * Additional data passed to the structure.
     */
    private $data;
    
    // Documentated in IStructure
    public function __construct($root = null, $data = null) {
        $this->view = new general\View($this);
        $this->data = $data === null ? array() : $data;
        $this->resetData($root, false);
    }
    
    // Documentated in IStructure
    public function initialLoad() {
        if (!$this->hasInitialLoad) {
            $this->hasInitialLoad = true;
            $this->resetData($this->root);
        }
    }
    
    // Documentated in IStructure
    public function resetData($root = null, $fetchData = true) {
        $this->root = $root === null ? _wp_rml_root() : $root;
        $this->rows = array();
        $this->parsed = array();
        $this->tree = array();
    
        if ($fetchData) {
            $this->fetch();
        }else{
            $this->hasInitialLoad = false;
        }
    }
    
    /*
     * Fetching all available folders into an array.
     */
    private function fetch() {
        global $wpdb;
        
        $table_name = general\Core::getInstance()->getTableName();
        
        /*f
         * Modify the tree SQL select fields statement. Just push your
         * fields to select custom fields.
         * 
         * @param {array} $fields The standard RML fields
         * @param {IStructure} $structure The structure
         * @filter RML/Tree/SQLStatement/SELECT
         * @returns {array}
         */
        $fields = join(", ", apply_filters("RML/Tree/SQLStatement/SELECT", array(
            // The whole row of the folder
            "tn.*",
            // Count images for this folder
            "IFNULL(tn.cnt, (SELECT COUNT(*)
            	FROM " . general\Core::getInstance()->getTableName("posts") . " AS rmlpostscnt
            	WHERE rmlpostscnt.fid = tn.id
            )) AS cnt_result"
        ), $this));
        
        /*f
         * Modify the tree SQL select join statement. Just push your
         * joins to $fields array.
         * 
         * @param {array} $fields The standard RML fields
         * @param {IStructure} $structure The structure
         * @filter RML/Tree/SQLStatement/JOIN
         * @returns {array} $fields
         */
        $joins = join(" ", apply_filters("RML/Tree/SQLStatement/JOIN", array(), $this));

        /*f
         * Modify the full tree SQL statement.
         * 
         * @param {string} $sql The sql query
         * @param {IStructure} $structure The structure
         * @filter RML/Tree/SQLStatement
         * @returns {string}
         */
        $sqlStatement = apply_filters("RML/Tree/SQLStatement", array("
            SELECT " . $fields . "
            FROM $table_name AS tn
            $joins
            ORDER BY parent, ord"), $this);
        
        $this->rows = $wpdb->get_results($sqlStatement[0]);
        
        /*f
         * The tree content is loaded.
         * 
         * @param {object[]} $rows The SQL results
         * @param {IStructure} $structure The structure
         * @filter RML/Tree/SQLRows
         * @returns {object[]}
         */
        $this->rows = apply_filters("RML/Tree/SQLRows",  $this->rows, $this);
        
        $this->parse();
    }
    
    /*
     * This functions parses the readed rows into folder objects.
     * It also handles the `cnt` cache for the attachments in this folder.
     * 
     * @see CountCache::updateCountCache
     */
    private function parse() {
        if (!empty($this->rows)) {
            $noCntCache = false;
            foreach ($this->rows as $key => $value) {
                // Check for image cache
                if (is_null($value->cnt)) {
                    $noCntCache = true;
                }
                
                // Prepare the data types
                $this->rows[$key]->id = intval($value->id);
                $this->rows[$key]->parent = intval($value->parent);
                $this->rows[$key]->ord = intval($value->ord);
                $this->rows[$key]->cnt_result = intval($value->cnt_result);
                
                // Craetable instance
                $creatable = folder\CRUD::getInstance()->getCreatables($value->type);
                if (isset($creatable)) {
                    $result = call_user_func_array(array($creatable, 'instance'), array($value));
                    if (is_rml_folder($result)) {
                        $this->parsed[] = $result;
                    }
                }
            }
            
            if ($noCntCache) {
                CountCache::getInstance()->updateCountCache();
            }
        }
        
        // Create the tree
        $folder = null;
        foreach($this->parsed as $key => $category){
            $parent = $category->getParent();
            if ($parent > -1) {
                $folder = $this->byId($parent);
                if ($folder !== null) {
                    $folder->addChildren($category);
                }
            }
        }
        
        $cats_tree = array();
        foreach ($this->parsed as $category) {
            if ($category->getParent() <= -1) {
                $cats_tree[] = $category;
            }
        }
        $this->tree = $cats_tree;
    }
    
    // Documentated in IStructure
    public function byId($id, $nullForRoot = true) {
        if (!$nullForRoot && $id == -1) {
            return folder\Root::getInstance();
        }
        
        foreach ($this->getParsed() as $folder) {
            if ($folder->getId() == $id) {
                return $folder;
            }
        }
        
        /*f
         * When a folder is not found by an absolute path this filter is
         * called and looks up for folders which are perhaps handled by other
         * structures.
         * 
         * @param {IFolder} $folder The folder (null if not found)
         * @param {integer} $id The searched Id
         * @param {IStructure} $structure The structure
         * @filter RML/Tree/ResolveById
         * @returns {IFolder} The found folder or null if not found
         * @since 3.3.1
         */
        return apply_filters("RML/Tree/ResolveById", null, $id, $this);
    }
    
    // Documentated in IStructure
    public function byAbsolutePath($path) {
        $path = trim($path, '/');
        foreach ($this->getParsed() as $folder) {
            if (strtolower($folder->getAbsolutePath()) == strtolower($path)) {
                return $folder;
            }
        }
        
        /*f
         * When a folder is not found by an absolute path this filter is
         * called and looks up for folders which are perhaps handled by other
         * structures.
         * 
         * @param {IFolder} $folder The folder (null if not found)
         * @param {string} $path The searched path
         * @param {IStructure} $structure The structure
         * @filter RML/Tree/ResolveByAbsolutePath
         * @returns {IFolder} The found folder or null if not found
         * @since 3.3.1
         */
        return apply_filters("RML/Tree/ResolveByAbsolutePath", null, $path, $this);
    }
    
    // Documentated in IStructure
    public function getRows() {
        $this->initialLoad();
        return $this->rows;
    }
    
    // Documentated in IStructure
    public function getParsed() {
        $this->initialLoad();
        return $this->parsed;
    }
    
    // Documentated in IStructure
    public function getTree() {
        $this->initialLoad();
        return $this->tree;
    }
    
    // Documentated in IStructure
    public function getCntAttachments() {
        if (has_filter("RML/Tree/CountAttachments")) {
            /*f
             * Counts all attachments which are available in the structure.
             * 
             * @param {integer} $count The count
             * @param {object} $structure The structure class
             * @returns {integer} The count
             * @filter RML/Tree/CountAttachments
             */
            return apply_filters("RML/Tree/CountAttachments", 0, $this);
        }
        return wp_count_posts('attachment')->inherit;
    }
    
    // Documentated in IStructure
    public function getCntRoot() {
        $cnt = 0;
        foreach ($this->getParsed() as $folder) {
            $cnt += $folder->getCnt();
        }
        $result = $this->getCntAttachments() - $cnt;
        return $result >= 0 ? $result : 0;
    }
    
    public function getView() {
        return $this->view;
    }
    
    // Documentated in IStructure
    public function getData() {
        return $this->data;
    }
    
    // Documentated in IStructure
    public function setData($data) {
        $this->data = is_array($data) ? $data : array();
    }
    
    public static function getInstance() {
        $bid = get_current_blog_id();
        return !isset(self::$blogs[$bid]) ? self::$blogs[$bid] = new Structure() : self::$blogs[$bid];
    }
}

?>