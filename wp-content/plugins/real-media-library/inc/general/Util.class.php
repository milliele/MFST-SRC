<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Others functionality for the plugin.
 */
class Util extends Base {
	private static $me = null;
	private $nonces = null;
        
    private function __construct() {
        // Silence is golden.
    }
    
    /*
     * Adds nonces to the backend.
     * 
     * @filter RML/Backend/Nonces
     * @filter RML/Backend/Nonces/manage_options
     * @hooked RML/Backend/LocalizeJS
     */
    public function nonces($arr) {
        if ($this->nonces == null) {
            $this->nonces = array(
                "bulkMove" => wp_create_nonce("rmlAjaxBulkMove"),
                "bulkSort" => wp_create_nonce("rmlAjaxBulkSort"),
                "folderCount" => wp_create_nonce("rmlAjaxFolderCount"),
                "folderRename" => wp_create_nonce("rmlAjaxFolderRename"),
                "folderDelete" => wp_create_nonce("rmlAjaxFolderDelete"),
                "folderCreate" => wp_create_nonce("rmlAjaxFolderCreate"),
                "sidebarResize" => wp_create_nonce("rmlAjaxSidebarResize"),
                "treeContent" => wp_create_nonce("rmlAjaxTreeContent"),
                "shortcutInfo" => wp_create_nonce("rmlAjaxShortcutInfo"),
                "migrateDismiss" => wp_create_nonce("rmlAjaxMigrateDismiss")
            );
        }
        
        /*f
         * Add your own nonces with key value pairs (nonceName => nonce).
         * 
         * @param {array} $nonces The available nonces
         * @filter RML/Backend/Nonces
         * @returns {array}
         */
        $this->nonces = apply_filters("RML/Backend/Nonces", $this->nonces);
        
        // Add user orientated nonces
        if (current_user_can("manage_options")) {
            $this->nonces["wipe"] = wp_create_nonce("rmlAjaxWipe");
            
            /*f
             * Add your own nonces with key value pairs (nonceName => nonce).
             * This filter is only called when the current user has manage_options
             * permission.
             * 
             * @param {array} $nonces The available nonces
             * @filter RML/Backend/Nonces/manage_options
             * @returns {array}
             */
            $this->nonces = apply_filters("RML/Backend/Nonces/manage_options", $this->nonces);
        }
        
        $arr["nonces"] = $this->nonces;
        return $arr;
    }
    
    /*
     * Checks, if the permission to use a specific AJAX 
     * request is given. It automatically dies the current
     * screen and prints out an error.
     * 
     * @param nonce The nonce to check
     * @param cap The needed capability
     * @private
     */
    public function checkNonce($nonce = false, $cap = "upload_files") {
        if ($nonce !== false) {
            check_ajax_referer($nonce, 'nonce');
        }
        
        if (!current_user_can($cap) || !_wp_rml_active()) {
            wp_send_json_error(__("Something went wrong."));
        }
    }
    
    /*
     * Query multiple sql statements.
     * 
     * @param mixed sql statements
     */
    public function query() {
        global $wpdb;
        
        if (is_array(func_get_arg(0))) {
            $sqls = func_get_arg(0);
        }else{
            $sqls = func_get_args();
        }
        
        foreach ($sqls as $param) {
            $wpdb->query($param);
        }
    }
    
