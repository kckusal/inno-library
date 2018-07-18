<?php
require_once 'config.php';
require_once 'load-classes.php';

parse_str($_SERVER['QUERY_STRING'], $query_arr);

if (!empty($query_arr) && isset($query_arr['destination']) && $query_arr['destination']=="header") {
    $user = new User();
    
    if ($query_arr['subject'] == "my-profile") {
        switch ($query_arr['action']) {
            case "view":
                $user->viewModal($_SESSION['user-data'], false, $_SESSION['user-data']);
                break;
            case "edit":
                $user->viewModal($_SESSION['user-data'], true, $_SESSION['user-data']);
                break;
            default:
                break;
        }
    } else if ($query_arr['subject'] == "user-profile") {
        switch ($query_arr['action']) {
            case "view":
                $user->viewModal($user->returnUserInfo($query_arr['id']), false, $_SESSION['user-data']);
                break;
            case "edit":
                $user->viewModal($user->returnUserInfo($query_arr['id']), true, $_SESSION['user-data']);
                break;
        }
    }
}
?>

<!-- Navbar (sit on top) -->
<div class="w3-top w3-white">
    <div class="w3-bar w3-padding w3-card">
        <a class="w3-bar-item w3-button w3-hover-white" href="<?php echo ROOT_URL.'dashboard.php'; ?>" title="Go to Dashboard."><img src="For Project/inno.png" alt="Innopolis University Logo" /></a>
        
        <!-- Right-sided navbar links. Hide them on small screens -->
        <div class="w3-display-middle w3-hide-small w3-hide-medium w3-text-red"><h3><a href="<?php echo ROOT_URL.'dashboard.php'; ?>" style="text-decoration: none;">Library Management System</h3></a></div>
        <div class="w3-display-right w3-padding w3-hide-small">
            <div class="w3-left w3-show-inline-block" style="margin-top:-5px;"><a href="<?php echo $_SERVER['PHP_SELF']."?destination=header&subject=my-profile&action=view&id=my-profile"; ?>" title="Click here to see your profile details."><img src="img/login_avatar.png" alt="Login Avatar" width="55px" style="border-radius: 50%; margin-right: 8px;" /></a></div>
            <div class="w3-show-inline-block w3-left-align">
            <span class="w3-right"><?php echo $_SESSION['user-data']['email'] . '<strong><sup style="font-family: Arial; font-size: 13px;">  ' . $_SESSION['user-data']['user_type_name'] . '</sup></strong>'; ?></span><br/>
            <span class="w3-left-align w3-text-red"><a href="<?php echo $_SERVER['PHP_SELF']."?destination=header&subject=my-profile&action=edit&id=my-profile"; ?>" title="Click here to edit your profile.">Edit Profile</a> | <a href="<?php echo $_SERVER['PHP_SELF']."?subject=my-profile&action=logout&id=my-profile"; ?>" class="to_register">Log Out</a></span>
            </div>
        </div>
        
        <!-- Show these instead in small screens -->
        <div class="w3-hide-medium w3-hide-large">
            <div class="w3-show-inline-block w3-text-red"><h5><a href="<?php echo ROOT_URL.'dashboard.php'; ?>"><i class="fas fa-home"></i>&nbsp;Dashboard</a></h5></div>
            <div class="w3-align-left w3-text-red">
                <a href="<?php echo $_SERVER['PHP_SELF']."?destination=header&subject=my-profile&action=edit&id=my-profile"; ?>" title="Click here to edit your profile.">Edit Profile</a> | <a href="<?php echo $_SERVER['PHP_SELF']."?subject=my-profile&action=logout&id=my-profile"; ?>" class="to_register">Log Out</a>
            </div>
            
        </div>
    </div>
</div>

<div class="w3-hidden" style="height: 65px;"></div>
<div class="container">
        <!-- Navigation 
        <div style="height: 70px;"></div>
        <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav" style="z-index: 998; background: white; border: black 2px solid; padding: 2px; margin: 0; height: 70px;">
            <div class="container">
                <a class="navbar-brand js-scroll-trigger" href="#page-top"> <img src="For Project/inno.png" /img>  </h2> </a>
                <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <a  class="nav-link js-scroll-trigger" href="dashboard.php"><center><h5><strong>LIBRARY MANAGEMENT</strong></h5></a></center>
                    <ul class="navbar-nav ml-auto">

                <?php if (isset($_SESSION['is-logged-in'])) : ?>
                        <li class="nav-item">
                                <a href="<?php echo $_SERVER['PHP_SELF']."?destination=header&subject=my-profile&action=view&id=my-profile"; ?>" title="Click here to see your profile details."><img src="img/login_avatar.png" alt="Login Avatar" width="44px" style="border-radius: 50%; margin-right: 8px;" /></a>
                                <div style="float:right; width: 180px; text-align:left;">
                                    <span style="width:320px;  display: inline-block; overflow: visible;"><?php echo $_SESSION['user-data']['email'] . '<strong><sup style="font-family: Arial; font-size: 13px;">  ' . $_SESSION['user-data']['user_type_name'] . '</sup></strong>'; ?></span><br/>
                                    <a href="<?php echo $_SERVER['PHP_SELF']."?destination=header&subject=my-profile&action=edit&id=my-profile"; ?>" title="Click here to edit your profile.">Edit Profile</a> | <a href="<?php echo $_SERVER['PHP_SELF']."?subject=my-profile&action=logout&id=my-profile"; ?>" class="to_register">Log Out</a>
                                </div>
                        </li>

                <?php else : ?>                    
                    <li class="collapse navbar-collapse" id="navbarResponsive" style="float: right; margin: 10px;">
                        <strong>You are not logged in. <a href="login.php">Go to login page</a>.</strong>
                    </li>
                <?php endif; ?>

                    </ul>
                </div>
            </div>
        </nav>
-->
