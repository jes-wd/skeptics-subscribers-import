<?php 

function ssi_format_user_data_for_wp_insert_user(array $subscriber) {
    $preformatted_username = $subscriber['First'] . $subscriber['Last'] . $subscriber['ID'];
    $username = ssi_format_to_class_friendly_string($preformatted_username);
    // use the email if there is one. if not, use a fake email
    // the '4' below represents the minimum chars that can make up a valid email
    $email = strlen($subscriber['Email']) > 4 ? $subscriber['Email'] : SSI_DEFAULT_EMAIL_USERNAME . $subscriber['ID'] . '@' . SSI_DEFAULT_EMAIL_DOMAIN_NAME;

    return array(
        'user_login' => $username,
        'display_name' => $subscriber['First'] . ' ' . $subscriber['Last'],
        'first_name' => $subscriber['First'],
        'last_name' => $subscriber['Last'],
        'user_email' => $email,
        'user_pass' => $subscriber['First']
    );
}

function ssi_csv_to_array($csv_file) {
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

function ssi_format_to_class_friendly_string(string $string) {
    return preg_replace('/\W+/', '', strtolower(strip_tags($string)));
}

function ssi_replace_in_file(string $file_path, string $old_text, string $new_text) {
    $result = array('status' => 'error', 'message' => '');
    if (file_exists($file_path) === TRUE) {

        if (is_writeable($file_path)) {
            try {
                $file_content = file_get_contents($file_path);
                $file_content = str_replace($old_text, $new_text, $file_content);

                if (file_put_contents($file_path, $file_content) > 0) {
                    $result["status"] = 'success';
                } else {
                    $result["message"] = 'Error while writing file';
                }
            } catch (Exception $e) {
                $result["message"] = 'Error : ' . $e;
            }
        } else {
            $result["message"] = 'File ' . $file_path . ' is not writable !';
        }
    } else {
        $result["message"] = 'File ' . $file_path . ' does not exist !';
    }
    return $result;
}