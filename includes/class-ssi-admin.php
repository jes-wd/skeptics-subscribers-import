<?php 

class Skeptics_Subscribers_Import_Admin {
    private static $initiated = false;

    public static function init() {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('admin_menu', array('Skeptics_Subscribers_Import_Admin', 'subscriber_import_menu'));
        }
    }

    public static function subscriber_import_menu() {
        add_submenu_page(
            'tools.php',
            'Import Subscribers',
            'Import Subscribers',
            'manage_options',
            'skeptics-subscribers-import',
            array('Skeptics_Subscribers_Import_Admin', 'subscriber_import_admin_page')
        );
    }

    public static function subscriber_import_admin_page() {
        // This function creates the output for the admin page.
        // It also checks the value of the $_POST variable to see whether
        // there has been a form submission. 

        // The check_admin_referer is a WordPress function that does some security
        // checking and is recommended good practice.

        // General check for user permissions.
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient privileges to access this page.'));
        }

        // Start building the page
        echo '<div class="wrap">';
        echo '<h2>Skeptics Subscribers Import</h2>';

        echo '<form action="tools.php?page=skeptics-subscribers-import" method="post" enctype="multipart/form-data">';
        wp_nonce_field('subscriber_import_clicked'); // this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
        echo '<input type="hidden" value="true" name="subscriber_import" />';
        submit_button('Import');
        echo '</form>';
        echo '</div>';
    }
}