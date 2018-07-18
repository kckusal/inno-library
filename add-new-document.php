<?php

require_once 'config.php';
require_once 'load-classes.php';

$user = new User();

if ($user->getLibrarianPrivilege($_SESSION['user-data']['user_card_number'])['add_doc'] == 0) {
    $_SESSION['msg'] = "Sorry! You're not authorized to gain access to the requested page. :(";
    header('Location: ' . ROOT_URL . "dashboard.php");
    exit();
}

if (isset($_SESSION['add-doc'])) {
    (new Document())->addDocuments($_SESSION['add-doc']);
    unset($_SESSION['add-doc']);
    (new Message('error', 'Documents Added Successfully!', "The documents with the information you provided has been successfully added to the library database."))->display();
}

// The page form is submitted using the 'POST' method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['btn-add-doc'])) {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        
        
        $_SESSION['add-doc'] = $post;
        header('Location: ' . $_SERVER["PHP_SELF"]);
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Add new documents | INNO Library</title>

    <?php require_once 'styles.php'; ?>

    <style>
    
    #add-doc-form {
        outline: 2px groove black;
        margin: 30px auto;
        width: 600px;
        text-align: left;
        padding: 10px 50px 20px;
    }
    
    #add-doc-form>h4 {
        text-align: center;
        margin-bottom: 35px;
        color: darkblue;
    }
    
    .asterik {
        color: red;
        font-weight: bold;
    }
    
    #add-doc-form label {
        font-weight: bold;
    }

    #add-doc-form input, select {
        margin: 6px;
        padding: 8px;
        width: 100%;
        max-width: 500px;
        font-size: 15px;
        font-family: Raleway;
        border: 1px solid #aaaaaa;

    }
    
    #add-doc-form input[type=submit] {
        display: block;
        width: 120px;
        height: 50px;
        padding: 6px 12px;
        margin: 0 auto;
        cursor: pointer;
        background: green;
        border-radius: 4px;
        color: white;
        font-size: 16px;
        opacity: 0.8;
    }
    
    #add-doc-form input[type=submit]:hover {
        opacity: 1;
        background: #00d000;
    }

    </style>

</head>
<body>
<?php require_once 'header.php'; ?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="w3-card" id="add-doc-form" onload="manageInputs()">
    <h4>Add New Documents to Library</h4>
    
    <p style="text-align: justify; margin-bottom: 8px;">
        <strong>Note</strong>: Add multiple copies at a time if and only if the document copies satisfy the following properties:
        <ul style="margin-bottom: 35px;">
            <li>They are all same document type, and</li>
            <li>They are all either reference copies or not reference copies, and</li>
            <li>They are all going to be stored in same location.</li>
        </ul>
        In addition, if they are books, make sure you are all of same edition.
    </p>
    
    <p class="general-input">
        <label>Document type <span class="asterik">*</span></label>
        <select required name="doc-type-id" onchange="if (this.selectedIndex) manageInputs();">
            <option value="" selected disabled hidden>Choose a document type</option>
            <?php
                foreach ((new Document())->returnTypes() as $type) {
                    echo '<option value="' . $type['type_id'] . '">' . $type['type_name'] . '</option>';
                }
            ?>
        </select>
    </p>

    <p  class="general-input">
        <label>Document Name/Title <span class="asterik">*</span></label>
        <input placeholder="" name="title" required/>
    </p>
    
    <p class="general-input">
        <label>Author(s) <span class="asterik">*</span></label>
        <br/><span style="font-family: Calibri; font-size: 15px;">Author names should be separated with a semi-colon (;).</span>
        <input placeholder="  e.g.  Thomas H. Cormen; Clifford Stein; Ronald Riverest" name="authors" required>
    </p>

    <p class="book-input" hidden>
        <label>Edition Number <span class="asterik">*</span></label>
        <input type="number" name="book-edition-number" min="1" max="10" placeholder="" />
    </p>
    
    <p class="journal-input" hidden>
        <label>Journal Name <span class="asterik">*</span></label>
        <input type="text" name="journal-name" placeholder="Enter Journal's name." />
    </p>
    
    <p class="journal-input" hidden>
        <label>Issue Name <span class="asterik">*</span></label>
        <input type="text" name="journal-issue-name" placeholder="Enter issue name." />
    </p>
    
    <p class="journal-input" hidden>
        <label>Issue Editor <span class="asterik">*</span></label>
        <input type="text" name="journal-issue-editor" placeholder="" />
    </p>
    
    <p class="journal-input" hidden>
        <label>Issue Publication Date <span class="asterik">*</span></label>
        <input type="date" name="journal-issue-publication-date"  />
    </p>
    
    <p class="book-input" hidden>
        <label>Publisher Name <span class="asterik">*</span></label>
        <input type="text" name="book-publisher-name" placeholder=""  />
    </p>
    
    <p class="book-input" hidden>
        <label>Published Date <span class="asterik">*</span></label>
        <input type="date" name="book-published-date"  />
    </p>
    
    <p class="general-input">
        <label>Number of Copies <span class="asterik">*</span></label>
        <input type="number" name="count-copies" min="0" required />
    </p>
    
    <p class="general-input">
        <label>Storage Location</label>
        <input type="text" name="storage-location" placeholder="Enter location where these copies will be stored." required />
    </p>
    
    <p class="general-input" >
        <label>Are these reference Copies? <span class="asterik">*</span></label>
        <select name="are_reference_copies" required>
            <option value="" selected disabled hidden>Choose from options below.</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
    </p>
    
    <p class="general-input">
        <label>Keywords <span class="asterik">*</span></label>
        <br/><span style="font-family: Calibri; font-size: 15px;">Keywords should be separated by a comma (,).</span>
        <input type="text" name="keywords" required />
    </p>

    <input type="submit" name="btn-add-doc" class="general-input" value="Add Copies" />

</form>

<?php require_once 'footer.php'; ?>

<script>
    var doc_type = document.getElementsByName('doc-type-id')[0];
    
    var book_inputs = document.getElementsByClassName('book-input');
    var journal_inputs = document.getElementsByClassName('journal-input');
    var av_inputs = document.getElementsByClassName('av-input');
    
    function manageInputs() {
        var selected_type = doc_type.options[doc_type.selectedIndex].value;
        if (selected_type=="1") {
            showInputs(book_inputs);
            hideInputs(journal_inputs);
            hideInputs(av_inputs);
        } else if (selected_type=="2") {
            hideInputs(book_inputs);
            showInputs(journal_inputs);
            hideInputs(av_inputs);
        } else if (selected_type=="3") {
            hideInputs(book_inputs);
            hideInputs(journal_inputs);
            showInputs(av_inputs);
        }
    }
    
    function hideInputs(inputs) {
        for(i=0; i<inputs.length; i++) {
            inputs[i].hidden = true;
            inputs[i].disabled = true;
        }
    }
    
    function showInputs(inputs) {
        for(i=0; i<inputs.length; i++) {
            inputs[i].hidden = false;
            inputs[i].disabled = false;
        }
    }
    
</script>
<?php require_once 'javascript.php'; ?>
</body>
</html>