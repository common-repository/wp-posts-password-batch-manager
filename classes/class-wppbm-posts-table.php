<?php

defined('ABSPATH') or exit;

if ( ! class_exists( 'WP_List_Table' ) ){

     require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WPPBM_Posts_Table
 * @extends WP_List_Table
 */
class WPPBM_Posts_Table extends WP_List_Table {

    public $index;

    public function __construct(){
        global $status, $page;

        $this->index = 0;

        parent::__construct( array(
            'singular'  => 'post',
            'plural'    => 'posts',
            'ajax'      => false
        ) );
    }

    public function get_columns(){
        $columns = array(
            'cb'                    => '<input type="checkbox"/>',
            'post_id'           => __( 'ID'),
            'post_title'   => __( 'Title'),
            'author'     => __( 'Author'),
            'password'              => __( 'Password'),
            'post_date'               => __( 'Date'),
        );
        return $columns;
    }

    public function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ 'id',
            /*$2%s*/ $item->ID
        );
    }

    public function wpbapp_get_posts_data($post_type='posts',$num=10){
        global $wpdb;

         $args = array(
            'posts_per_page'   => $num,
            'offset'           => 0,
            'category'         => '',
            'orderby'          => 'post_date',
            'order'            => 'DESC',
            'include'          => '',
            'exclude'          => '',
            'meta_key'         => '',
            'meta_value'       => '',
            'post_type'        => $post_type,
            'post_mime_type'   => '',
            'post_parent'      => '',
            'post_status'      => 'publish',
            'suppress_filters' => true
        );

        $posts= get_posts( $args );
        return $posts;

    }


    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
        	case 'post_id' :
        		return $item->ID;
            case 'post_title' :
                return $item->post_title;
            case 'author':
               $user = get_user_by( 'id', $item->post_author );
               return $user->user_login;
            case 'password' :
                return $item->post_password;
        		return $remaining;
        	case 'post_date' :
            /*get_option( 'date_format' )*/
        		return  $item->post_date;
        }
	}

    public function get_sortable_columns() {
        $sortable_columns = array(
            'post_date'       => array( 'post_date', true ),
        );
        return $sortable_columns;
    }

    public function get_bulk_actions() {
        $actions = array(
            'check'    => __( 'Add Password', WPPBM_SLUG ),
            'deletepsw'   => __( 'Delete Password', WPPBM_SLUG ),

        );
        return $actions;
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        global $wpdb;

        if ( ! isset( $_POST['id'] ) ) return;

        $_POST = stripslashes_deep($_POST);

        if (
            ! isset( $_POST['wc-posts-psw' ] )
            || ! wp_verify_nonce($_POST['wc-posts-psw' ], 'savepswform' )
        ) {

            return ;

        }

        $items = array_map( 'intval', $_POST['id'] );

        if ( 'deletepsw' === $this->current_action() ) {

            if ( $items ) {
                $ids = array();
                foreach ( $items as $k=>$id ) {
                    if ( ! $id ) continue;
                    $id = (int) $id;
                    $r = $wpdb->update(
                            $wpdb->posts,
                            array('post_password' => ''),
                            array( 'ID' => $id ),
                            array( '%s'),
                            array( '%d' )
                        );
                    if(!is_wp_error($r)&&$r){
                        $ids[] = $id;
                    }
                }

                echo '<div class="updated"><p>' . sprintf(__( 'Password deleted from Post %s.', WPPBM_SLUG ),implode(',', $ids)) . '</p></div>';

            }


        }elseif ( 'check' === $this->current_action() ) {

            if ( $items ) {
                $ids = array();
                foreach ( $items as $id ) {
                    if ( ! $id ) continue;
                    $id = (int) $id;
                    global $WP_Posts_Password_Manager;
                    $password=trim($WP_Posts_Password_Manager->get_option( WPPBM_SLUG.'_general', 'my_password', '' ));

                    $r = $wpdb->update(
                            $wpdb->posts,
                            array('post_password' => $password),
                            array( 'ID' => $id ),
                            array( '%s'),
                            array( '%d' )
                        );
                    if(!is_wp_error($r)&&$r){
                        $ids[] = $id;
                    }
                }

            }
            echo '<div class="updated"><p>' . sprintf(__( 'Password added to %s.', WPPBM_SLUG ),implode(',',$ids)) . '</p></div>';
        }
    }


    public function prepare_items() {
        global $wpdb;
        $current_page 		= $this->get_pagenum();
        /*define 10 items per page by suifengtec*/
        $per_page			= empty( $_REQUEST['posts_per_page'] ) ? 10 : (int) $_REQUEST['posts_per_page'];

		$orderby 			= ( ! empty( $_REQUEST['orderby'] ) ) ? esc_attr( $_REQUEST['orderby'] ) : 'post_date';
		$order 				= ( empty( $_REQUEST['order'] ) || $_REQUEST['order'] == 'asc' ) ? 'DESC' : 'ASC';


        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );


        $this->process_bulk_action();

       	$max = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}posts  WHERE `post_status`='publish' AND `post_type`='post'" );

		$this->items = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}posts WHERE `post_status`='publish' AND `post_type`='post'
			ORDER BY `{$orderby}` {$order} LIMIT %d, %d
		", ( $current_page - 1 ) * $per_page, $per_page ) );

        $this->set_pagination_args( array(
            'total_items' => $max,
            'per_page'    => $per_page,
            'total_pages' => ceil( $max / $per_page )
        ) );
    }

}
