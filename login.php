<?php
require_once 'config.php';
require_once 'load-classes.php';

if (isset($_SESSION['is-logged-in'])) {
    header('Location: '.ROOT_URL.'dashboard.php');
}

$user = new User();
$error = null;
// what to do if login button is clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['btn-login'])) {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $user_name = $post['login-username'];
        $password = $post['login-password'];
        
        $login_result = $user->login($user_name, $password);
        
        if ($login_result) {
            
            $_SESSION['is-logged-in'] = true;
            $_SESSION['user-data'] = array(
                "user_card_number"    =>    $login_result['user_card_number'],
                "name"                =>    $login_result['name'],
                "email"               =>    $login_result['email'],
                "password"            =>    $login_result['password'],
                "phone_number"        =>    $login_result['phone_number'],
                "address"             =>    $login_result['address'],
                "user_type_id"        =>    $login_result['user_type_id'],
                "user_type_name"      =>    ucwords($user->getUserTypeName($login_result['user_type_id'])),
                "booking_priority"    =>    $user->getUserTypeInfo($login_result['user_type_id'])['booking_priority'],
                "maximum_booking_duration"  => $user->getUserTypeInfo($login_result['user_type_id'])['maximum_booking_duration'],
                "date_registered"     =>    $login_result['date_registered']
            );

            header('Location: '.ROOT_URL.'dashboard.php');
        } else {
            $login_error = $user->getError();
            (new Message('error', 'Login Failed', 'The <strong>login information</strong> you provided is <strong>not correct</strong>.<br /> Make sure both User ID, i.e. Innopolis Email ID ending with @innopolis.ru, and the password you signed up with are correctly filled in.<br><br>If you have <strong>recently submitted an application</strong>, your application might still be waiting approval or has been declined. Please consult with one of the librarians to know your application status in further detail.<br><br>In the unlikely case, it may be that <strong>you have been removed</strong> from the library management system for some reason. Again, consulting a librarian is advised.'))->display();
        }
    }

    // what to do if register button is clicked
    if (isset($_POST['btn-register'])) {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $name = $post['reg-name'];
        $email = $post['reg-email'];
        $password = $post['reg-password'];
        $phone = $post['reg-phone'];
        $address = $post['reg-address'];
        $type = $post['user-type'];

        $user->apply($name, $email, $password, $phone, $address, $type);
        $submit_error = $user->getError();

        if ($submit_error) {
            $error = new Message('error', 'Application Submission Failed! :(', "Submission has failed. <br/>".$submit_error);
        } else {
            (new Message('error', 'Application Submitted! :)', "Your application for user account has been submitted. You'll be able to log in if and when your application has been successful and an user account is created for you."))->display();
            // add to the log
            $db = new DatabaseConnection();
            $user = new User();
            $db->addLog("$name requested a '". ucwords($user->getUserTypeName($type)) . "' user account.");
        }
    }
}
?>

