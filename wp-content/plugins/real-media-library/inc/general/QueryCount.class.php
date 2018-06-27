<?php
namespace MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * fixed count bug when WPML in usage
 */
class QueryCount extends \WP_Query {
    
    public function __construct( $args = array() )
    {
        add_filter( 'posts_request',    array( $this, 'posts_request'   ), RML_PRE_GET_POSTS_PRIORITY);
        add_filter( 'posts_orderby',    array( $this, 'posts_orderby'   ), RML_PRE_GET_POSTS_PRIORITY);
        add_filter( 'post_limits',      array( $this, 'post_limits'     ), RML_PRE_GET_POSTS_PRIORITY);
        add_action( 'pre_get_posts',    array( $this, 'pre_get_posts'   ), RML_PRE_GET_POSTS_PRIORITY);
    
        parent::__construct( $args );
    }
    
    public function count()
    {
        if( isset( $this->posts[0] ) )
            return $this->posts[0];
    
        return '';          
    }
    
    public function posts_request( $request )
    {
        remove_filter( current_filter(), array( $this, __FUNCTION__ ), RML_PRE_GET_POSTS_PRIORITY );
        $sql = sprintf( 'SELECT COUNT(*) FROM ( %s ) as t', $request );
        return $sql;
    }
    
    public function pre_get_posts( $q )
    {
        $q->query_vars['fields'] = 'ids';
        remove_action( current_filter(), array( $this, __FUNCTION__ ), RML_PRE_GET_POSTS_PRIORITY );
    }
    
    public function post_limits( $limits )
    {
        remove_filter( current_filter(), array( $this, __FUNCTION__ ), RML_PRE_GET_POSTS_PRIORITY );
        return '';
    }
    
    public function posts_orderby( $orderby )
    {
        remove_filter( current_filter(), array( $this, __FUNCTION__ ), RML_PRE_GET_POSTS_PRIORITY );
        return '';
    }
}