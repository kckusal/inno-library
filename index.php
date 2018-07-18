<?php
require_once 'config.php';

if (isset($_SESSION['is-logged-in'])) {
    header('Location: '.ROOT_URL.'dashboard.php');
} else {
    header('Location: '.ROOT_URL.'login.php');
}
?>