<?php

defined('ABSPATH') or exit;

if(!class_exists('WeDevs_Settings_API')){
    require_once dirname( WPPBM_SELF ) . '/classes/class.settings-api.php';
}

if ( !class_exists('WPPBM_Options' ) ):
class WPPBM_Options {

    public $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init'),12 );

    }

    public function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    public function admin_menu() {
        add_menu_page(__( 'Posts password settings', WPPBM_SLUG ), __( 'My PSW', WPPBM_SLUG ), 'manage_options', WPPBM_SLUG,array($this, 'plugin_page')  );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => WPPBM_SLUG.'_general',
                'title' => __( 'General', WPPBM_SLUG)
            ),
/*          array(
                'id' => WPPBM_SLUG.'_posts',
                'title' => __( 'Bulk Action',WPPBM_SLUG )
            ),*/
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            WPPBM_SLUG.'_general' => array(

                array(
                    'name' => 'my_password',
                    'label' => __( 'My Password', WPPBM_SLUG),
                    'desc' => __( 'Setting your password here', WPPBM_SLUG ),
                    'type' => 'text',
                    'default' => '',
                ),


                array(
                    'name' => 'add_to_all',
                    'label' => __( 'Add your password to all posts', WPPBM_SLUG),
                    'desc' => __( 'bulk add your password to  all posts?', WPPBM_SLUG ),
                   'type' => 'checkbox'
                ),
                array(
                    'name' => 'delete_all_psw',
                    'label' => __( 'Delete your password from all posts', WPPBM_SLUG),
                    'desc' => __( 'bulk delete all posts password the last added?', WPPBM_SLUG ),
                   'type' => 'checkbox'
                ),


                array(
                    'name' => 'now_choose_posts',
                    'label' => __( 'Next step', WPPBM_SLUG),
                    'desc' => __( 'Navigating to the subemenu "Posts list" to select posts you want to add/remove password, or navigating to the another tab to bulk add/remove password to posts.', WPPBM_SLUG ),
                    'type' => 'html',
                ),

            ),/*general*/


        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}
endif;

/*$settings = new WPPBM_Options();*/