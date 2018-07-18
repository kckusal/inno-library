<?php
require_once 'config.php';
require_once 'load-classes.php';

$db = new DatabaseConnection();

$query_string = $_SERVER['QUERY_STRING'];
parse_str($query_string, $query_arr);

/* Check queries and redirect them to execution. */
if (!empty($query_arr) && $query_arr['destination']=="dashboard") {
    $_SESSION['execute-query'] = $query_arr;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;    
}

// There's a form element to manage user access privileges.
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['btn-set-user-privileges'])) {
        $user_type_id = $_POST['user-type-id'];
        $add_doc = isset($_POST['add-doc']) ? 1:0;
        $modify_doc = isset($_POST['modify-doc']) ? 1:0;
        $remove_doc = isset($_POST['remove-doc']) ? 1:0;
        $add_user = isset($_POST['add-user']) ? 1:0;
        $modify_user = isset($_POST['modify-user']) ? 1:0;
        $remove_user = isset($_POST['remove-user']) ? 1:0;
        //die($user_type_id . $add_doc . $modify_doc . $remove_doc . $add_user . $modify_user . $remove_user);
        $user = new User();
        $user->setPrivilegesByType($user_type_id, $add_doc, $modify_doc, $remove_doc, $add_user, $modify_user, $remove_user);
        $_SESSION['msg'] = "Successful! Privileges to access and modify document and user information has been updated!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;    
    }
    
    // Manages librarian access
    if (isset($_POST['submit-librarian-access'])) {
        $librarian_id = $_POST['librarian-id'];
        $add_doc = isset($_POST['add-doc-la']) ? 1:0;
        $modify_doc = isset($_POST['modify-doc-la']) ? 1:0;
        $remove_doc = isset($_POST['remove-doc-la']) ? 1:0;
        $add_user = isset($_POST['add-user-la']) ? 1:0;
        $modify_user = isset($_POST['modify-user-la']) ? 1:0;
        $remove_user = isset($_POST['remove-user-la']) ? 1:0;
        
        $user = new User();
        $user->setLibrarianPrivilege($librarian_id, $add_doc, $modify_doc, $remove_doc, $add_user, $modify_user, $remove_user);
        $_SESSION['msg'] = "Successful! The selected librarian's privileges to access and modify document and user information has been updated!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* Handle all queries here. */
if (isset($_SESSION['execute-query'])) {
    $user_profile = array();
    $msg="";
    if (isset($_SESSION['execute-query']['subject'])) {
        if ($_SESSION['execute-query']['subject']=="user-request") {
            $user = new User();
            // check privilege
            if ($user->getLibrarianPrivilege($_SESSION['user-data']['user_card_number'])['add_user']==0) {
                $msg = 'You are <strong>not</strong> authorized to approve or modify new user requests. Kindly contact a librarian or the admin to see if it possible to get the information you are looking for.';
                
            } else {
                switch ($_SESSION['execute-query']['action']) {
                    case ('approve'):
                        $user->approve($_SESSION['execute-query']['id']);
                        $msg = "The user account has been successfully <strong>approved</strong> and <strong>activated</strong>.<br/><br/> After closing this message, you will be given an option to update any information about that user. Please use that feature to your advantage to make sure all relevant user details are correctly filled.";
                        $user->viewModal($user->returnUserInfo($_SESSION['execute-query']['id']), true, $_SESSION['user-data']);
                        
                        $db->addLog($_SESSION['user-data']['name'] . " approved the user request with ID: " . $_SESSION['execute-query']['id']);
                        break;
                    case('decline'):
                        $user->remove($_SESSION['execute-query']['id']);
                        $msg = "Successful! The user account request has been rejected and the record removed from database.";
                        
                        $db->addLog($_SESSION['user-data']['name'] . " declined a new user account request.");
                        break;
                    case('hold'):
                        $msg = "The request will be kept on <strong>hold</strong>, i.e. the request will be put on the end of other requests. You can later review it and take actions.";
                        break;
                }
            }
        } else if ($_SESSION['execute-query']['subject']=="booking-request") {
            $booking = new Booking();
            
            switch ($_SESSION['execute-query']['action']) {
                case "approve":
                    $booking->approveRequest($_SESSION['execute-query']['id']);
                    $msg = "Successful! The booking request has been approved.";
                    $db->addLog('Approved booking request for Id = ' . $_SESSION['execute-query']['id']);
                    break;
                case "decline":
                    $booking->declineRequest($_SESSION['execute-query']['id']);
                    $msg = "<strong>Declined</strong>! The request for booking has been declined.";
                    $db->addLog('Declined booking request for Id = ' . $_SESSION['execute-query']['id']);
                    break;
                case "hold":
                    $msg = "Done! The request has been kept on <strong>hold</strong>, i.e. it will be dealt with later.";
                    break;
                case "clear-overdue":
                    $booking->clearOverdue($_SESSION['execute-query']['id']);
                    $msg = "Successful! The overdue status of this record has been cleared.<br/><br/>The document is now available and all the fines and charges have been cleared for this document and the user.";
                    $db->addLog('Cleared overdue status of the booking record Id = ' . $_SESSION['execute-query']['id']);
                    break;
            }
            
        } else if ($_SESSION['execute-query']['subject']=="return-doc") {
            $booking = new Booking();
            
            switch ($_SESSION['execute-query']['action']) {
                case "say-return":
                    $booking->sayBookedDocReturned($_SESSION['execute-query']['id']);
                    $msg = "Your document return status has been recorded. One of the librarians should check and <strong>confirm</strong> the return shortly.";
                    $db->addLog('Marked booked document with booking ID = ' . $_SESSION['execute-query']['id'] . ' as returned. Awaiting confirmation.');
                    break;
                case "confirm-return":
                    $booking->confirmBookedDocReturned($_SESSION['execute-query']['id']);
                    $msg = "You have successfully <strong>confirmed</strong> the return of this document.";
                    $db->addLog('Confirmed return of booked document with booking ID = ' . $_SESSION['execute-query']['id']);
                    break;
            }
        }
        unset($_SESSION['execute-query']);
        $_SESSION['msg'] = $msg;
    }
// no need to load document further, AJAX will update parts in existing documents
//exit();
}

if (isset($_SESSION['msg'])) {
    (new Message('error', 'Action Results', $_SESSION['msg']))->display();
    unset($_SESSION['msg']);
}


// doc object to use Document class's procedures
$doc = new Document();

// get all the new user requests
$user = new User();
$new_user_requests = $user->getPendingUserRequests();

// get all new booking requests, except for outstanding ones
$booking = new Booking();
$new_booking_requests = $booking->listPendingRecords();

// get outstanding pending requests
$outstanding_pending_requests = $booking->listOutstandingPendingRecords();

// get my booking requests
$my_booking_requests = $booking->myBookingRecords($_SESSION['user-data']['user_card_number'], "PENDING");

// get my booking history
$my_booking_history_records = $booking->myBookingRecords($_SESSION['user-data']['user_card_number'], "BOOKED", "RETURNED", "RETURNED-UNCONFIRMED");

// unconfirmed returns of the booked documents
$unconfirmed_return_records = $booking->returnUnconfirmedReturns();

// overdue booking records
$overdue_booking_records = $booking->returnOverdueBookings();

// checks if the user is LIBRARIAN or not.
// The user with type_id=1 is ALWAYS a Librarian.
$user_is_librarian = ($_SESSION['user-data']['user_type_id'] == 1 or $_SESSION['user-data']['user_type_id'] == 0) ? true : false;

?>

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Dashboard | INNO Library</title>

    <?php require_once 'styles.php'; ?>
    <style>
    
    .table-list {
        overflow: auto;
        margin-bottom: 20px;
        margin-top: 30px;
    }
    
    .table-user-requests{
        width: 100%;
        text-align: center;
        border-collapse: collapse;
        border: grey solid thin;
        font-size: 15px;
    }
    
    
    .table-user-requests tr:nth-child(1) {
        border: 2px solid darkblue;
    }
    
    .table-user-requests th,td {
        padding: 8px;
    }
    
    .table-user-requests tr:hover {
        outline: 1.5px solid brown; 
    }
    
    .table-user-requests tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    
    .btn-action {
        margin: 0 2px;
        border-radius: 4px;
        padding: 2px 5px;
    }
    
    .btn-action:hover {
        color: black;
        background-color: #fff;
        outline: 2px solid black;
        text-decoration: none;
    }

    .btn-approve {
        background-color: #4CAF10;
        color: white;
    }
    
    .btn-decline {
        background-color: #ff0000;
        color: white;
    }
    
    .btn-hold {
        background-color: #0000ff;
        color: white;
    }
    
    #quick-links-container {
        text-align: left;
        border: 2px solid black;
        margin: 60px 0 40px;
        padding: 10px;
        border-radius: 6px;
    }
    
    #quick-links-container>h5 {
        margin: 5px 20px;
        color: darkblue;
    }
    
    #quick-links-container div>p {
        display: inline-block;
        margin: 0 20px;
        line-height: 2.5;
        font-weight: bold;
        width: 200px;
    }
    
    #quick-links-container div>p>i {
        float: right;
        margin-top: 12px;
    }
    
    #quick-links-container div>a {
        color: blue;
        padding: 3px 10px;
        font-weight: 550;
        border-radius: 5px;
        display: inline-block;
        width: 180px;
    }
    
    #quick-links-container div>a:hover {
        text-decoration: none;
        color: white;
        background-color: black;
        
    }
    
    body {
        font-family: "Arial";
        font-size: 15px;
    }

    /* Style the tab */
    .tab {
        overflow: hidden;
        border: 1px solid #ccc;
        background-color: #f1f1f1;
        color: black;
        margin: 20px 0 0;
    }

    /* Style the buttons inside the tab */
    .tab button {
        background-color: inherit;
        float: left;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 14px 16px;
        transition: 0.3s;
        font-size: 15px;
        color: teal;
        font-weight: bolder;
    }

    /* Change background color of buttons on hover */
    .tab button:hover {
        background-color: #ddd;
    }

    /* Create an active/current tablink class */
    .tab button.active {
        background-color: #ccc;
    }

    /* Style the tab content */
    .tabcontent {
        display: none;
        padding: 25px 15px;
        border: 1px solid #ccc;
        border-top: none;
        max-height: 1200px;
        overflow: auto;
        width: 100%;
        margin-bottom: 30px;
    }
    
    .tab-content-record-count {
        background-color: green;
        border-radius: 50%;
        font-size: 13px;
        color: white;
        margin-left: 4px;
        vertical-align: super;
        padding: 3px 6.5px 2.5px 6px;
    }
    
    #manage-my-booking-history table, tr {
        border: 1px solid blue;
    }

    </style>

  </head>