<!DOCTYPE html>
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6 lt8"> <![endif]-->
<!--[if IE 7 ]>    <html lang="en" class="no-js ie7 lt8"> <![endif]-->
<!--[if IE 8 ]>    <html lang="en" class="no-js ie8 lt8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="UTF-8" />
        <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">  -->
        <title>Login | Online Library Management System</title>
        <link rel="stylesheet" type="text/css" href="css/form.css" />
        <link href="css/form.css" rel="stylesheet">
                <!-- Custom fonts for this template -->
        <link href="vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

                <!-- Font Awesome -->
        <link href="https://use.fontawesome.com/releases/v5.0.7/css/all.css" rel="stylesheet">
        
        <style>
        select {
            height: 30px;
            width: 100%;
            margin: 8px 0;
        }

        </style>

    </head>
    <body>
        <div class="container">
            <header style = "background-color: #FFCC99">
                <a href = "https://university.innopolis.ru/">
                <img src="For Project/inno.png" /> </a>

            </header>
            <br/>
        <section>
                <div id="container_demo" >
                    <!-- hidden anchor to stop jump http://www.css3create.com/Astuce-Empecher-le-scroll-avec-l-utilisation-de-target#wrap4  -->
                    <a class="hiddenanchor" id="toregister"></a>
                    <a class="hiddenanchor" id="tologin"></a>
                    <div id="wrapper" class="w3-middle">
                        <div id="login" class="animate form">
                            <form  action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" autocomplete="on">
                                <h1>Log in</h1>
                                <p>
                                    <label for="username" class="uname" > Your Innopolis ID </label>
                                    <input id="username" name="login-username" required="required" type="text" placeholder="name@innopolis.ru"/>
                                </p>
                                <p>
                                    <label for="password" class="youpasswd"> Your password </label>
                                    <input id="password" name="login-password" required="required" type="password" placeholder="eg: xxxxxxxxx" />
                                </p>
                                <p class="keeplogin">
                                    <input type="checkbox" name="loginkeeping" id="loginkeeping" value="loginkeeping" />
                                    <label for="loginkeeping">Keep me logged in</label>

                                </p>
                                <p class="signin button">
                                    <input type="submit" name="btn-login" value="Login"/>
                                </p>
                                <p class="change_link">
                                    Not a member yet?
                                    <a href="#toregister" class="to_register" onclick="document.title = 'Apply for an account | Online Library Management System'">Apply for a user account</a>.
                                </p>
                            </form>
                        </div>


                        <div id="register" class="animate form w3-middle">
                        <form  action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" autocomplete="on">
                            <h1> User Application </h1>
                            <p><strong>Note</strong>: Please enter correct information. Your information will be verified by one of our librarians and you'll be notified via email if successful.</p>
                            <p>
                                <label for="usernamesignup" class="uname" >Name</label>
                                <input id="usernamesignup" name="reg-name" required="required" type="text" placeholder="Full name" />
                            </p>
                            <p>
                                <label for="emailsignup" class="youmail"  >Innopolis ID</label>
                                <input id="emailsignup" name="reg-email" required="required" type="email" placeholder="name@innopolis.ru"/>
                            </p>
                            <p>
                                <label for="passwordsignup" class="youpasswd" >Phone </label>
                                <input id="usersignup" name="reg-phone" required="required" type="text" placeholder="+7 (123) 456-7890 "/>
                            </p>
                            <p>
                                <label for="passwordsignup" class="youpasswd" >Mailing Address </label>
                                <input id="usersignup" name="reg-address" required="required" type="text" placeholder="Current Address"/>
                            </p>
                            
                            <p>
                                <label for="passwordsignup" class="youpasswd" >Your Password </label>
                                <input id="usernamesignup" name="passwordsignup" required="required" type="password" placeholder="Password"/>
                            </p>
                            <p>
                                <label for="passwordsignup_confirm" class="youpasswd" >Confirm Your Password </label>
                                <input id="passwordsignup_confirm" name="reg-password" required="required" type="password" placeholder="Re-enter password"/>
                            </p>
                            <p>
                                <label for="passwordsignup" class="youpasswd" >What type of account are you applying for?</label><br />
                                <select name="user-type" onchange="if (this.selectedIndex) checkFacultySelect();" required="required">
                                    <option value="" selected disabled hidden>Choose user type.</option>
                                    <?php
                                        $user = new User;
                                        $user_type=$user->individualTypes();
                                        
                                        foreach($user_type as $type) {
                                            $type_name = ucwords($type['type_name']);
                                            if ( $type['parent_type_id'] != 0) {
                                                echo '<option value="' . $type['type_id']. '">' . $type_name . '</option>';
                                            }
                                        }
                                    ?>
                                    </select>
                            </p>
                            <hr />
                            <p>By submitting this application, you agree to our <a href="#">terms and conditions</a>:</p>
                            <p class="signin button">
                                <input type="submit" name='btn-register' value="Submit Form"/>
                            </p>
                            <p class="change_link">
                                Already a member ?
                                <a href="#tologin" class="to_register" onclick="document.title = 'Login | Online Library Management System'"> Log in </a>
                            </p>
                        </form>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        
    </body>
</html>

<?php
if ($error) {
    $error->display();
}
?>