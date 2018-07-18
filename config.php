<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define database parameters
if (!defined('DB_HOST')) define("DB_HOST", "127.0.0.1");	// "localhost" is also "localhost"
if (!defined('DB_USER')) define("DB_USER", "root");
if (!defined('DB_PASS')) define("DB_PASS", "root");
if (!defined('DB_NAME')) define("DB_NAME", "project-library");

// Define URLs
if (!defined('ROOT_PATH')) define("ROOT_PATH", "/library-project/login.php");
if (!defined('ROOT_URL')) define("ROOT_URL", "http://localhost/library-project/");

require_once 'load-classes.php';

if ($_SERVER['QUERY_STRING'] == "subject=my-profile&action=logout&id=my-profile") {
    unset($_SESSION['is-logged-in']);
    unset($_SESSION['user-data']);
    session_destroy();
    // Redirect
    header('Location: '.ROOT_URL.'login.php');
    exit;
}

if (!isset($_SESSION['is-logged-in']) && $_SERVER['REQUEST_URI']!= ROOT_PATH) {
    header('Location: '.ROOT_URL.'login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['btn-update-user'])) {
        $_SESSION['user_update_data'] = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }       
}

if (isset($_SESSION['user_update_data'])) {
    $update_data = $_SESSION['user_update_data'];
    
    $user = new User();
    $existing_data = $user->returnUserInfo($update_data['original_user_card_number']);
    //die($update_data['original_user_card_number']);    
    if (array_key_exists('user_card_number', $update_data)) {
        if ($existing_data['user_card_number'] != $update_data['user_card_number']) {
            $user->update($existing_data['user_card_number'], 'user_card_number', $update_data['user_card_number']);
            $existing_data['user_card_number'] = $update_data['user_card_number'];
        }
    }
        
    if ($existing_data['name'] != $update_data['name']) {
        $user->update($existing_data['user_card_number'], 'name', $update_data['name']);
    }
        
    if ($existing_data['email'] != $update_data['email']) {
        $user->update($existing_data['user_card_number'], 'email', $update_data['email']);
    }
        
    if (!empty($update_data['password'])) {
        $user->update($existing_data['user_card_number'], 'password', md5($update_data['password']));
    }
        
    if ($existing_data['phone_number'] != $update_data['phone_number']) {
        $user->update($existing_data['user_card_number'], 'phone_number', $update_data['phone_number']);
    }
        
    if ($existing_data['address'] != $update_data['address']) {
        $user->update($existing_data['user_card_number'], 'address', $update_data['address']);
    }
        
    if (array_key_exists('user_type', $update_data)) {
        $user_id = $user->getUserTypeIdByTypeName($update_data['user_type']);
        if ($existing_data['user_type_id'] != $user_id) {
            $user->update($existing_data['user_card_number'], 'user_type_id', $user_id);
        }
    }
        
    if (array_key_exists('date_registered', $update_data)) {
        if ($existing_data['date_registered'] != $update_data['date_registered']) {
            $user->update($existing_data['user_card_number'], 'date_registered', $update_data['date_registered']);
        }
    }
        
    (new Message('error', 'User Profile Updated!', "The new information about the user profile has been successfully updated.<br /><br />Thank you!"))->display();

    unset($_SESSION['user_update_data']);
    
}

?>