<body>
<?php require_once 'header.php'; ?>

    <h2 style="position: relative; top: 40px; font-family:'Baskerville Old Face';"><i class="fas fa-home"  style="margin-right: 30px;"></i>Innopolis Online Library Management Portal</h2>
    <div class="container w3-hide-small" style="min-height: 80px; position: relative; top: 35px; ">
        <div class="row">
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="far fa-3x fa-gem text-primary mb-3 sr-icons"></i>
              <h5 class="mb-3">Quality</h5>
              <p class="text-muted mb-0">We choose the best collection for our students!</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fa fa-3x fa-paper-plane text-primary mb-3 sr-icons"></i>
              <h5 class="mb-3">Ready to Book</h5>
              <p class="text-muted mb-0">You can book any book, anytime!</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fa fa-3x fa-newspaper-o text-primary mb-3 sr-icons"></i>
              <h5 class="mb-3">Up to Date</h5>
              <p class="text-muted mb-0">Everything is upto date!</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fa fa-3x fa-heart text-primary mb-3 sr-icons"></i>
              <h5 class="mb-3">Classic Experience</h5>
              <p class="text-muted mb-0">A wonderful experience is assured!</p>
            </div>
          </div>
        </div>
        <br />
    </div>

<div class="container">
<!-- QUICK LINKS SECTION -->
<div id="quick-links-container">
    <h5>DASHBOARD MENU</h5>
    
    <div id="manage-user-links">
        <p>User Profile Management&nbsp;&nbsp;<i class="fas fa-chevron-right"></i></p>
        
        <!-- The user with type_id=1 is ALWAYS a Librarian. -->
        <?php if ($user_is_librarian) : ?>
            <?php if ($_SESSION['user-data']['user_type_id']==0) : ?>
                <a href="#new-user-requests" onclick="document.getElementById('manage-librarian-access').style.display = 'block'" title="Click to add new documents.">Manage Librarian's Access</a>
                <div class="w3-modal" id="manage-librarian-access" style="font-size: 14px;">
                    <div class="w3-modal-content w3-round w3-padding w3-animate-zoom">
                        <div class="w3-button w3-hover-none w3-display-topright w3-right w3-xlarge" onclick="document.getElementById('manage-librarian-access').style.display='none'"><i class="far fa-times-circle"></i></div>
                        <h3 class="w3-row w3-center">Manage access to Librarians</h3>
                        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <div class="w3-row w3-padding">
                                <label class="">Select Librarian: </label>
                                <select name="librarian-id" class="w3-select w3-margin-left" style="width: 100%; max-width: 400px;" required>
                                    <option value="" selected disabled hidden>Choose from below.</option>
                                    <?php
                                        $librarians = $user->returnAllUsers(1);
                                        foreach ($librarians as $lib) {
                                            echo '<option value="' . $lib['user_card_number']. '">' . $lib['name'] . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>
                                
                            <div class="w3-row w3-padding-small">
                                <span class="w3-cell w3-third  w3-padding-small">
                                <input class="w3-checkbox" type="checkbox" name="add-doc-la" value="" />
                                <label>Add Doc</label>
                                </span>
                                
                                <span class="w3-cell w3-third w3-padding-small">
                                <input type="checkbox" name="modify-doc-la" />
                                <label>View/Modify Doc</label>
                                </span>
                                
                                <span class="w3-cell w3-third w3-padding-small">
                                <input type="checkbox" name="remove-doc-la" />
                                <label>Remove Doc</label>
                                </span>
                                
                                <span class="w3-cell w3-third w3-padding-small">
                                <input type="checkbox" name="add-user-la" />
                                <label>Add Users</label>
                                </span>
                                
                                <span class="w3-cell w3-third w3-padding-small">
                                <input type="checkbox" name="modify-user-la" />
                                <label>View/Modify Users</label>
                                </span>
                                
                                <span class="w3-cell w3-third w3-padding-small">
                                <input type="checkbox" name="remove-user-la" />
                                <label>Remove Users</label>
                                </span>
                            </div>    
                            <div class="w3-block w3-center" style="float: none;">
                                <input class="w3-button w3-teal" type="submit" name="submit-librarian-access" value="Update" />
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <a href="list-users.php" title="This page shows list of users of this system.">List Registered Users</a>    
            <?php if ($_SESSION['user-data']['user_type_id']==0) : ?>
                <a href="#" class="w3-button" onclick="document.getElementById('manage-user-privileges').style.display='block'" title="Click to assign privileges to the user.">Manage User Privileges</a>
                <a href="logs.php" title="This page shows list of logs.">Logs</a>
                <div id="manage-user-privileges" class="w3-modal" style="font-size: 13px;">
                    <div class="w3-modal-content w3-round w3-padding w3-animate-zoom">
                        <div class="w3-button w3-hover-none w3-display-topright w3-right w3-xlarge" onclick="document.getElementById('manage-user-privileges').style.display='none'"><i class="far fa-times-circle"></i></div>
                        <div class="w3-padding">
                            <h3 class="w3-row w3-center">Manage User Privileges</h3>
                            This section allows you (<strong>admin</strong>) of this system to manage privileges of other users according to their type.
                            <style>
                                #table-manage-privileges {
                                    width: 100%;
                                    border-collapse: collapse;
                                }
                                
                                #table-manage-privileges td, th {
                                    border: 1px solid #dddddd;
                                    padding: 8px;
                                    font-weight: bold;
                                }
                                
                                #table-manage-privileges tr:nth-child(even) {
                                    
                                }

                            </style>
                            <table id="table-manage-privileges" class="w3-row w3-center w3-margin-top">
                                <tr class="w3-win8-green">
                                    <th>TypeID</th>
                                    <th>User Type</th>
                                    <th>Add Doc</th>
                                    <th>Modify Doc</th>
                                    <th>Remove Doc</th>
                                    <th>Add User</th>
                                    <th>Modify User</th>
                                    <th>Remove User</th>
                                    <th>Action</th>
                                </tr>
                                    <?php
                                        $all_user_types = $user->allUserTypes();
                                        //die(print_r($all_user_types));
                                        foreach($all_user_types as $user_type) {
                                            $user_type_id = $user_type['type_id'];
                                            //die(print_r($user_type));
                
                                            if ($user_type_id > 0) {
                                                $privileges = $user->getPrivilegesByType($user_type_id);
                                                
                                                if ($privileges) {
                                                    $add_doc = $privileges['add_doc'];
                                                    $modify_doc = $privileges['modify_doc'];
                                                    $remove_doc = $privileges['remove_doc'];
                                                    $add_user = $privileges['add_user'];
                                                    $modify_user = $privileges['modify_user'];
                                                    $remove_user = $privileges['remove_user'];
                                                } else {
                                                    $add_doc = $modify_doc = $remove_doc = $add_user = $modify_user = $remove_user = 0;
                                                }
                                                $to_check = "";
                                                echo '<tr><form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
                                                    echo '<td><input type="text" name="user-type-id" value="'.$user_type_id.'" hidden />'. $user_type_id .'</td>';
                                                    echo '<td>'. ucwords($user->getUserTypeName($user_type_id)) .'</td>';
                                                    $to_check = ($add_doc == 1) ? "checked" : "";
                                                    echo '<td><input type="checkbox" name="add-doc" value="'. $add_doc . '" '. $to_check . ' /></td>';
                                                    $to_check = ($modify_doc == 1) ? "checked" : "";
                                                    echo '<td><input type="checkbox" name="modify-doc" value="'. $modify_doc . '" '. $to_check . ' /></td>';
                                                    $to_check = ($remove_doc == 1) ? "checked" : "";
                                                    echo '<td><input type="checkbox" name="remove-doc" value="'. $remove_doc . '" '. $to_check . ' /></td>';
                                                    $to_check = ($add_user == 1) ? "checked" : "";
                                                    echo '<td><input type="checkbox" name="add-user" value="'. $add_user . '" '. $to_check . ' /></td>';
                                                    $to_check = ($modify_user == 1) ? "checked" : "";
                                                    echo '<td><input type="checkbox" name="modify-user" value="'. $modify_user . '" '. $to_check . ' /></td>';
                                                    $to_check = ($remove_user == 1) ? "checked" : "";
                                                    echo '<td><input type="checkbox" name="remove-user" value="'. $remove_user . '" '. $to_check . ' /></td>';
                                                echo '<td><input type="submit" name="btn-set-user-privileges" class="w3-metro-blue w3-text-white w3-hover-black w3-padding-small w3-round" value="Update" /></td>';
                                                echo '</form><tr>';
                                            }
                                        }
                                    ?>
                            </table>
                        
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?php echo $_SERVER['PHP_SELF'].'?destination=header&subject=my-profile&action=view&id=my-profile'; ?>" title="Click to see your profile.">See My Profile</a>
            <a href="<?php echo $_SERVER['PHP_SELF'].'?destination=header&subject=my-profile&action=edit&id=my-profile'; ?>" title="Click to see your profile.">Edit My Profile</a>
        <?php endif; ?>
        
    </div>
    
    <div id="manage-document-links">
        <p>Document Management&nbsp;&nbsp;<i class="fas fa-chevron-right"></i></p>
        
        <?php if ($user_is_librarian) : ?>
            <a href="add-new-document.php" title="Click to add new documents.">Add New Document</a>
        <?php endif; ?>

        <a href="advanced-search.php">Search Document</a>
        <a href="list-documents.php">View All Documents</a>
    </div>
    
    <div id="manage-booking-links">
        <p>Booking Management&nbsp;&nbsp;<i class="fas fa-chevron-right"></i></p>
        
        <?php if ($user_is_librarian) : ?>
            <a href="#table-booking-requests" title="Click to add new documents.">New Booking Requests</a>
            <a href="#" title="Click to see list of booked documents.">List Current Bookings</a>
        <?php else: ?>
            <a href="booking.php" title="Click to book a new Document.">Book Document</a>
            <a href="#" title="Click to see your current booking requests.">My Current Bookings</a>
        <?php endif; ?>
        
    </div>
    
