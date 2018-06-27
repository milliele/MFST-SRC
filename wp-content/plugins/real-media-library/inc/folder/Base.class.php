<?php
namespace MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\api;
use MatthiasWeb\RealMediaLibrary\order;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Abstract base class for folders. It handles the available fields and getters / setters.
 */
abstract class Base extends general\Base implements api\IFolder {
    protected $id;
    
    protected $parent;
    
    protected $name;
    
    protected $cnt;

    protected $order;
    
    protected $slug;
    
    protected $absolutePath;
    
    protected $row;
    
    protected $children;
    
    protected $restrictions = array();
    
    protected $restrictionsCount = 0;

    protected $systemReservedFolders = array("/", "..", ".");
    
    protected $contentCustomOrder;
    
    public $orderNumbers = array(); // Can be cleared!
    
    public function anyParentHas($column, $value = null, $valueFormat = "%s", $includeSelf = false, $until = null) {
        global $wpdb;
        $sql = wp_rml_create_all_parents_sql($this, $includeSelf, $until, array(
            "fields" => "rmldata.id, rmldata.$column",
            "afterWhere" => $wpdb->prepare("AND rmldata.$column = $valueFormat", $value)
        ));
        return $sql === false ? array() : $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function anyParentHasMetadata($meta_key, $meta_value = null, $valueFormat = "%s", $includeSelf = false, $until = null) {
        global $wpdb;
        $sql = wp_rml_create_all_parents_sql($this, $includeSelf, $until, array(
            "fields" => "rmlmeta.meta_id AS id, rmlmeta.realmedialibrary_id AS folderId, rmlmeta.meta_value AS value",
            "join" => $wpdb->prepare("JOIN " . $this->getTableName("meta") . " rmlmeta ON rmlmeta.realmedialibrary_id = rmldata.id AND rmlmeta.meta_key = %s", $meta_key),
            "afterWhere" => $meta_value === null ? "AND TRIM(IFNULL(rmlmeta.meta_value,'')) <> ''" 
                                : $wpdb->prepare("AND rmlmeta.meta_value = $valueFormat", $meta_value)
        ));
        return $sql === false ? array() : $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function anyChildrenHas($column, $value = null, $valueFormat = "%s", $includeSelf = false) {
        global $wpdb;
        $sql = wp_rml_create_all_children_sql($this, $includeSelf, array(
            "fields" => "rmldata.id, rmldata.$column",
            "afterWhere" => $wpdb->prepare("AND rmldata.$column = $valueFormat", $value)
        ));
        return $sql === false ? array() : $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function anyChildrenHasMetadata($meta_key, $meta_value = null, $valueFormat = "%s", $includeSelf = false) {
        global $wpdb;
        $sql = wp_rml_create_all_children_sql($this, $includeSelf, array(
            "fields" => "rmlmeta.meta_id AS id, rmlmeta.realmedialibrary_id AS folderId, rmlmeta.meta_value AS value",
            "join" => $wpdb->prepare("JOIN " . $this->getTableName("meta") . " rmlmeta ON rmlmeta.realmedialibrary_id = rmldata.id AND rmlmeta.meta_key = %s", $meta_key),
            "afterWhere" => $meta_value === null ? "AND TRIM(IFNULL(rmlmeta.meta_value,'')) <> ''" 
                                : $wpdb->prepare("AND rmlmeta.meta_value = $valueFormat", $meta_value)
        ));
        return $sql === false ? array() : $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function hasChildren($name, $returnObject = false) {
        foreach ($this->getChildren() as $value) {
            if ($value->getName() == $name) {
                return $returnObject === true ? $value : true;
            }
        }
        return false;
    }
    
    public function getId() {
	    return $this->id;
	}
	
    public function getParent() {
        return $this->parent;
	}
	
    public function getAllParents($until = null) {
        global $wpdb;
        $sql = wp_rml_create_all_parents_sql($this, false, $until);
        return $sql === false ? array() : $wpdb->get_col($sql);
	}
	
    public function getName($htmlentities = false) {
	    return $htmlentities ? htmlentities($this->name) : $this->name;
	}
	
    public function getSlug($force = false, $fromSetName = false) {
        if ($this->slug == "" || $force) {
            $slugBefore = $this->slug;
            $this->slug = _wp_rml_sanitize($this->name, $this->id > -1, $this->id);
            
            // Update in database
            if ($this->id > -1) {
                if ($slugBefore != $this->slug) {
                    global $wpdb;
                    $table_name = $this->getTableName();
                    $wpdb->query($wpdb->prepare("UPDATE $table_name SET slug=%s WHERE id = %d", $this->slug, $this->id));
                    $this->debug("Successfully changed slug '$this->slug' in database", __METHOD__);
                }
                
                if (!$fromSetName) {
                    $this->updateThisAndChildrensAbsolutePath();
                }
            }
        }
        
        return $this->slug;
    }
    
    public function getPath($implode = "/", $map = "htmlentities") {
        $return = array($this->name);
        $folder = $this;
        while (true) {
            $f = wp_rml_get_object_by_id($folder->parent);
            if ($f !== null && $f->getType() !== RML_TYPE_ROOT) {
                $folder = $f;
                $return[] = $folder->name;
            }else{
                break;
            }
        }
        if ($map !== null) {
            $return = array_map($map, $return);
        }
        return implode($implode, array_reverse($return));
    }
    
    public function getOwner() {
        return $this->owner;
    }
    
    public function getAbsolutePath($force = false, $fromSetName = false) {
        if ($this->absolutePath == "" || $force) {
            $pathBefore = $this->absolutePath;
            $return = array($this->getSlug(true, true));
            $folder = $this;
            while (true) {
                $f = wp_rml_get_object_by_id($folder->getParent());
                if (is_rml_folder($f) && $f->getId() !== -1) {
                    $folder = $f;
                    $return[] = $folder->getSlug();
                }else{
                    break;
                }
            }
            $this->absolutePath = implode("/", array_reverse($return));
            
            // Update in database
            if ($this->id > -1) {
                if ($pathBefore != $this->absolutePath) {
                    global $wpdb;
                    $table_name = $this->getTableName();
                    $wpdb->query($wpdb->prepare("UPDATE $table_name SET absolute=%s WHERE id = %d", $this->absolutePath, $this->id));
                    $this->debug("Successfully changed absolute path '$this->absolutePath' in database", __METHOD__);
                }
                
                if (!$fromSetName) {
                    $this->updateThisAndChildrensAbsolutePath();
                }
            }
        }
        return $this->absolutePath;
    }
    
    public function getCnt($forceReload = false) {
        if ($this->cnt === null || $forceReload) {
            $query = new general\QueryCount(
                /*f
                 * Modify the query arguments to count items within a folder.
                 * 
                 * @param {array} $query The query with post_status, post_type and rml_folder
                 * @filter RML/Folder/QueryCountArgs
                 * @returns {array} The query
                 */
                apply_filters('RML/Folder/QueryCountArgs', array(
                	'post_status' => 'inherit',
                	'post_type' => 'attachment',
                	'rml_folder' => $this->id
                ))
            );
            if (isset($query->posts[0])) {
                $this->cnt = $query->posts[0];
            }else{
                $this->cnt = 0;
            }
        }
        return $this->cnt;
    }
    
    public function getChildren() {
        return $this->children;
    }
    
    public function getOrder() {
	    return $this->order;
	}
	
	public function getRestrictions() {
	    return $this->restrictions;
	}
	
    public function getRestrictionsCount() {
	    return $this->restrictionsCount;
	}
	
    public function getPlain() {
        $result = array(
            "id" => $this->getId(),
            "type" => $this->getType(),
            "parent" => $this->getParent(),
            "name" => $this->getName(),
            "order" => $this->getOrder(),
            "restrictions" => $this->getRestrictions(),
            "slug" => $this->getSlug(),
            "absolutePath" => $this->getAbsolutePath(),
            "cnt" => $this->getCnt(),
            "_links" => array(
                "this" => array(array(
                    "href" => general\REST::url("creatable/" . $this->getId())
                )),
                "children" => array(array(
                    "href" => general\REST::url("creatable/" . $this->getId() . "/children")
                ))
            )
        );
        if ($this->getId() != -1) {
            $result["_links"]["parent"] = array(array(
                "href" => general\REST::url("creatable/" . $this->getParent())
            ));
        }
        return $result;
    }
    
    public function setRestrictions($restrictions = array()) {
        $this->debug($restrictions, __METHOD__);
        $this->restrictions = $restrictions;
        $this->restrictionsCount = count($this->restrictions);
        
        // Save in Database
        if ($this->id > -1) {
            global $wpdb;
            $table_name = $this->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET restrictions=%s WHERE id = %d", implode(",", $restrictions), $this->id));
            $this->debug("Successfully saved restrictions in database", __METHOD__);
        }
    }
    
    public function is($folder_type) {
        return $this->getType() == $folder_type;
    }
    
    public function isRestrictFor($restriction) {
        return in_array($restriction, $this->restrictions) || in_array($restriction . ">", $this->restrictions);
    }
    
    public function isValidChildrenType($type) {
        $allowed = $this->getAllowedChildrenTypes();
        $this->debug("Check if children type '$type' of $this->id... is allowed here: " . (($allowed === true) ? "All is allowed here" : "Only " . json_encode($allowed) . " is allowed here"), __METHOD__);
        return $allowed === true ? true : in_array($type, $allowed);
    }
}