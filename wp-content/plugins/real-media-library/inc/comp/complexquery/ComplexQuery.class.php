<?php
namespace MatthiasWeb\RealMediaLibrary\comp\complexquery;
use MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Define your complex SQL query. When implementing your complex query do
 * not use global $wpdb; in your methods. Use ::getWpdb() instead.
 * 
 * A complex query can be splitted into three parts:
 * <ul>
 *  <li>1. Single Query: The query can be processed through one single SQL query with user defined variables</li>
 *  <li>2. Procedure: The procedure can write for example to an temporary table and reads again from this one</li>
 *  <li>3. Fallback: The fallback can throw an error or do the query through PHP functions</li>
 * </ul>
 * 
 * @example $rows = new MyComplexQuery($wpdb)->getResults();
 */
abstract class ComplexQuery extends general\Base {
    private $wpdb;
    
    private $isProcedurable;
    
    const CACHE_OPTION_NAME = "mw_complex_query";
    
    private $cache;
    
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
        
        // Check the cache
        $cache = $this->cache = $this->getCache();
        if ($cache === false || time() > $cache["expire"]) {
            $cache = array();
            $cache["expire"] = strtotime("15 days");
            $cache["isSQ"] = $this->isSingleQueriableWithUserDefinedVars();
            $cache["isP"] = $this->isProcedurable();
            update_site_option(ComplexQuery::CACHE_OPTION_NAME, $cache);
            $this->cache = $cache;
        }
    }
    
    /**
     * Get the result from the three different result types: singleQuery, procedure
     * or fallback.
     * 
     * @returns mixed
     */
    public function getResult() {
        if ($this->isSingleQueriableWithUserDefinedVars()) {
            return $this->singleQuery();
        }else if ($this->isMysqli() && $this->isProcedurable()) {
            return $this->procedure();
        }else{
            return $this->fallback();
        }
    }
    
    /**
     * This function is called when user defined variables are support. This method
     * should return your expected result. It works with both mysqli_connect and mysql_connect.
     * 
     * @see this::isSingleQueriableWithUserDefinedVars()
     * @returns mixed
     */
    abstract public function singleQuery();
    
    /**
     * This function is called when procedures (stored functions and
     * procedures) are available. It is also necessery that mysqli is in
     * use. mysql_connect does not support store_results() method. This method
     * should return your expected result. You should work with this::hasProcedure()
     * to install your procedure if not exists.
     * 
     * Note: A procedure can for example write into a temporary table and reads from it again.
     * 
     * @see this::isProcedurable()
     * @see this::hasProcedure()
     * @see this::install() 
     * @see this::getProcedureResults()
     * @returns mixed
     */
    abstract public function procedure();
    
    /**
     * This function is called when a single query is not possible and procedures
     * are not allowed.
     * 
     * @returns mixed
     */
    abstract public function fallback();
    
    /**
     * Start an installer. Use this function in your procedure() method.
     * 
     * @param callable $callable The callable to install the procedure for example
     */
    final public function install($callable) {
        // Avoid error messages in frontend
        require_once(RML_PATH . '/inc/others/install.php');
        rml_install(false, $callable);
    }
    
    /**
     * Call a "CALL proc" SQL and parse the results.
     * 
     * @param string $sql The SQL string to execute
     * @param boolean $returnTrue When the CALL is successfully and has no results then return true instead of an empty array
     * @returns Array or false when an error occured
     */
    final protected function getProcedureResults($sql, $returnTrue = false) {
        $mysqli = $this->getDbh();
        
        if (($result = $mysqli->query($sql)) === false) {
            return false;
        }
        
        $results = array();

        // The procedure can be empty
        if ($result === true) {
            return $returnTrue ? true : $results;
        }
        
        // The procedure has results
        while ($row = $result->fetch_array()) {
            $results[] = $row;
        }
        $result->close();
        return $results;
    }
    
    /**
     * Checks if a given procedure is available for the current user.
     * 
     * @returns boolean
     */
    public function hasProcedure($procedure) {
        $wpdb = $this->getWpdb();
        $sql = $wpdb->prepare("SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.ROUTINES
            WHERE UPPER(ROUTINE_TYPE)='PROCEDURE'
            AND UPPER(ROUTINE_SCHEMA)=UPPER(%s)
            AND UPPER(ROUTINE_NAME)=UPPER(%s)", $wpdb->dbname, $procedure);
        return $wpdb->get_var($sql) > 0;
    }
    
    /**
     * Checks if a single query is allowed to use @vars. For example MariaDB
     * does not resolve @vars in a single query when they are not declared before.
     * This method uses a cache (30 days).
     * 
     * @returns boolean
     */
    public function isSingleQueriableWithUserDefinedVars() {
        // Cache
        if (isset($this->cache["isSQ"])) {
            return $this->cache["isSQ"];
        }
        
        return $this->getWpdb()->get_var("SELECT @var := 5 AS myvar") == "5";
    }
    
    /**
     * Checks if procedures are allowed. This function uses a cache (30 days).
     * 
     * @see https://stackoverflow.com/questions/609855/check-user-rights-before-attempting-to-create-database
     * @returns boolean
     */
    public function isProcedurable() {
        // Cache
        if (isset($this->cache["isP"])) {
            return $this->cache["isP"];
        }
        
        $this->install(array($this, "_isProcedurable"));
        return $this->isProcedurable;
    }
    
    final public function _isProcedurable() {
        $wpdb = $this->getWpdb();
        $wpdb->query("DROP PROCEDURE IF EXISTS _wp_realmedialibrary");
        $this->isProcedurable = $wpdb->query("CREATE PROCEDURE _wp_realmedialibrary( ) BEGIN END");
    }
    
    /**
     * Checks if the database handle is mysqli or not.
     * 
     * @returns boolean
     */
    public function isMysqli() {
        return $this->wpdb->use_mysqli;
    }
    
    public function getWpdb() {
        return $this->wpdb;
    }
    
    public function getDbh() {
        return $this->wpdb->dbh;
    }
    
    public function getCache() {
        return get_site_option(ComplexQuery::CACHE_OPTION_NAME);
    }
}