</div>

    <!-- DASHBOARD PAGE FOR LIBRARIANS AND PATRONS -->
    <section style="margin: 0; padding:0;">

    <div class="tab">
        <?php if (!$user_is_librarian) : ?>
            <button class="tablinks" onclick="openPanel(event, 'manage-my-booking-requests')" id="defaultOpen">My Booking Requests<span class="tab-content-record-count"><?php echo count($my_booking_requests); ?></span></button>
            <button class="tablinks" onclick="openPanel(event, 'manage-my-booking-history')" id="defaultOpen">My Booking History<span class="tab-content-record-count"><?php echo count($my_booking_history_records); ?></span></button>
        <?php else: ?>
        <button class="tablinks" onclick="openPanel(event, 'manage-new-booking-requests')" id="defaultOpen">New Booking Requests<span class="tab-content-record-count"><?php echo count($new_booking_requests)+count($outstanding_pending_requests); ?></span></button>
        <button class="tablinks" onclick="openPanel(event, 'manage-document-return')">'Document Return' Confirmation<span class="tab-content-record-count"><?php echo count($unconfirmed_return_records); ?></span></button>
        <button class="tablinks" onclick="openPanel(event, 'manage-booking-overdue')">Overdue Bookings<span class="tab-content-record-count"><?php echo count($overdue_booking_records); ?></span></button>
        <button class="tablinks" onclick="openPanel(event, 'manage-user-requests')">New User Requests<span class="tab-content-record-count"><?php echo count($new_user_requests); ?></span></button>
        <button class="tablinks" onclick="openPanel(event, 'show-activity-log')">Activity Log<sup> </sup></button>
        <?php endif; ?>
    </div>

