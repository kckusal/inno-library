<?php
require_once 'config.php';
require_once 'load-classes.php';
$db = new DatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit-booking'])) {
        $data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        
        $doc_type = $data['doc-type'];
        $doc_title = $data['doc-title'];
        $doc_author = $data['doc-author'];
        $duration = $data['duration'];
        $outstanding_request = isset($data['is-outstanding-request']) ? 1 : 0;
        
        $user = new User();
        
        // check if it is a librarian, and has the ability to remove documents.
        if ($_SESSION['user-data']['user_type_id'] == 1 and $user->getLibrarianPrivilege($_SESSION['user-data']['user_card_number'])['remove_doc']==0) {
            $_SESSION['booking-message'] = array (
                'message-type' => "error",
                'message-title' => "Action Results",
                'message' => "Sorry! You are not authorized to make outstanding requests. Please contact administrator for further information."
            );
            header('Location: '. ROOT_URL . 'booking.php');
            exit;
        }
        
        $booking = new Booking();
        $booking->addNewRecord($doc_type, $doc_title, $doc_author, $duration, $outstanding_request, $_SESSION['user-data']);
        $booking_error = $booking->getError();
        
        if ($booking_error) {
            $_SESSION['booking-message'] = array (
                'message-type' => "error",
                'message-title' => "Action Results",
                'message' => $booking_error
            );
        } else {
            $_SESSION['booking-message'] = array (
                'message-type' => "error",
                'message-title' => "Booking Request Queued!",
                'message' => "We have added your request to the <strong>request queue</strong> for this document. You can see the status of your request as well as availability of the requested document in your 'My Booking Requests' section.<br /><br />If you wish to cancel your request at any time, you can do so by deleting your request from the same section."
            );
            $doc = new Document();
            $db->addLog("Submitted new booking request for '" . ucwords($doc->getDocTypeByTypeId($doc_type)) . "' $doc_title.");
        }
        header('Location: '. ROOT_URL . 'booking.php');
    }
} else {
    if (isset($_SESSION['booking-message'])) {
        (new Message($_SESSION['booking-message']['message-type'], $_SESSION['booking-message']['message-title'], $_SESSION['booking-message']['message']))->display();
        unset($_SESSION['booking-message']);
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Submit a Booking Request | INNO Library</title>

    <?php require_once 'styles.php'; ?>

    <style>
    .booking-panel {
        margin-top:535px;
    }

    .booking-panel input[type=text] {
        max-width: 600px;
        display: inline;
    }

    .btn-search {
      background: #424242;
      border-radius: 0;
      color: #fff;
      border-width: 1px;
      border-style: solid;
      border-color: #1c1c1c;
    }
    .btn-search:link, .btn-search:visited {
      color: #fff;
    }
    .btn-search:active, .btn-search:hover {
      background: #1c1c1c;
      color: #fff;
    }

    .search-result-box {
        border: black 2.5px solid;
        margin: 10px 0;
        height: 300px;
    }

    .input-group {
    }

    .booking-container {
        margin: 25px auto;
        margin-left: 10px;
    }

    .inputs {
        width: 480px;
        margin: 0 auto;
        text-align: left;
    }

    .inputs label {
        margin-top:10px;
        margin-bottom:0;
        font-weight: bolder;
    }

    .inputs input {
        width: 90%;
        padding: 12px 20px;
        margin: 8px 0;
        display: inline-block;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    .inputs .asterik {
        color: red;
        padding: 0 2px;
    }

    .inputs #btn-booking-submit {
        background: #424242;
        border-radius: 0;
        color: #fff;
        border-width: 1px;
        border-style: solid;
        border-color: #1c1c1c;
        float: left;
    }

    #btn-booking-submit:link, #btn-booking-submit:visited {
        color: #fff;
    }
    #btn-booking-submit:active, #btn-booking-submit:hover {
        background: #1c1c1c;
        color: #fff;
    }


    </style>

</head>
<body style="background-image:url();">
    <?php require_once 'header.php'; ?>

    <div class="booking-container">
        <h3>Book a new Document</h3>
        <p>This page allows you to submit a request to book a document. Please fill in the form below with the details of the document.</p>
        <div class="inputs">
        <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" style="margin:0; padding:0;">
            <p>
                <label>Type of document<span class="asterik">*</span></label>
                <input list="doc-type" name="doc-type" required type="text" placeholder="Choose a document type" />
                <datalist id="doc-type">
                <?php
                    $doc = new Document();
                    $types = $doc->returnTypes();
                    foreach ($types as $type) {
                        echo "<option value='" . $type['type_name'] . "'>";
                    }
                ?>
                </datalist>
            </p>
            <p>
                <label>Document Name<span class="asterik">*</span></label>
                <input type="text" name="doc-title" required placeholder="e.g. Introduction to Algorithms" />
            </p>
            <p>
                <label>Do you know any author?</label> <br />
                <span>Note:- There are NO documents in the library by authors NOT found in suggestion list below.</span>
                <input list="author-names" name="doc-author" required type="text" placeholder="Type for suggestions">
                <datalist id="author-names">
                <?php
                    $doc = new Document();
                    $authors = $doc->listAuthors();
                    foreach ($authors as $each) {
                        echo "<option value='" . $each['author_name'] . "'>";
                    }

                ?>
                </datalist>
                
            </p>
            <p>
                <label>Duration (in days)<span class="asterik">*</span></label>
                <input type="number" min="0" max="28" name="duration" style="width: 80px; height: 35px; margin-left: 20px;"/>
            </p>
            <p>
                <input type="checkbox" name="is-outstanding-request" style="display: inline; text-align: left; width: 20px; padding-right: 8px;" />
                <label for="checkbox" style="display: inline; text-align: left;">This is an outstanding request.</label>
            </p>
            <p>
                <button class="btn" id = "btn-booking-submit" name="submit-booking">Submit <i class="fas fa-check-circle"></i></button>
            </p>
        </form>
        </div>

    </div>



    <?php require_once 'footer.php'; ?>
    <?php require_once 'javascript.php'; ?>

</body>
</html>