<?php
/*
Plugin Name: Skeptics Subscribers Import
Plugin URI: 
Description: Custom plugin to import users from old Australian Skeptics Inc subscribers DB. Use a CSV export of the "subscribers" table for this.
Version:     0.1
Author:      Jesse Sugden
License:     GPL2 etc
*/

class Skeptics_Subscribers_Import {
    const CSV_PATH = 'csv/subscribers.csv'; // relative to this file

    private static $initiated = false;

    public static function init() {
        if (!self::$initiated) {
            add_action('admin_menu', array('Skeptics_Subscribers_Import', 'subscriber_import_menu'));
        }
    }

    public static function subscriber_import_menu() {
        add_submenu_page(
            'tools.php',
            'Import Subscribers',
            'Import Subscribers',
            'manage_options',
            'skeptics-subscribers-import',
            array('Skeptics_Subscribers_Import', 'subscriber_import_admin_page')
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

        // Check whether the button has been pressed AND also check the nonce
        if (isset($_POST['subscriber_import']) && check_admin_referer('subscriber_import_clicked')) {
            self::subscriber_import_action($_FILES['subscribers_file']);
        }

        echo '<form action="tools.php?page=skeptics-subscribers-import" method="post" enctype="multipart/form-data">';
        wp_nonce_field('subscriber_import_clicked'); // this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
        echo '<input type="hidden" value="true" name="subscriber_import" />';
        submit_button('Import');
        echo '</form>';
        echo '</div>';
    }

    private static function subscriber_import_action($file) {
        $have_nulled_strings = self::replace_in_file(plugin_dir_path(__FILE__) . self::CSV_PATH, 'NULL', '');

        if ($have_nulled_strings === 'success') {
            $csv_file = @fopen(plugin_dir_path(__FILE__) . self::CSV_PATH, 'r'); // open the file
            $csv_array = self::csv_to_array($csv_file);
    
            foreach ($csv_array as $subscriber) {
                $user_data = self::format_user_data_for_wp_insert_user($subscriber);
                // $new_user_id = wp_insert_user($user_data);
            }
        }
        else {
            echo 'error';
        }
        

        // echo '<pre>';
        // print_r($csv_array);
        // echo '</pre>';
    }

    private static function format_user_data_for_wp_insert_user($subscriber) {
        // $subscriber = self::handle_null_fields($subscriber);

        $preformatted_username = $subscriber['First'] . $subscriber['Last'] . '-' . $subscriber['ID'];
        $username = self::format_to_class_friendly_string($preformatted_username);


        return array(
            'user_login' => $subscriber['First'],
            'display_name' => $subscriber['First'] . ' ' . $subscriber['Last'],
            'first_name' => $subscriber['First'],
            'last_name' => $subscriber['Last'],
            'user_email' => $subscriber['First'],
            'user_pass' => $subscriber['First'],
        );
    }

    private static function csv_to_array($csv_file) {
        $array = $fields = array();
        $i = 0;

        if ($csv_file) {
            while (($row = fgetcsv($csv_file, 4096)) !== false) { // create associative array from csv
                if (empty($fields)) {
                    $fields = $row;
                    continue;
                }
                foreach ($row as $k => $value) {
                    $array[$i][$fields[$k]] = $value;
                }
                $i++;
            }
            if (!feof($csv_file)) {
                echo "Error: unexpected fgets() fail\n";
            }

            return $array;
        } else {
            return "Error: The file cannot be found. Add a file called 'subscribers.csv' to the 'csv' folder of this plugin.";
        }
    }

    private static function format_to_class_friendly_string($string) {
        return preg_replace('/\W+/', '', strtolower(strip_tags($string)));
    }

    private static function replace_in_file($FilePath, $OldText, $NewText) {
        $Result = array('status' => 'error', 'message' => '');
        if (file_exists($FilePath) === TRUE) {
            if (is_writeable($FilePath)) {
                try {
                    $FileContent = file_get_contents($FilePath);
                    $FileContent = str_replace($OldText, $NewText, $FileContent);
                    if (file_put_contents($FilePath, $FileContent) > 0) {
                        $Result["status"] = 'success';
                    } else {
                        $Result["message"] = 'Error while writing file';
                    }
                } catch (Exception $e) {
                    $Result["message"] = 'Error : ' . $e;
                }
            } else {
                $Result["message"] = 'File ' . $FilePath . ' is not writable !';
            }
        } else {
            $Result["message"] = 'File ' . $FilePath . ' does not exist !';
        }
        return $Result;
    }


    // private static function handle_null_fields($subscriber_array) {
    //     echo sizeof($subscriber_array);
    //     for ($i = 0; $i < sizeof($subscriber_array); $i++) {
    //         if ($subscriber_array[$i] === 'NULL') $subscriber_array[$i] = '';
    //         print_r($subscriber_array[$i]);
    //     }
    //     // echo '<pre>';
    //     // print_r($subscriber_array);
    //     // echo '</pre>';
    //     return $subscriber_array;
    // }
}

$skeptics_subscribers_import = new Skeptics_Subscribers_Import;
$skeptics_subscribers_import->init();
