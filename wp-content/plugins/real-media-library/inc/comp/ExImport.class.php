<?php
namespace MatthiasWeb\RealMediaLibrary\comp;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Import and export functionality.
 */
class ExImport extends general\Base {
    
    private static $me = null;
    
    private $idOffset = null;
    
    private $columns = array();
    
    private function __construct() {
        $this->columns = explode(",", "name,ord,type,restrictions,contentCustomOrder,importId");
    }
    
    public function options_register() {
        add_settings_field(
            'rml_button_export',
            '<label for="rml_button_export">'.__('Export / Import folders' , RML_TD ).'</label>' ,
            array($this, 'html_rml_button_export'),
            'media',
            'rml_options_reset'
        );
        
        if (count($this->getHierarchicalTaxos())) {
            add_settings_field(
                'rml_button_import_cats',
                '<label for="rml_button_import_cats">'.__('Import taxonomy categories' , RML_TD ).'</label>' ,
                array($this, 'html_rml_button_import_cats'),
                'media',
                'rml_options_reset'
            );
        }
    }
    
    public function html_rml_button_export() { 
        echo '<button class="button button-primary rml-button-wipe"
                data-nonce-key="wipe" 
                data-action="rml_export" 
                data-method="">' . __('Export', RML_TD) . '</button>
        <button class="button rml-button-wipe"
                data-nonce-key="wipe"
                data-http-method="POST"
                data-action="rml_import" 
                data-method="">' . __('Import', RML_TD) . '</button>
        <p class="description" style="margin-bottom:10px">' . __('The export process will respect all available folders. The import process will not touch your existing structure and please make sure the import data does not have duplicate names with your current hierarchy.', RML_TD) . '</p>
        <div id="rml_export_data" style="float:left;margin-right: 10px;"><div>' . __('Exported data:', RML_TD) . '</div><textarea></textarea></div>
        <div id="rml_import_data" style="float:left;"><div>' . __('Import data:', RML_TD) . '</div><textarea></textarea></div>';
    }
    
    public function html_rml_button_import_cats() {
        foreach ($this->getHierarchicalTaxos() as $tax) {
            echo '<button class="button rml-button-wipe"
                    data-nonce-key="wipe" 
                    data-http-method="POST"
                    data-action="rml_import" 
                    data-method="' . $tax . '">' . __('Import', RML_TD) . ' \'' . $tax . '\'</button>
            <p class="description">' . __('Imports categories and post relations.', RML_TD) . '</p>';
        }
    }
    