<?php if (!$user_is_librarian) : ?>

    <!-- My Booking Requests -->
    <div id="manage-my-booking-requests" class="tabcontent">
        <h4>My Booking Requests</h4>    
        <p style="text-align: left;">
            The following list shows your <strong>pending</strong> booking requests as well as information about currently booked item.
            Your <strong>queue rank</strong> is <strong>1</strong> means your request is on top and is likely to be processed next, unless any other outstanding requests and/or requests by priority users are received for the same document (in which case your rank value increases, meaning your request has been pushed back).
        </p>
        <div class="table-list">
        <table class="table-user-requests">
            <tr>
                <th>S.N.</th>
                <th>Document Type</th>
                <th>ID</th>
                <th>Title</th>
                <th>Date Requested</th>
                <th>Document Status</th>
                <th>Booking Request Status</th>
            </tr>
        <?php
            $i = 1;
            foreach ($my_booking_requests as $row) {
                $booking_doc_info = explode('-', substr($row['booking_status'], 8, strlen($row['booking_status'])));
                $my_request_rank = $booking->calculateMyRank($row['booking_log_id'], $booking_doc_info[0], $booking_doc_info[1]);
                //$earliest_avail_date = $booking->
                $outstanding = ($row['is_outstanding_request'] == 1) ? '<br />(Outstanding Request)' : ' | <strong style="color:blue;">Queued</strong><br /><span style="color: green; font-weight: bold;">Rank in Queue</span> &#8658; '.$my_request_rank;
                $bookable_items = $doc->getBookableDocItems($booking_doc_info[0], $booking_doc_info[1]);
                $available_status = (count($bookable_items)>0) ? "<strong>".count($bookable_items)."</strong> Copies Available." : "No bookable copies.";

                echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $doc->getDocTypeByTypeId($booking_doc_info[0]) . "</td>";
                    echo "<td>" . $booking_doc_info[1] . "</td>";
                    echo "<td>" . $doc->getDocTitleByDocId($booking_doc_info[0], $booking_doc_info[1]) . "</td>";
                    echo "<td>" . $row['booked_date'] . "</td>";
                    echo "<td>". $available_status ."</td>";
                    echo '<td style="font-family: Consolas; font-size: 17px;">PENDING'. $outstanding .'</td>';                
                echo "</tr>";
                $i += 1;
            }

        ?>
        </table>
        </div>

    </div>

    <!-- Booking History for Patrons -->
    <div id="manage-my-booking-history" class="tabcontent">
        <h4>My Booking History</h4>
        <p>This is where the current user's booking history is displayed.</p>
        <div class="table-list">
        <table class="table-user-requests">
            <tr>
                <th>S.N.</th>
                <th>Doc Type</th>
                <th>Doc ID</th>
                <th>Document Title</th>
                <th>Date Booked</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        <?php
            $i = 1;
            foreach ($my_booking_history_records as $row) {
                $outstanding = ($row['is_outstanding_request'] == 1) ? '<br />(Outstanding Request)' : '';
                $doc_type_id = $doc->getDocTypeIdByDocItemId($row['item_id']);
                $doc_table_id = $doc->getDocTableIdByDocItemId($row['item_id']);
               
                echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $doc->getDocTypeByTypeId($doc_type_id) . "</td>";
                    echo "<td>" . $doc_table_id . "</td>";
                    echo "<td>" . $doc->getDocTitleByDocId($doc_type_id, $doc_table_id) . "</td>";
                    echo "<td>" . $row['booked_date'] . "</td>";
                    
                    if ($row['booking_status'] == "RETURNED-UNCONFIRMED") {
                        echo "<td><strong>RETURNED</strong>". $outstanding ."</td>";
                        echo "<td><strong>Awaiting Confirmation</strong></td>";
                    } else {
                        echo '<td><strong>' . $row['booking_status'] . "</strong>". $outstanding ."</td>";
                        echo '<td>';
                            if ($row['booking_status'] == "BOOKED") {
                                $expected_return_date = date('Y-m-d', strtotime($row['booked_date'] . ' + ' . $row['duration_days'] . ' days'));
                                $interval = $booking->formatDateDifference(date('Y-m-d'), $expected_return_date);
                                if ($expected_return_date > date('Y-m-d')) {
                                    echo 'To be returned within '. $interval .'<br/><a style="text-align: right" href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=return-doc&action=say-return&id='. $row['booking_log_id'] . '" class="btn-action">Mark Returned<i class="far fa-check-square" style="margin-left:4px;"></i></a>';
                                } else {
                                    echo "Overdue by " . $interval;
                                    echo '<br />[<strong style="color: red; text-align: left;">Expected Fine</strong>: ' . $booking->calculateFine($expected_return_date) . ' Rubles]';
                                }
                                
                            } else if ($row['booking_status'] == "RETURNED") {
                                echo '<a href="#" class="btn-action">View Details<i class="fas fa-info-circle" style="margin-left:4px;"></i></a>';
                            }
                        echo "</td>";
                    }
                $i += 1;
            }

        ?>
        </table>
        </div>
    </div>

    <?php else : ?>
    
    <div id="manage-new-booking-requests" class="tabcontent">
    <p style="text-align: left;">The following booking requests have been received from the users. Click on <strong>Approve</strong> to approve the request, <strong>Decline</strong> to decline the request, and <strong>Hold</strong> to put the request on hold such that it can be dealt later.</p>
    <?php if ($outstanding_pending_requests) : ?>
        <h4 class="w3-card w3-green w3-text-white w3-padding w3-round">Outstanding Booking Requests</h4>
        <p class="w3-left-align">The following outstanding booking requests have been made. Your discretion is needed to deal with these requests. <strong>Note</strong> that approving <em>any</em> outstanding requests made by users may not be fair to other users.</p>
        <div class="table-list">
        <table class="table-user-requests">
            <tr>
                <th>S.N.</th>
                <th>Document Type</th>
                <th>Document ID</th>
                <th>Document Title</th>
                <th>Date Requested</th>
                <th>Requesting User</th>
                <th>Actions</th>
            </tr>
        <?php
            $i = 1;
            foreach ($outstanding_pending_requests as $row) {
                if (!isset($booking_doc_info)) $booking_doc_info = explode('-', substr($row['booking_status'], 8, strlen($row['booking_status'])));
                echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $doc->getDocTypeByTypeId($booking_doc_info[0]) . "</td>";
                    echo "<td>" . $booking_doc_info[1] . "</td>";
                    echo "<td>" . $doc->getDocTitleByDocId($booking_doc_info[0],$booking_doc_info[1]) . "</td>";
                    echo "<td>" . $row['booked_date'] . "</td>";
                    echo "<td>" . $user->returnUserInfo($row['user_id'])['name']. " [" . $row['user_id'] . "]</td>";
                    echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=booking-request&action=approve&id='. $row['booking_log_id'] . '" class="btn-action btn-approve" title="Approve this request." ><i class="far fa-check-square"></i></a>
                            <a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=booking-request&action=decline&id='. $row['booking_log_id'] . '" class="btn-action btn-decline" title="Decline this request."><i class="far fa-window-close"></i></a>
                            <a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=booking-request&action=hold&id='. $row['booking_log_id'] . '" class="btn-action btn-hold" title="Keep this request on hold."><i class="fas fa-thumbtack"></i></a>
                    </td>';
                echo "</tr>";
                
                $i += 1;
            }

        ?>
        </table>
        </div>
        
    <?php endif; ?>
        <h4 class="w3-card w3-green w3-text-white w3-margin-top w3-padding w3-round">Regular Booking Requests</h4>
        <p style="text-align: left;">The following are the regular booking requests made by the users; the requests are sorted by document first and then for each document, the requesting user's priority according to their type has been taken into consideration in ordering.</p>
        <style>
            #booking-requests {
                border: none;
                border-collapse: collapse;
                width: 100%;
            }
            
            #booking-requests tr {
                border: none;
            }
            
            #booking-doc-title {
                line-height: 1.3;
                color: darkblue;
                border-bottom: 1.5px solid grey;
            }
            
            #booking-doc-type {
                font-size: 15px;
                font-weight: bold;
            }
            
            #booking-availability {
                display: block;
                float: right;
                color: darkgreen;
                margin-right: 8px;
            }
            
            #doc-booking-requests {
                width: 95%;
                margin: 20px auto;
            }
            
            #doc-booking-requests tr {
                border: 2px solid black;
                text-align: center;
                border-top: none;
                border-bottom: none;
            }
        

        </style>
        <div class="w3-card w3-padding-small" style="text-align: left; padding: 5px 15px; font-family: Arial, Helvetica, sans-serif;">
        <table id="booking-requests">
        <?php
            $i = 1; $j=0;
            $booking_requests_by_doc = $booking->filterRequestsByDoc($new_booking_requests);
            
            foreach($booking_requests_by_doc as $doc_info=>$doc_requests) {
                $j++;
                $booking_doc_info = explode('-', $doc_info);
                $bookable_items = $doc->getBookableDocItems($booking_doc_info[0], $booking_doc_info[1]);
                $available_status = (count($bookable_items)>0) ? '<span class="w3-win8-blue w3-padding-small w3-round">Available for booking.<span>' : '<span class="w3-win8-red w3-padding-small w3-round">No bookable copies.<span>';
                if ($j%2==0) {
                    echo '<tr class=""><td>';
                } else {
                    echo '<tr class="w3-light-gray"><td>';
                }
                echo '
                <h5 class="w3-large" id="booking-doc-title">' . $doc->getDocTitleByDocId($booking_doc_info[0], $booking_doc_info[1]) . '<span style="margin-left: 8px;">[<span id="booking-doc-type">' . $doc->getDocTypeByTypeId($booking_doc_info[0]) . '</span>, ID &#8658; '. $booking_doc_info[1] .']</span><span class="w3-right w3-small">'. $available_status .'</span></h5>
                    <div class="doc-request-row">
                        <span style="display: inline-block;">Total Non-reference Copies in Library = <strong>'. count($doc->getNonReferenceCopies($booking_doc_info[0], $booking_doc_info[1])) .'</strong></span> <strong style="margin: 0 8px;">|</strong> <span style="display: inline-block;">Copies Currently Booked = <strong>'. count($doc->getCurrentlyBookedCopies($booking_doc_info[0], $booking_doc_info[1])) .'</strong></span>
                        <br />
                        <table id="doc-booking-requests">
                            <tr class="w3-row w3-metro-dark-green w3-text-white">
                                <th class="w3-cell w3-cell-middle w3-padding" width: "20px">Rank</th>
                                <th class="w3-cell w3-cell-middle w3-padding"><i class="fas fa-list-ol"></i></th>
                                <th class="w3-cell w3-cell-middle w3-padding">Requested By</th>
                                <th class="w3-cell w3-cell-middle w3-padding">Requesting User Type</th>
                                <th class="w3-cell w3-cell-middle w3-padding">Requested On</th>
                                <th class="w3-cell w3-cell-middle w3-padding">Actions</th>
                            </tr>';
                $i = 1;
                
                if (count($bookable_items)==0) {
                    echo '<style>#approve-booking {pointer-events: none; cursor: default;}</style>';
                }
                 
                foreach($doc_requests as $req) {
                    echo '<tr class="w3-row"><td class="w3-cell w3-cell-middle w3-padding">&#8658;</td>';
                    echo '<td class="w3-cell w3-cell-middle w3-padding">'. $i .'</td>';
                    echo '<td class="w3-cell w3-cell-middle w3-padding"><span class="w3-left">'. $user->returnUserInfo($req['user_id'])['name']. '</span> <span class="w3-tag w3-right w3-round w3-metro-dark-red">' . $req['user_id'] . '</span></td>';
                    
                    echo '<td class="w3-cell w3-cell-middle w3-padding">'. ucwords($user->getUserTypeName($user->returnUserInfo($req['user_id'])['user_type_id'])) . '</td>';
                    echo '<td class="w3-cell w3-cell-middle w3-padding">'. $req['booked_date']. '</td>';
                    
                    echo '<td class="w3-cell w3-cell-middle w3-padding">
                                    <a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=booking-request&action=approve&id='. $req['booking_log_id'] . '" id="approve-booking" class="btn-action btn-approve" title="Approve this request." ><i class="far fa-check-square"></i></a>
                            <a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=booking-request&action=decline&id='. $req['booking_log_id'] . '" class="btn-action btn-decline" title="Decline this request."><i class="far fa-window-close"></i></a>
                            <a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=booking-request&action=hold&id='. $req['booking_log_id'] . '" class="btn-action btn-hold" title="Keep this request on hold."><i class="fas fa-thumbtack"></i></a>
                    
                                </td>';
                    echo '</tr>';
                    $i += 1;
                }
                
                echo '
                        </table>
                        
                    </div>
                ';
                echo '</td></tr>';
            }
        ?>
        </table>
        </div>
    </div>
    
    <!-- Recent Bookings available to both, partrons mark 'returned' and see 'not confirmed' while librarians mark 'Confirm returned' and see 'Return confirmation asked' -->
    <div id="manage-document-return" class="tabcontent">
        <h4>Document Return Confirmation</h4>
        <p>As a librarian, you must <strong>confirm return</strong> of any bookings as indicated by the concerned user.</p>
        <div class="table-list">
        <table class="table-user-requests">
            <tr>
                <th>S.N.</th>
                <th>Document Type</th>
                <th>Unique Document ID</th>
                <th>Document Title</th>
                <th>Date Returned</th>
                <th>Status</th>
            </tr>
        <?php
            $i = 1;
            foreach ($unconfirmed_return_records as $row) {
                echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $doc->getDocTypeByTypeId($doc->getDocTypeIdByDocItemId($row['item_id']) ) . "</td>";
                    echo "<td>" . $row['item_id'] . "</td>";
                    echo "<td>" . $doc->getDocTitleByItemId($doc->getDocTypeIdByDocItemId($row['item_id']), $row['item_id']) . "</td>";
                    echo "<td>" . $row['returned_date'] . "</td>";
                    echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=return-doc&action=confirm-return&id='. $row['booking_log_id'] . '" class="btn-action btn-approve">Confirm&nbsp;<i class="far fa-check-circle"></i></a></td>';
                echo "</tr>";
                
                $i += 1;
            }

        ?>
        </table>
        </div>
    </div>

    
    <!-- Shows overdue bookings if any -->
    <div id="manage-booking-overdue" class="tabcontent">
        <h4>Overdue Booking List</h4>
        <p>The following bookings are <strong>overdue</strong>. Kindly check with the user and take actions.</p>
        <div class="table-list">
        <table class="table-user-requests">
            <tr>
                <th>S.N.</th>
                <th>Document Title</th>
                <th>Booked By</th>
                <th>Booked Date</th>
                <th>Expected Return Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
    <?php if (!empty($overdue_booking_records)): ?>
    <!-- check if there are any documents overdue... if yes, show this section. -->

        <?php
            $i = 1;
            foreach ($overdue_booking_records as $row) {
                echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $doc->getDocTitleByItemId($doc->getDocTypeIdByDocItemId($row['item_id']), $row['item_id']) . "</td>";
                    echo '<td><a href="'. $_SERVER['PHP_SELF'] .'?destination=header&subject=user-profile&action=view&id='. $row['user_id'] .'">' . $row['user_id'] . "</a></td>";
                    echo "<td>" . $row['booked_date'] . "</td>";
                    $expected_return_date = date('Y-m-d', strtotime($row['booked_date'] . ' + ' . $row['duration_days'] . ' days'));
                    echo "<td>" . $expected_return_date . "</td>";
                    
                    echo "<td>";
                        echo "Overdue by " . $booking->formatDateDifference(date('Y-m-d'), $expected_return_date) . ".";
                        echo '<br />[<strong style="color: red; text-align: left;">Expected Fine</strong>: ' . $booking->calculateFine($expected_return_date) . ' Rubles]';
                    echo "</td>";
                    echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=booking-request&action=clear-overdue&id='. $row['booking_log_id'] . '" class="btn-action ">Clear Overdue&nbsp;<i class="far fa-check-circle"></i></a></td>';
                echo "</tr>";
                
                $i += 1;
            }
        ?>
    <?php endif; ?>
        </table>
        </div>
    </div>
    

    
        <!-- New user requests to be approved by Librarians -->
    <div id="manage-user-requests" class="tabcontent">
        <h4>New User Requests</h4>
        <p>As a librarian, you have the capability to approve user account requests. The following are the requests received for new user account <strong>chronologically ordered</strong> by their received date.</p>
        <div class="table-list">
        <table id="new-user-requests" class="table-user-requests">
            <tr>
                <th>S.N.</th>
                <th>Applicant Name</th>
                <th>Innopolis ID</th>
                <th>Requested Account Type</th>
                <th>Date Submitted</th>
                <th>Actions</th>
            </tr>
        <?php
            $i = 1;
            foreach ($new_user_requests as $row) {
                echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td>" . $row['email'] . "</td>";
                    echo '<td style="font-family: \'Consolas\';">' . ucwords($user->getUserTypeName($row['user_type_id']*(-1))) . "</strong></td>";
                    echo "<td>" . $row['date_registered'] . "</td>";
                    echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=user-request&action=approve&id='. $row['user_card_number'] . '" class="btn-action btn-approve" >Approve</a>
                            <a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=user-request&action=decline&id='. $row['user_card_number'] . '" class="btn-action btn-decline" >Decline</a>
                            <a href="' . $_SERVER['PHP_SELF'] . '?destination=dashboard&subject=user-request&action=hold&id='. $row['user_card_number'] . '" class="btn-action btn-hold" >Hold</a>
                    </td>';
                echo "</tr>";
                
                $i += 1;
            }

        ?>
        </table>
        </div>
    </div>

    
    <div class="tabcontent" id="show-activity-log">
        <h4>Activity Log of Inno Library</h4>
        <p class="w3-left-align">As an <strong>administrator</strong> of this library system, you have the unique privilege to see the activity log of the system. Here you can see all the actions such as booking, user requests, fine paid, etc. that has happened in the sytem. These actions have been sorted in reverse chronological order.</p>

        <style>
        .input-search-table {
            background-image: url('https://www.w3schools.com/css/searchicon.png');
            background-position: 7px 6px;
            background-repeat: no-repeat;
            width: 100%;
            font-size: 14px;
            padding: 6px 5px 5px 35px;
            border: 1px solid #ddd;
        }
        </style>
        
        <div class="w3-right">
            <input type="text" class="input-search-table w3-animate-input" onkeyup='searchTable("table-activity-log", this.value)' placeholder="Search in this table..." title="Type to search an activity log.">
        </div>
        <table width=100% id="table-activity-log">
            <tr>
                <th>Date</th>
                <th>Activity Description</th>
            </tr>
        </table>
    </div>
  

<?php endif; ?>
</div>
  
    <script>
    
    function openPanel(evt, panelId) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(panelId).style.display = "block";
        evt.currentTarget.className += " active";
    }

    // Get the element with id="defaultOpen" and click on it
    document.getElementById("defaultOpen").click();

    /* Utility function to sesarch a table with given id. */
    function searchTable(table_id, searchStr) {
        var table, tr, i, j, td, cell;
        
        table = document.getElementById(table_id);
        searchStr = searchStr.toUpperCase();
        tr = table.getElementsByTagName("tr");
        
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td");
            
            for(j = 1; j < td.length-1; j++) {
                cell = td[j];
                
                if (cell) {
                    
                    if (cell.innerHTML.toUpperCase().indexOf(searchStr) > -1) {
                        tr[i].style.display = "";
                        break;
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }       
        }
    }
    
    $(document).ready(function(){
        function performAction(destination, subject, action, id) {
            $.ajax({
                url: "dashboard.php",
                data: {
                    destination: destination,
                    subject: subject,
                    action: action,
                    id: id
                },
                
                type: "POST",
                dataType: "text",
            })
            
                .done(function(text) {
                    if (!text) {
                        
                    } else {
                        // change respective html according to the results received.
                    }
                })
                
                // Code to run if the request fails; the raw request and
                // status codes are passed to the function
                .fail(function( xhr, status, errorThrown ) {
                    alert("Ajax Request failed!");
                })
                
                // Code to run regardless of success or failure;
                .always(function( xhr, status ) {
                    //alert( "The request is complete!" );
            });
        }
    });
    
    </script>


<?php require_once 'footer.php'; ?>

<?php require_once 'javascript.php'; ?>

  </body>

</html>

