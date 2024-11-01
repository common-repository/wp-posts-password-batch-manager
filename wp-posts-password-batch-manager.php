<?php
/*
Plugin Name: WP Posts Password Batch Manager
Plugin URI: http://suoling.net/wp-posts-password-batch-manager
Description: Batch managing your Wordpress posts password with this plugin.
Author: suifengtec
Version: 1.1
Author URI: http://coolwp.com/
*/

defined('ABSPATH') or exit;


if(!class_exists('WP_Posts_Password_Manager')):
    final class WP_Posts_Password_Manager{

        protected static $_instance = null;

        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __clone() {}
        public function __wakeup() {}

        public function __construct() {

            defined('WPPBM_SLUG') or  define('WPPBM_SLUG','wppbm');
            defined('WPPBM_SELF') or  define('WPPBM_SELF',__FILE__);
            defined('WPPBM_SELF_DIR') or  define('WPPBM_SELF_DIR',dirname(__FILE__));
            add_action( 'plugins_loaded', array($this,'plugins_loaded'),11 );

        }


        public function plugins_loaded(){

            load_plugin_textdomain(WPPBM_SLUG, false, dirname( plugin_basename( __FILE__) ) . '/languages/'  );

            if(!function_exists('wp_get_current_user')){

               require_once(ABSPATH . WPINC . '/pluggable.php');
            }

            add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array(__CLASS__,'add_extra_links'), 10, 2 );

            if(is_admin()&&current_user_can('manage_options' )) {

                require_once(WPPBM_SELF_DIR.'/classes/class-wppbm-posts-table.php');
                require_once(WPPBM_SELF_DIR.'/includes/settings-api.php');
            }

            add_action( 'admin_menu',  array(__CLASS__,'admin_menu' ));

            add_action('admin_init',array(__CLASS__,'handle_general_bulk_actions'),11);
        }

        public static function wppbm_posts_table(){

            $messages = array();
            $WPPBM_Posts_Table = new WPPBM_Posts_Table();
            $WPPBM_Posts_Table->prepare_items();

            ?>
            <div class="wrap">
                <h2><?php _e( 'Posts'); ?></h2>
                <form id="posts-psw" method="post">
                    <?php  if ( $messages ) : ?>

                            <div class="updated">
                            <?php foreach ( $messages as $message ) { ?>
                                <p><?php echo  $message;?> </p>
                            <?php } ?>
                            </div>

                        <?php

                        endif;
                        wp_nonce_field( 'savepswform', 'wc-posts-psw' );
                    ?>
                    <?php $WPPBM_Posts_Table->display(); ?>
                </form>
            </div>
            <?php

        }


        public static function admin_menu(){

            $setting_api = new WPPBM_Options();

            add_menu_page(__( 'Posts password settings', WPPBM_SLUG ), __( 'My PSW', WPPBM_SLUG ), 'manage_options', WPPBM_SLUG,array($setting_api, 'plugin_page')  );

            add_submenu_page( WPPBM_SLUG, __( 'Posts list', WPPBM_SLUG ),  __( 'Posts list', WPPBM_SLUG ), 'manage_options', WPPBM_SLUG.'-posts',  array(__CLASS__,'wppbm_posts_table'  ));

        }

        public static function handle_general_bulk_actions(){


            $_POST = stripslashes_deep($_POST);

            if(!isset($_POST['option_page'])||!('wppbm_general'==$_POST['option_page'])){
            return false;
            }

            $add = (isset($_POST["wppbm_general"]['add_to_all'])&&('on'===$_POST["wppbm_general"]['add_to_all']))?true:false;
            $delete = (isset($_POST["wppbm_general"]['delete_all_psw'])&&'on'===$_POST["wppbm_general"]['delete_all_psw'])?true:false;

            if(!$add&&!$delete){
                return ;
            }

            global $wpdb;
            if($delete){
                   $args = array(
                        'post_type'=>'post',
                        'post_status'=>'publish',
                        'numberposts' => -1,
                        'post_password'=>$password
                    );
                    $posts = get_posts($args);
                    if($posts){
                        foreach($posts as $post){
                            $r = $wpdb->update(
                                $wpdb->posts,
                                array( 'post_password' => '' ),
                                array( 'ID' => $post->ID),
                                array( '%s' ),
                                array( '%d' )
                            );
                        }
                    }

                    self::delete_option( WPPBM_SLUG.'_general', 'delete_all_psw');

            }elseif($add){

                $old_psw = trim(self::get_option( WPPBM_SLUG.'_general', 'my_password', '' ));
                $new_psw = (isset($_POST["wppbm_general"]['my_password'])&&!empty($_POST["wppbm_general"]['my_password']))?trim($_POST["wppbm_general"]['my_password']):false;
                if($old_psw!=$new_psw){
                    self::update_option(WPPBM_SLUG.'_general','my_password',$new_psw);
                    $password=$new_psw;
                }else{
                    $password=$old_psw;
                }

                if(!empty($password)){
                       $args=array(
                            'post_type'=>'post',
                            'post_status'=>'publish',
                            'numberposts' => -1,
                            'post_password'=>''
                        );
                        $posts=get_posts($args);
                        if($posts){
                            foreach($posts as $post){
                                    $r = $wpdb->update(
                                            $wpdb->posts,
                                            array( 'post_password' => $password ),
                                            array( 'ID' => $post->ID),
                                            array( '%s' ),
                                            array( '%d' )
                                        );
                            }
                        }
                }

            }

            wp_redirect(admin_url('admin.php?page='.WPPBM_SLUG.'-posts'));
            exit;
        }


        public static function add_extra_links( $links, $file ) {

            $start_link = '<a href="' . admin_url( 'options-general.php?page='.WPPBM_SLUG) . '">' . esc_html__( 'Start', WPPBM_SLUG ) . '</a>';
            array_unshift( $links, $start_link );
            return $links;
        }

        public static function  update_option($section,$option,$value){

            $options = get_option( $section );
            $options[$option] = $value;
            update_option($section,$options);


        }

        public static function get_option( $section, $option, $default = '' ) {

            $options = get_option( $section );
            if ( isset( $options[$option] ) ) {
                return $options[$option];
            }
            return $default;
        }
        public static function delete_option(  $section,$option ) {

            $options = get_option( $section );
            if ( isset( $options[$option] ) ) {
                unset($options[$option]);
            }
            update_option($section,$options);

        }

    } /*//CLASS*/
    $GLOBALS['WP_Posts_Password_Manager'] = WP_Posts_Password_Manager::instance();
endif;
