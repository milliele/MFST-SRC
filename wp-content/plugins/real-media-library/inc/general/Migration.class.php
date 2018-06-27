<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles all hooks for future updates (migrations).
 */
class Migration extends Base {
    private static $me = null;
    
    private function __construct() {
        // Silence is golden.
    }
    
    /*
     * @hooked plugins_loaded
     */
    public function plugins_loaded() {
        add_action("RML/Sidebar/Content",           array($this, "sidebar_content"));
        add_action("RML/Options/Register",          array($this, "register_options_07102016"));
        add_filter("RML/Migration/07102016",        array($this, "migrate_07102016"));
        
        add_filter("RML/Migration/20161229",        array($this, "migrate_20161229"));
        add_filter("RML/Migration/20170925",        array($this, "migrate_20170925"));
        add_action("RML/Options/Register",          array($this, "register_options_20161229"));
    }
    
    /*
     * ======================================================
     * MIGRATION BUILD FROM:    <= 3.2.2 to >= 3.3
     *           BUILD DATE:     20170925
     * ======================================================
     * 
     * @migration 20170925
     */
    /*
     * Do the migration to save the current owner to the database and reset the slugs.
     */
    public function migrate_20170925($arr) {
        global $wpdb;
        Util::getInstance()->resetAllSlugsAndAbsolutePathes("html_entity_decode");
        $wpdb->update($this->getTableName(), array("owner" => get_current_user_id()), array("owner" => "0"));
        $this->debug("Migrate the owner of all folders to user with id " . get_current_user_id(), __METHOD__);
        $arr[2] = false;
        return $arr;
    }
    
    /*
     * ======================================================
     * MIGRATION BUILD FROM:    <= 2.7.2 to >= 2.8
     *           BUILD DATE:     20161229
     * ======================================================
     * 
     * @migration 20161229
     */
     
    /*
     * Do the migration if no multisite.
     */
    public function migrate_20161229($arr) {
        $this->do_20161229();
        $arr[2] = false;
        return $arr;
    }
     
    /*
     * Do the migration.
     */
    public function do_20161229() {
        $this->debug("Migrate the order table to the posts table...", __METHOD__);
        
        global $wpdb;
        $show_errors = $wpdb->show_errors(false);
		$suppress_errors = $wpdb->suppress_errors(false);
        $exists = $wpdb->get_var("SELECT 'exists' AS e FROM " . $this->getTableName("order") . " LIMIT 1");
        if ($exists === "exists") {
            $this->debug("The order table exists... run migration query", __METHOD__);
            
            // Change the nr and oldCustomNr to _posts, ignore fid because at this time (2.8) there are no shortcuts allowed
            $sql = "UPDATE " . $this->getTableName("posts") . " AS rmlp
            LEFT JOIN " . $this->getTableName("order") . " AS rmlo ON rmlo.attachment = rmlp.attachment
            SET rmlp.nr = rmlo.nr, rmlp.oldCustomNr = rmlo.oldCustomNr
            WHERE rmlp.isShortcut <= 0 AND rmlp.nr IS NULL";
            $wpdb->query($sql);
            
            // Check, which folders have a custom order
            $sql = "UPDATE " . $this->getTableName() . " AS rml
            SET rml.contentCustomOrder = ( IF( EXISTS ( SELECT 1 FROM " . $this->getTableName("order") . " AS rmlo WHERE fid = rml.id),  '1',  '0' ))
            WHERE rml.contentCustomOrder = 0";
            $wpdb->query($sql);
            $this->debug("Migration sucessful", __METHOD__);
        }else{
            $this->debug("No order table available", __METHOD__);
        }
        $wpdb->show_errors($show_errors);
		$wpdb->suppress_errors($suppress_errors);
    }
    
    /*
     * Register a migration button to copy the files from wp_postmeta
     * to wp_realmedialibrary_posts table.
     * 
     * @hooked RML/Options/Register
     * @see this::do_07102016
     */
    public function register_options_20161229() {
        $migrations = $this->getMigrations();
        $build = "20161229";
        
        if (isset($migrations[$build])) {
            add_settings_field(
                'rml_button_migrate_20161229',
                '<label>'.__('Migration <= 2.7.2' , RML_TD ).'</label>',
                array($this, 'html_rml_button_20161229'),
                'media',
                'rml_options_migration'
            );
        }
    }
    
    public function html_rml_button_20161229() {
        echo '<button class="rml-button-wipe button" data-nonce-key="migrateDismiss" data-action="rml_migration" data-method="20161229">' . __('Import gallery order', RML_TD) . '</button>
            <p class="description">' . __("You have used an old version (<= 2.7.2) of RML and the new version (>= 2.8) has changed the place where the image gallery order is stored.", RML_TD) . '</p>';
    }
    
    /*
     * ======================================================
     * MIGRATION BUILD FROM:    <= 2.6.3 to >= 2.6.4
     *           BUILD DATE:     07102016
     * ======================================================
     * 
     * @migration 07102016
     */
     
    /*
     * Do the migration if no multisite.
     */
    public function migrate_07102016($arr) {
        $this->do_07102016();
        $arr[2] = false;
        return $arr;
    }
     
    public function sidebar_content($folders) {
        $migrations = $this->getMigrations();
        $build = "07102016";
        
        if (isset($migrations[$build])
            && $migrations[$build][2]) {
            
            if ($folders->getCntAttachments() > 0) {
                echo '<div class="notice inline notice-warning notice-alt">
                    <p>
                        Uaaargh! Your image relationships can be broken now! It is because RML has some technical changes in the new version 2.6.4.
                        Go to <strong>Media Settings > RML - "Reset"</strong> and import the old relationships.
                        <a href="' . admin_url("options-media.php") . '">Goto Media settings</a> or 
                        <a href="#" class="rml-migration-dismiss" data-build="' . $build . '">dismiss this notice</a>.
                    </p>
                </div>';
            }
        }
    }
    