    public function doActionAnyParentHas($folder, $action, $args = null) {
        global $wpdb;
        if (!is_rml_folder($folder)) {
            return;
        }
        
        /*f
         * Add a condition after a defined action ($action) to check if any parent has a metadata.
         * It also includes the self folder so you can check for own folder metadata.
         * <strong>Note:</strong> All found parents are grouped and passed through an action
         * RML/$action/AnyParentHasMeta/$meta_key so you can execute your command. The allowed
         * $actions are:
         * <ul>
         *  <li>"Folder/Insert": Items are moved to the folder (see RML/Item/MoveFinished action for $args)</li>
         *  <li>Your action: Please contact Real Media Library developer to request another action</li>
         * </ul>
         * 
         * @param {string[]} $conditions The conditions which are joined together with OR
         * @param {IFolder} $folder The IFolder object
         * @param {arguments[]} $args The referenced arguments
         * @example <caption>Add a condition</caption>
         * $conditions[] = $wpdb->prepare("rmlmeta.meta_key = 'myMeta' AND rmlmeta.meta_value = %s", "test");
         * @returns {string[]} The conditions
         * @see IFolder::anyParentHasMetadata()
         * @see wp_rml_create_all_parents_sql()
         * @see RML/$action/AnyParentHasMeta/$meta_key
         * @filter RML/$action/AnyParentHasMeta
         * @since 3.3
         */
        $conditions = apply_filters("RML/" . $action . "/AnyParentHasMeta", array(), $folder, $args);
        if (count($conditions) > 0) {
            $sql = wp_rml_create_all_parents_sql($folder, true, null, array(
                "fields" => "rmlmeta.meta_id AS id, rmlmeta.realmedialibrary_id AS folderId, rmlmeta.meta_key, rmlmeta.meta_value AS value",
                "join" => "JOIN " . $this->getTableName("meta") . " rmlmeta ON rmlmeta.realmedialibrary_id = rmldata.id",
                "afterWhere" => "AND ((" . implode(") OR (", $conditions) . "))"
            ));
            
            if ($sql !== false) {
                $rows = $this->group_by($wpdb->get_results($sql, ARRAY_A), "meta_key");
                foreach ($rows as $meta_key => $metas) {
                    /*a
                     * This action is called for the results of the RML/$action/AnyParentHasMeta filter.
                     * <strong>Note:</strong> The allowed $actions are: See RML/$action/AnyParentHasMeta
                     * 
                     * @param {array} $metas Result array for this meta key, similar to IFolder::anyParentHasMetadata result
                     * @param {IFolder} $folder The IFolder object
                     * @param {arguments[]} $args The referenced arguments which are also passed to RML/$action/AnyParentHasMeta
                     * @param {array} $all_metas All found metas grouped by meta_key so you can check for multiple meta_keys
                     * @see RML/$action/AnyParentHasMeta
                     * @action RML/$action/AnyParentHasMeta/$meta_key
                     * @since 3.3
                     */
                    do_action("RML/" . $action . "/AnyParentHasMeta/" . $meta_key, $metas, $folder, $args, $rows);
                }
            }
        }
    }
    
    /*
     * Build a tree from an array.
     * 
     * @see https://stackoverflow.com/questions/8840319/build-a-tree-from-a-flat-array-in-php
     */
    public function buildTree(&$elements, $parentId = -1, $keyParent = 'parent', $key = 'id', $keyChildren = 'children') {
        $branch = array();
        foreach ($elements as &$element) {
            if ($element[$keyParent] == $parentId) {
                $children = $this->buildTree($elements, $element[$key], $keyParent, $key, $keyChildren);
                if ($children) {
                    $element[$keyChildren] = $children;
                }
                $branch[$element[$key]] = $element;
                unset($element);
            }
        }
        return $branch;
    }
    
    /*
     * Clears an array of a tree of the parent and id values.
     *
     * @param array $tree The result of this::buildTree
     * @param string[] $clear
     * @parma string $keyChildren
     */
    public function clearTree(&$tree, $clear = array(), $keyChildren = 'children') {
        foreach ($tree as &$node) {
            foreach ($clear as $c) {
                if (isset($node[$c])) {
                    unset($node[$c]);
                }
            }
            if (isset($node[$keyChildren])) {
                $this->clearTree($node[$keyChildren], $clear, $keyChildren);
                $node[$keyChildren] = array_values($node[$keyChildren]);
            }
        }
        return array_values($tree);
    }
    
    public function group_by($array, $key) {
        $return = array();
        foreach($array as $val) {
            $return[$val[$key]][] = $val;
        }
        return $return;
    }
    