    public function importTaxonomy($tax) {
        global $wpdb;
        
        // Import taxonomies
        $this->import($this->getCategories($tax));
        
        $table_name = $this->getTableName();
        $table_name_posts = $this->getTableName("posts");
        
        // Import posts
        $this->debug("Start importing posts for the taxonomy " . $tax . "...", __METHOD__);
        $sql = $wpdb->prepare("INSERT INTO $table_name_posts (`attachment`, `fid`, `importData`)
        SELECT p.ID AS attachment, rml.id AS fid, GROUP_CONCAT(rml.id SEPARATOR ',') AS importData
        FROM $wpdb->posts AS p
        INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id
        INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
        INNER JOIN $wpdb->terms AS t ON t.term_id = tt.term_id
        INNER JOIN $table_name AS rml ON t.term_id = rml.importId
        WHERE p.post_type = 'attachment' AND tt.taxonomy = %s
        GROUP BY p.ID
        ON DUPLICATE KEY UPDATE importData = VALUES(importData)", $tax);
        $wpdb->query($sql);
        
        // Fix isShortcut parameter
        general\Util::getInstance()->fixIsShortcutInPosts();
        
        // Import shortcuts
        $this->importShortcuts();
        $this->_clear();
        attachment\CountCache::getInstance()->resetCountCache();
    }
    
    /*
     * Search the wp_realmedialibrary_posts table for importData contains ","
     * and then create the shortcuts for the splitted "," folders.
     */
    public function importShortcuts() {
        global $wpdb;
        $this->debug("Start importing shortcuts...", __METHOD__);
        $table_name_posts = $this->getTableName("posts");
        $result = $wpdb->get_results("SELECT * FROM $table_name_posts WHERE importData LIKE \"%,%\"", ARRAY_A);
        
        // Grouping
        $group = array();
        foreach ($result as $result) {
            $folders = explode(",", $result["importData"]);
            
            // Iterate all needed folders
            foreach ($folders as $scFid) {
                if ($scFid != $result["fid"]) {
                    $isShortcut = $result["isShortcut"] > 0 ? $result["isShortcut"] : $result["attachment"];
                    $group[] = array("to" => $scFid, "attachment" => $isShortcut);
                }
            }
        }
        
        // Create shortcuts
        $group = general\Util::getInstance()->group_by($group, "to");
        foreach ($group as $to => $attachments) {
            $attachmentIds = array();
            foreach ($attachments as $attachment) {
                $attachmentIds[] = $attachment["attachment"];
            }
            wp_rml_create_shortcuts($to, $attachmentIds, true);
        }
    }
    
    /*
     * Import a tree with __children array and __metas array recursively.
     * 
     * @param array $tree The tree, for example from $this::getFolders()
     */
    public function import($tree) {
        $this->debug("Start importing a tree...", __METHOD__);
        $this->_import($tree);
        general\Util::getInstance()->resetAllSlugsAndAbsolutePathes();
        $this->debug("Importing done", __METHOD__);
        wp_rml_structure_reset();
    }
    
    /*
     * Get the folder tree of a taxonomy for import process.
     * 
     * @returns array
     */
    public function getCategories($tax) {
        global $wpdb;
        $util = general\Util::getInstance();
        
        $result = $wpdb->get_results($wpdb->prepare("SELECT t.term_id as importId, t.name, tt.parent
            FROM $wpdb->terms AS t 
            INNER JOIN $wpdb->term_taxonomy AS tt ON (t.term_id = tt.term_id) 
            WHERE tt.taxonomy = %s
            ORDER BY t.name ASC", $tax), ARRAY_A);
        $tree = $util->buildTree($result, 0, 'parent', 'importId', '__children');
        return $util->clearTree($tree, array("parent"), "__children");
    }
    
    /*
     * Get the folder tree for import process.
     * 
     * @returns array
     */
    public function getFolders() {
        global $wpdb;
        $util = general\Util::getInstance();
        
        // Folders
        $table_name = $this->getTableName();
        $folders = $wpdb->get_results("SELECT id,parent," . implode(",", $this->columns) . " FROM $table_name", ARRAY_A);
        
        // Metas
        $table_name = $this->getTableName("meta");
        $metas = $util->group_by($wpdb->get_results("SELECT * FROM $table_name", ARRAY_A), "realmedialibrary_id");

        // Group metas
        $grouped = array();
        foreach ($folders as $folder) {
            // Assign metas
            if (isset($metas[$folder["id"]])) {
                $folder["__metas"] = array();
                foreach ($metas[$folder["id"]] as $meta) {
                    unset($meta["meta_id"]);
                    unset($meta["realmedialibrary_id"]);
                    $folder["__metas"][] = $meta;
                }
            }
            $grouped[] = $folder;
        }
        
        // Create tree
        $tree = $util->buildTree($grouped, -1, 'parent', 'id', '__children');
        return $util->clearTree($tree, array("id", "parent"), '__children');
    }
    
    /*
     * Get the hierarchical taxonomies for the media taxonomy.
     * 
     * @returns String
     */
    public function getHierarchicalTaxos() {
        // Fetch the taxonomies which are able to filter
        $taxonomy_objects = get_object_taxonomies( 'attachment', 'objects' );
        $taxos = array();
        foreach ($taxonomy_objects as $key => $value) {
            if ($value->hierarchical == 1) {
                $taxos[] = $key;
            }
        }
        return $taxos;
    }
    
    /*
     * Clear importData and importId in posts and folder table.
     */
    private function _clear() {
        global $wpdb;
        $this->debug("Clear importData and importId in posts and folder table...", __METHOD__);
        $table_name = $this->getTableName();
        $table_name_posts = $this->getTableName("posts");
        $wpdb->query("UPDATE $table_name SET importId = NULL");
        $wpdb->query("UPDATE $table_name_posts SET importData = NULL");
    }
    
    /*
     * Import a tree with __children array and __metas array recursively.
     * 
     * @param array $tree The tree, for example from $this::getFolders()
     * @param int $idOffset The id offset for inserting unique keys
     * @param int $parent THe parent for the import
     */
    private function _import($tree, $idOffset = null, $parent = null) {
        global $wpdb;
        
        // Just to get the ID...
        if ($idOffset === null) {
            $this->idOffset = wp_rml_create("Import " . date("Y-m-d H:m:s"), _wp_rml_root(), RML_TYPE_FOLDER, array(), true);
            wp_rml_delete($this->idOffset, true);
        }
        
        // Set default parent
        if ($parent === null) {
            $parent = _wp_rml_root();
        }
        
        if (is_array($tree) && count($tree) > 0) {
            $values = array();
            
            // Flat
            foreach ($tree as $node) {
                $this->idOffset++;
                //error_log("[" . $this->idOffset . "]{" . $parent . "}: " . $node["name"]);
                
                // Create column values
                $columnValues = array($this->idOffset, $parent, get_current_user_id());
                foreach ($this->columns as $column) {
                    if (array_key_exists($column, $node)) {
                        $columnValues[] = $wpdb->prepare("%s", $node[$column]);
                    }else{
                        $columnValues[] = "DEFAULT";
                    }
                }
                $values[] = implode(",", $columnValues);
            }
            
            // SQL Insert
            $sql = "INSERT INTO " . $this->getTableName() . " (`id`,`parent`,`owner`,`" . implode("`,`", $this->columns) . "`) VALUES (" . implode("),(", $values) . ")";
            if ($wpdb->query($sql) !== false) {
                $lastParent = $wpdb->insert_id; // It is the last inserted id
                
                // Next depth and metas
                $metaValues = array(); // Values for metadata...
                foreach (array_reverse($tree) as $node) {
                    if (isset($node["__children"]) && is_array($node["__children"])) {
                        $this->_import($node["__children"], $this->idOffset, $lastParent);
                    }
                    
                    if (isset($node["__metas"]) && is_array($node["__metas"]) && count($node["__metas"]) > 0) {
                        foreach ($node["__metas"] as $meta) {
                            $metaValues[] = $wpdb->prepare("%d, %s, %s", $lastParent, $meta["meta_key"], $meta["meta_value"]);
                        }
                    }
                    $lastParent--;
                }
                
                // SQL Insert meta data
                if (count($metaValues) > 0) {
                    $wpdb->query("INSERT INTO " . $this->getTableName("meta") . " (`realmedialibrary_id`,`meta_key`,`meta_value`) VALUES (" . implode("),(", $metaValues) . ")");
                }
            }
        }
    }
    
    public static function getInstance() {
        return self::$me === null ? self::$me = new ExImport() : self::$me;
    }
}

?>