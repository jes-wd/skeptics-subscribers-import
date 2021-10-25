<?php
/*
Plugin Name: Skeptics Subscribers Import
Plugin URI: 
Description: Custom plugin to import users from old Australian Skeptics Inc subscribers DB. Use a CSV export of the "subscribers" table for this.
Version:     0.1
Author:      Jesse Sugden
License:     GPL2 etc
*/

const SSI_DEFAULT_EMAIL_USERNAME = 'testemail';
const SSI_DEFAULT_EMAIL_DOMAIN_NAME = 'skeptics.com.au';

require_once('includes/ssi-functions.php');
require_once('includes/class-ssi-admin.php');

class Skeptics_Subscribers_Import {
    // place a csv file of the exported subscribers table at the path below with this name
    const CSV_PATH = 'csv/subscribers.csv'; // relative to this file
    const SUB_START_DATE = '2021-10-27 00:00:00';
    const SUB_BILLING_INTERVAL = 3;
    const SUB_BILLING_PERIOD = 'month';
    const SUB_CURRENCY = 'AUD';
    const LINE_BREAK = '</br>';

    private static $initiated = false;

    public static function init() {
        if (!self::$initiated) {
            self::$initiated = true;

            // init admin page
            if (is_admin()) {
                $admin_page = Skeptics_Subscribers_Import_Admin::init();
            }

            // Check whether the button has been pressed AND also check the nonce
            // if (isset($_POST['subscriber_import'])) {
            //     self::subscriber_import_action();
            // }
            if (isset($_POST['subscriber_import']) && check_admin_referer('subscriber_import_clicked')) {
                self::subscriber_import_action();
            }
        }
    }

    private static function subscriber_import_action() {
        echo '<div style="margin-left: 200px; margin-top:15px;">';
        // in the csv file, empty fields have 'NULL' instead of being empty. find and replace.
        $find_replace_null_strings = ssi_replace_in_file(plugin_dir_path(__FILE__) . self::CSV_PATH, 'NULL', '');

        if ($find_replace_null_strings['status'] === 'success') {
            $csv_file = @fopen(plugin_dir_path(__FILE__) . self::CSV_PATH, 'r'); // open the file
            $csv_array = ssi_csv_to_array($csv_file);

            foreach ($csv_array as $subscriber) {
                if (empty($subscriber['First'])) {
                    echo 'Invalid data for a user' . self::LINE_BREAK;
                    continue;
                }

                echo 'Working on user: ' . $subscriber['Email'] . self::LINE_BREAK;
                $email_exists = email_exists($subscriber['Email']);

                if ($email_exists) {
                    $user_id = get_user_by('email', $subscriber['Email'])->ID;
                    echo 'User exists. ID = ' . $user_id . self::LINE_BREAK;
                } else {
                    $user_data = ssi_format_user_data_for_wp_insert_user($subscriber);
                    $user_id = wp_insert_user($user_data);
                    echo 'User created. ID = ' . $user_id . self::LINE_BREAK;
                }

                $subscription = wcs_create_subscription(
                    array(
                        'customer_id'      => $user_id,
                        'start_date'       => self::SUB_START_DATE,
                        'billing_interval' => self::SUB_BILLING_INTERVAL,
                        'billing_period'   => self::SUB_BILLING_PERIOD,
                        'created_via'      => 'importer',
                        'customer_note'    => '',
                        'currency'         => self::SUB_CURRENCY,
                    )
                );

                print_r(WCS_Importer::add_product( $subscription, array( 'product_id' => 18 ), 0 ));
                echo self::LINE_BREAK;

                // echo '<pre>';
                // print_r(array(
                //     'customer_id'      => $user_id,
                //     'start_date'       =>  gmdate('Y-m-d H:i:s', strtotime(self::SUB_START_DATE)),
                //     'billing_interval' => self::SUB_BILLING_INTERVAL,
                //     'billing_period'   => self::SUB_BILLING_PERIOD,
                //     'created_via'      => 'importer',
                //     'customer_note'    => '',
                //     'currency'         => self::SUB_CURRENCY,
                // ));
                // echo '</pre>';

                if (is_wp_error($subscription)) {
                    echo '<pre>';
                    print_r($user_data);
                    echo '</pre>';
                    throw new Exception(sprintf(esc_html__('Could not create subscription: User: id: %s', 'wcs-import-export'), $subscription->get_error_message()));
                } else {
                    echo 'Sub created. ID = ' . $subscription->get_id() . self::LINE_BREAK;
                    $subscription->save();
                }
            }

           
            fclose($csv_file);
            
        } else {
            echo '<pre>';
            print_r($find_replace_null_strings);
            echo '</pre>';
        }


        // echo '<pre>';
        // print_r($csv_array);
        // echo '</pre>';
        echo '</div>';
    }
}

$skeptics_subscribers_import = new Skeptics_Subscribers_Import;
// ensure that plugin runs only when wp is initialized
add_action('init', array(&$skeptics_subscribers_import, 'init'));