    /**
     * Fixing the missing isShortcut parameter in wp_realmedialibrary_posts
     * when SC is given in the guid.
     */
    public function fixIsShortcutInPosts() {
        $this->debug("Fixing the isShortcut parameter in posts table...", __METHOD__);
        
        global $wpdb;
        $table_name_posts = $this->getTableName("posts");
        $result = $wpdb->get_results("SELECT rmlp.*, p.guid FROM $table_name_posts rmlp
        INNER JOIN $wpdb->posts p ON rmlp.attachment = p.ID
        WHERE rmlp.isShortcut = 0 AND p.guid LIKE '%?sc=%'", ARRAY_A);
        
        $this->debug("Found " . count($result) . " rows", __METHOD__);
        foreach ($result as $row) {
            preg_match('/\?sc=([0-9]+)$/', $row["guid"], $matches);
            if (isset($matches) && is_array($matches) && isset($matches[1])) {
                $sc = (int) $matches[1];
                $sql = $wpdb->query($wpdb->prepare("UPDATE $table_name_posts SET isShortcut = %d WHERE attachment = %d AND fid = %d AND isShortcut = %d",
                    $sc, $row["attachment"], $row["fid"], $row["isShortcut"]));
            }
        }
    }
    
    /**
     * Allows to reset all names with slugs and absolute pathes.
     */
    public function resetAllSlugsAndAbsolutePathes($remap = null) {
        global $wpdb;
        $this->debug("Start resetting names, slugs and absolute pathes...", __METHOD__);
        
        // Read rows
        $table_name = $this->getTableName();
        $sql = "SELECT t1.id, t2.name FROM ( SELECT t0.r_init AS id, IF(t0.reset_r = 1, (@r := t0.r_init), (@r := (select parent from $table_name where id = @r))) AS r, IF(t0.reset_r = 1, (@l := 1), (@l := @l + 1)) AS lvl FROM (SELECT m0.id as counter, m1.id AS r_init, ((SELECT min(id) FROM $table_name) = m0.id) AS reset_r FROM $table_name m0, $table_name m1 ORDER BY r_init, counter) t0 ORDER BY t0.r_init, t0.counter ) t1 INNER JOIN $table_name t2 ON t2.id = t1.r WHERE r <> -1 ORDER BY id, lvl DESC";
        $rows = $wpdb->get_results($sql, ARRAY_A); // folder|folderparentpart|name
        if (count($rows) > 0) {
            // Create migration table
            $table_name_reset = $this->getTableName("resetnames");
            require_once(RML_PATH . '/inc/others/install.php');
            rml_install(false, array($this, "resetAllSlugsAndAbsolutePathesTable"));
            
            // Clear already created resets
            $wpdb->query("DELETE FROM " . $table_name_reset);
            
            // Get rows and create
            $rows = $this->group_by($rows, "id");
            $sqlInserts = array();
            foreach ($rows as $fid => $parts) {
                $names = array();
                foreach ($parts as $part) {
                    $names[] = $part["name"];
                }
                if ($remap !== null) {
                    $names = array_map($remap, $names);
                }
                $slugs = array_map("_wp_rml_sanitize", $names);
                
                $partCount = count($names);
                $lastIdx = $partCount - 1;
                
                // Updateable columns
                $name = $names[$lastIdx];
                $slug = $slugs[$lastIdx];
                $absolutePath = implode("/", $slugs);
                
                // Add to update statement
                if (!empty($name)) {
                    $sqlInserts[] = $wpdb->prepare("%d,%s,%s,%s", $fid, $name, $slug, $absolutePath);
                }
            }
            
            // Create SQL INSERT statements
            $chunks = array_chunk($sqlInserts, 150);
            foreach ($chunks as $sqlInsert) {
                $sql = "INSERT INTO $table_name_reset VALUES (" . implode("),(", $sqlInserts) . ")";
                $wpdb->query($sql);
            }
            
            // Create UPDATE statement
            $wpdb->query("UPDATE $table_name AS rml
                LEFT JOIN $table_name_reset AS rmlnew ON rml.id = rmlnew.id
                SET rml.name = rmlnew.name, rml.slug = rmlnew.slug, rml.absolute = rmlnew.absolute");
            
            // Clear again
            //$wpdb->query("DELETE FROM " . $table_name_reset);
        }
        
        // Resolve duplicate slugs
        $this->debug("Reset finished", __METHOD__);
        $dups = $wpdb->get_results("SELECT rml.id, rml.slug
            FROM $table_name rml
            INNER JOIN (
             SELECT rmlSlug.slug
                FROM $table_name rmlSlug
                GROUP BY rmlSlug.slug
                HAVING COUNT( id ) > 1
            ) j ON rml.slug = j.slug", ARRAY_A);
        if (count($dups) > 0) {
            $this->debug("Resolving duplicate slugs...", __METHOD__);
            foreach ($this->group_by($dups, "slug") as $dupIds) {
                for ($i = 1; $i < count($dupIds); $i++) {
                    $folder = wp_rml_get_object_by_id($dupIds[$i]["id"]);
                    if ($folder !== null) {
                        $folder->updateThisAndChildrensAbsolutePath();
                    }
                }
            }
        }
    }
    
    public function resetAllSlugsAndAbsolutePathesTable() {
        $this->debug("Create reset table...", __METHOD__);
        $table_name = $this->getTableName("resetnames");
        $sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL,
			name tinytext NOT NULL,
			slug text DEFAULT '' NOT NULL,
			absolute text DEFAULT '' NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		dbDelta( $sql );
    }
    
    /**
     * @see wp_rml_create_all_parents_sql
     */
    public static function createSQLForAllParents($folder, $includeSelf = false, $until = null, $options = null) {
        global $wpdb;
        
        // Get folder id
        $folderId = $folder instanceof folder\Creatable ? $folder->getId() : $folder;
        if (!is_numeric($folderId)) {
            return false;
        }
        
        // Parse options
        $options = array_merge(array(
            "fields" => "rmldata.*, rmltmp.lvl AS lvlup",
            "join" => "",
            "where" => "rmltmp.lvl > " . ($includeSelf === true ? "-1" : "0"),
            "afterWhere" => "",
            "orderby" => "rmltmp.lvl ASC",
            "limit" => ""
        ), $options === null ? array() : $options);
        
        $table_name = general\Core::getInstance()->getTableName();
        return $wpdb->prepare("SELECT " . $options["fields"] . "
            FROM ( SELECT @r AS _id, (SELECT @r := parent FROM $table_name WHERE id = _id) AS parent, @l := @l + 1 AS lvl
                    FROM (SELECT @r := %d, @l := -1) vars, $table_name m
                    WHERE @r <> %d) rmltmp
            JOIN $table_name rmldata ON rmltmp._id = rmldata.id " . $options["join"] . "
            WHERE " . $options["where"] . " " . $options["afterWhere"] . "
            ORDER BY " . $options["orderby"] . " " . $options["limit"], $folderId, $until == null ? _wp_rml_root() : $until);
    }
    
    /**
     * @see wp_rml_create_all_children_sql
     */
    public static function createSQLForAllChildren($folder, $includeSelf = false, $options = null) {
        global $wpdb;
        
        // Get folder id
        $folderId = $folder instanceof folder\Creatable ? $folder->getId() : $folder;
        if (!is_numeric($folderId)) {
            return false;
        }
        
        // Parse options
        $options = array_merge(array(
            "fields" => "rmldata.*",
            "join" => "",
            "where" => $wpdb->prepare("rmldata._parent = %d", $folderId)
                        . ($includeSelf === true ? "" : $wpdb->prepare(" AND rmldata.id != %d", $folderId)),
            "afterWhere" => "",
            "orderby" => "rmldata.parent, rmldata.ord",
            "limit" => ""
        ), $options === null ? array() : $options);
        
        $table_name = general\Core::getInstance()->getTableName();
        return "SELECT " . $options["fields"] . "
            FROM (SELECT t0.*,
                IF(t0._r_reset = 1, (@r := t0._r_init), (@r := (SELECT m3.parent FROM $table_name m3 WHERE id = @r))) AS _parent,
                IF(t0._r_reset = 1, (@l := 0), (@l := @l + 1)) AS lvldown
              FROM 
                (SELECT m1.*, m0.id AS _r_counter, m1.id AS _r_init,
                   ((SELECT MIN(m2.id) FROM $table_name m2) = m0.id) AS _r_reset 
                 FROM $table_name m0 JOIN $table_name m1) t0 
              ORDER BY t0._r_init, t0._r_counter) rmldata
            " . $options["join"] . "
            WHERE " . $options["where"] . " " . $options["afterWhere"] . "
            ORDER BY " . $options["orderby"] . " " . $options["limit"];
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Util();
        }
        return self::$me;
    }
}

?>