    /*
     * Do the migration of the current blog id.
     */
    public function do_07102016() {
        global $wpdb;
        
        $this->debug("Do migration", __METHOD__);
        $sql = 'INSERT IGNORE INTO ' . Core::getInstance()->getTableName("posts") . ' (`attachment`, `fid`, `isShortcut`)
        SELECT wpp.ID AS attachment, rml.id AS fid, 0 AS isShortcut
        FROM ' . $wpdb->postmeta . ' AS wpm
        INNER JOIN ' . $wpdb->posts . ' AS wpp ON wpp.ID = wpm.post_id
        LEFT JOIN ' . Core::getInstance()->getTableName() . ' AS rml ON rml.id = wpm.meta_value
        WHERE wpm.meta_key = "_rml_folder"
        AND rml.id > 0
        ON DUPLICATE KEY UPDATE fid=VALUES(fid), isShortcut=VALUES(isShortcut);';
        $wpdb->query($sql);
        attachment\CountCache::getInstance()->resetCountCache();
    }
    
    /*
     * Register a migration button to copy the files from wp_postmeta
     * to wp_realmedialibrary_posts table.
     * 
     * @hooked RML/Options/Register
     * @see this::do_07102016
     */
    public function register_options_07102016() {
        $migrations = $this->getMigrations();
        $build = "07102016";
        
        if (isset($migrations[$build])) {
            add_settings_field(
                'rml_button_migrate_07102016',
                '<label>'.__('Migration <= 2.6.3' , RML_TD ).'</label>',
                array($this, 'html_rml_button_07102016'),
                'media',
                'rml_options_migration'
            );
        }
    }
    
    public function html_rml_button_07102016() {
        echo '<button class="rml-button-wipe button" data-nonce-key="migrateDismiss" data-action="rml_migration" data-method="07102016">' . __('Import image relationships', RML_TD) . '</button>
            <p class="description">' . __("You have used an old version (<= 2.6.3) of RML and the new version (>= 2.6.4) has changed the place where the image relationships are stored.", RML_TD) . '</p>';
    }
    
    /*
     * ======================================================
     * CREATE MIGRATIONS
     * 
     * Determines in a new update, if there is a migration available.
     * 
     * Array of migrations: {
     *    Idx:  Build date
     *      [0] => $old version
     *      [1] => $new version
     *      [2] => Boolean if migration is active and not finished, yet
     * }
     * @hooked RML/Migration
     */
    public function migration($old, $new) {
        if (!is_string($old)) {
            return;
        }
        
        $migrations = $this->getMigrations();
        
        /*
         * Old:     <= 2.6.3
         * New:     >= 2.6.4
         * Then:    Migrate the meta key values to the new table
         */
        if (version_compare($old, "2.6.3", "<=") && version_compare($new, "2.6.4", ">=")) {
            $migrations["07102016"] = $this->doMigration("07102016", "2.6.3", "2.6.4");
        }
        
        /*
         * Old:     <= 2.7.2
         * New:     >= 2.8
         * Then:    Migrate the order of galleries to the _posts table
         */
        if (version_compare($old, "2.7.2", "<=") && version_compare($new, "2.8", ">=")) {
            $migrations["20161229"] = $this->doMigration("20161229", "2.7.2", "2.8");
        }
        
        /*
         * Old:     <= 3.2.2
         * New:     >= 3.3
         * Then:    Migrate the order of galleries to the _posts table
         */
        if (version_compare($old, "3.2.2", "<=") && version_compare($new, "3.3", ">=")) {
            $migrations["20170925"] = $this->doMigration("20170925", "3.2.2", "3.3");
        }
        
        $this->updateMigration($migrations);
    }
    
    /*
     * @see this::migration
     */
    private function doMigration($build, $from, $to) {
        return apply_filters("RML/Migration/" . $build, array($from, $to, true));
    }
    
    /*
     * Dismiss an update notice.
     * 
     * @param $build The build version of the update
     * @return boolean
     */
    public function dismiss($build) {
        $migrations = $this->getMigrations();
        if (isset($migrations[$build])
            && $migrations[$build][2]) {
                $migrations[$build][2] = false;
                $this->updateMigration($migrations);
                return true;
        }else{
            return false;
        }
    }
    
    public function getMigrations() {
        // Migrate the old migration system, LOL
        if (is_multisite()) {
            $comp2_8 = get_site_option("rml_migration");
            if (is_array($comp2_8)) {
                // Save the network-wide option to the blog-wide options
                
                // WordPress 4.6
                if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
                    $sites = get_sites();
                    foreach ( $sites as $site ) {
                        switch_to_blog( $site->blog_id );
                        update_option("rml_migration", $comp2_8);
                        restore_current_blog();
                    }
                }
                
                // WordPress < 4.6
                if ( function_exists( 'wp_get_sites' ) ) {
                    $sites = wp_get_sites();
                    foreach ( $sites as $site ) {
                        switch_to_blog( $site['blog_id'] );
                        update_option("rml_migration", $comp2_8);
                        restore_current_blog();
                    }
                }
                delete_site_option("rml_migration");
            }
        }
        
        return get_option("rml_migration", array());
    }
    
    public function updateMigration($migrations) {
        update_option("rml_migration", $migrations);
    }

    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Migration();
        }
        return self::$me;
    }
}