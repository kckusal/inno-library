<?php

class User {
    private $error;     // This error can be accumulated during validation.
    private $database;

    public function __construct() {
        $this->database = new DatabaseConnection();
    }

    public function setError($error_msg) {
        $this->error = $error_msg;
    }
    
    public function getError() {
        return $this->error;
    }

    // returns the user record matching the credentials provided.
    // collect error if no such record found.
    public function login($user_id, $password) {
        $password = md5($password);
        
        $this->database->query("SELECT * FROM users WHERE user_type_id>=0 AND email =:email AND password =:password");
        $this->database->bind(':email', $user_id);
        $this->database->bind(':password', $password);

        return $this->database->nextResult();
        
    }

    // adds a new user record with user type starting with 'PENDING' which upon approval
    // will automatically change to correct user type.
    public function apply($name, $email, $password, $phone, $address, $user_type) {
        $card_number = "INNO".mt_rand(10000000, 99999999);
        $password = md5($password);
        $user_type = "-" . $user_type;

        $this->database->query("INSERT INTO users (user_card_number, name, email, password, phone_number, address, user_type_id) VALUES (:card_number, :name, :email, :password, :phone, :address, :type)");
        $this->database->bind(':card_number', $card_number);
        $this->database->bind(':name', $name);
        $this->database->bind(':email', $email);
        $this->database->bind(':password', $password);
        $this->database->bind(':phone', $phone);
        $this->database->bind(':address', $address);
        $this->database->bind(':type', $user_type);

        $this->database->execute();
    }

    // changes the user type starting with 'PENDING' to correct type and can only be done
    // upon approval from a librarian
    public function approve($card_number) {
        
        $this->database->query("UPDATE users SET `user_type_id`=(-1*`user_type_id`) WHERE user_card_number=:card_num");
        $this->database->bind(':card_num', $card_number);
        $this->database->execute();
    }

    public function remove($card_number) {
        $this->database->query("DELETE FROM users WHERE user_card_number= :card_num");
        $this->database->bind(':card_num', $card_number);
        $this->database->execute();
    }

    // returns an array of all user-type records in database.
    public function allUserTypes() {
        $this->database->query("SELECT * FROM `user-type`");
        return $this->database->resultset();
    }
    
    // returns user-type which have sub-types
    // types having subtypes have 'parent_type_id' set to 0; having no subtypes have 'parent_type_id' set to their own type_id
    public function parentTypes() {
        $this->database->query("SELECT DISTINCT `parent_type_id` FROM `user-type`");
        $parent_types = $this->database->resultset();
        
        $output = array();
        foreach($parent_types as $parent_id) {
            $this->database->query("SELECT * FROM `user-type` WHERE type_id=:type_id");
            $this->database->bind(':type_id', $parent_id['parent_type_id']);
         
            array_push($output, $this->database->nextResult());
        }
        return $output;
    }
    
    // returns base user-types, not the parent ones if any.
    public function individualTypes() {
        $this->database->query("SELECT DISTINCT * FROM `user-type` WHERE `parent_type_id`>=0");
        return $this->database->resultset();
    }
    
    // returns an array consisting of all users in the database or according to user type
    // depending on the argument passed
    public function getUserTypeIdByTypeName($user_type = "") {
        $sql = 'SELECT * FROM `user-type` WHERE `type_name`=:type';

        $this->database->query($sql);
        $this->database->bind(':type', $user_type);
        return $this->database->nextResult()['type_id'];
    }
    
    // returns type_name of the user according to the given type_id
    public function getUserTypeName($user_type_id) {
        $this->database->query("SELECT `type_name` FROM `user-type` WHERE `type_id`=:type_id");
        $this->database->bind(':type_id', $user_type_id);
        return $this->database->nextResult()['type_name'];
    }
    
    public function getUserTypeInfo($user_type_id) {
        $this->database->query("SELECT * FROM `user-type` WHERE `type_id`=:type_id");
        $this->database->bind(':type_id', $user_type_id);
        return $this->database->nextResult();
    }
    
    public function getPendingUserRequests() {
        $db = $this->database;
        $db->query("SELECT * FROM `users` WHERE `user_type_id`<0");
        return $db->resultset();
        
    }
    
    public function update($user_card_number, $column_to_update, $update_value) {
        $this->database->query("UPDATE `users` SET `". $column_to_update . "`=:update_value WHERE `user_card_number`=:card_number");
        $this->database->bind(":update_value", $update_value);
        $this->database->bind(":card_number", $user_card_number);
        $this->database->execute();
    }
    
    public function returnUserInfo($card_number) {
        $this->database->query("SELECT * FROM users WHERE user_card_number= :card_num");
        $this->database->bind(':card_num', $card_number);
        return $this->database->nextResult();
    }
    
    // returns all approved user records
    public function returnAllUsers($user_type_id="") {
        $db = $this->database;
        $sql="";
        if (empty($user_type_id)) {
            $sql = "SELECT * FROM users WHERE `user_type_id`>=0";
        } else {
            if ($user_type_id < 0) return;
            $sql = "SELECT * FROM users WHERE `user_type_id`=" . $user_type_id;
        }
                
        $db->query($sql);
        return $db->resultset();
        
    }
    
    public function getLibrarianPrivilege($librarian_id) {
        $db = $this->database;
        $db->query('SELECT * FROM `librarian-privilege` WHERE librarian_id=:user_type');
        $db->bind(':user_type', $librarian_id);
        return $db->nextResult();
    }
    
    public function setLibrarianPrivilege($librarian_id, $add_doc, $modify_doc, $remove_doc, $add_user, $modify_user, $remove_user) {
        $db = $this->database;
        //die($user_type_id . $add_doc . $modify_doc . $remove_doc . $add_user . $modify_user . $remove_user);
        // check if the record exists or if it is a new record
        $record = $this->getLibrarianPrivilege($librarian_id);
        
        if ($record) {
            // update
            $db->query("UPDATE `librarian-privilege` SET `add_doc`=:add_doc,`modify_doc`=:modify_doc,`remove_doc`=:remove_doc,`add_user`=:add_user,`modify_user`=:modify_user,`remove_user`=:remove_user WHERE `librarian_id`=:user_type");
        } else {
            // insert
            $db->query('INSERT INTO `librarian-privilege`(`librarian_id`, `add_doc`, `modify_doc`, `remove_doc`, `add_user`, `modify_user`, `remove_user`) VALUES (:user_type, :add_doc, :modify_doc, :remove_doc, :add_user, :modify_user, :remove_user)');
        }
        
        $db->bind(':user_type', $librarian_id);
        $db->bind(':add_doc', $add_doc);
        $db->bind(':modify_doc', $modify_doc);
        $db->bind(':remove_doc', $remove_doc);
        $db->bind(':add_user', $add_user);
        $db->bind(':modify_user', $modify_user);
        $db->bind(':remove_user', $remove_user);
        
        $db->execute();
        
        
    }
    
    public function getPrivilegesByType($user_type_id) {
        $db = $this->database;
        $db->query('SELECT * FROM `user-privileges` WHERE user_type_id=:user_type');
        $db->bind(':user_type', $user_type_id);
        return $db->nextResult();
    }
    
    public function setPrivilegesByType($user_type_id, $add_doc, $modify_doc, $remove_doc, $add_user, $modify_user, $remove_user) {
        $db = $this->database;
        //die($user_type_id . $add_doc . $modify_doc . $remove_doc . $add_user . $modify_user . $remove_user);
        // check if the record exists or if it is a new record
        $record = $this->getPrivilegesByType($user_type_id);
        
        if ($record) {
            // update
            $db->query("UPDATE `user-privileges` SET `add_doc`=:add_doc,`modify_doc`=:modify_doc,`remove_doc`=:remove_doc,`add_user`=:add_user,`modify_user`=:modify_user,`remove_user`=:remove_user WHERE `user_type_id`=:user_type");
        } else {
            // insert
            $db->query('INSERT INTO `user-privileges`(`user_type_id`, `add_doc`, `modify_doc`, `remove_doc`, `add_user`, `modify_user`, `remove_user`) VALUES (:user_type, :add_doc, :modify_doc, :remove_doc, :add_user, :modify_user, :remove_user)');
        }
        
        $db->bind(':user_type', $user_type_id);
        $db->bind(':add_doc', $add_doc);
        $db->bind(':modify_doc', $modify_doc);
        $db->bind(':remove_doc', $remove_doc);
        $db->bind(':add_user', $add_user);
        $db->bind(':modify_user', $modify_user);
        $db->bind(':remove_user', $remove_user);
        
        $db->execute();
        
    }
    
    public function viewModal($user_data, $allow_input, $requesting_user_info) {
        $user = new User();
        
        // if one user is requesting other user's info
        if ($user_data['user_card_number'] != $requesting_user_info['user_card_number']) {
            // check privilege
            if ($user->getPrivilegesByType($requesting_user_info['user_type_id'])['modify_user']==0) {
                $this->error = 'You are not authorized to access information of other users. Kindly contact a librarian or the admin to see if it possible to get the information you are looking for.';
                return;
            }
        }
        
        
        $user_data['user_type_name'] = "";
        if ($user_data['user_type_id']<0) {
            $user_data['user_type_name'] = ucwords($this->getUserTypeName($user_data['user_type_id']*(-1)));
        } else {
            $user_data['user_type_name'] = ucwords($this->getUserTypeName($user_data['user_type_id']));
        }
        
        echo '
        <!-- USER PROFILE LOGIN MODAL -->
<style>
    #user-profile-modal {
        display: block; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 997; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        overflow: auto;
        text-align: left;
        -webkit-animation: animatezoom 0.6s;
        animation: animatezoom 0.6s
    }
    
    @-webkit-keyframes animatezoom {
        from {-webkit-transform: scale(0)}
        to {-webkit-transform: scale(1)}
    }
    
    @keyframes animatezoom {
        from {transform: scale(0)}
        to {transform: scale(1)}
    }
    
    #user-profile-modal::-webkit-scrollbar {
        width:0;
    }
    
    #user-modal-content {
        position: relative;
        background-color: #fefefe;
        margin: 60px auto 15%; /* 5% from the top, 15% from the bottom and centered */
        border: 1px solid #888;
        border-radius: 12px;
        padding: 18px 18px 0;
        width: 500px; /* Could be more or less, depending on screen size */
    }

    /* The Close Button (x) */
    #modal-close {
        color: #000;
        text-align: right;
        font-weight: bold;
        opacity: 0.8;
        position: absolute;
        right: 16px;
    }
    
    #modal-close:hover {
        cursor: pointer;
        font-size: 15px;
        opacity: 1;
    }
    
    #user-modal-content img {
        display: block;
        margin: 25px auto;
        border-radius: 50%;
    }
    
    #profile-fields {
        margin: 25px 25px 0;
        padding: 8px 16px 5px;
    }
    
    #profile-fields i {
        margin-right: 6px;
    }
    
    #profile-fields label {
        display: block;
        margin:0 0 5px;
    }
    
    #profile-fields input {
        padding: 6px;
        width: 100%;
        text-align: center;
        background-color: transparent;
        border:none;
    }
    
    #profile-fields input:nth-child(even) {
        background-color: #f2f2f2;
    }
    
    #user-modal-content input[type=submit] {
        display: block;
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        margin: 20px auto;
        border: none;
        border-radius: 5%;
        cursor: pointer;
    }
    
    #user-modal-content input[type=submit]:hover {
        background-color: #fff;
        color: #000;
        outline: 2px solid black;
    }

</style>
<div id="user-profile-modal">
<form id="user-modal-content" action="' . $_SERVER["PHP_SELF"] .'" method="post">
    <div onclick="document.getElementById(\'user-profile-modal\').style.display=\'none\'" id="modal-close" title="Close Modal">
        Close<i class="far fa-times-circle" style="margin-left: 3px;"></i>
    </div>
    
    <p style="position: absolute; font-size: 16px;">
        <i class="fas fa-tags"></i>&nbsp;&nbsp;' . $user_data['user_type_name'] .'
    </p>

    <img src="img/login_avatar.png" width=150px; alt="Avatar" class="avatar" />

    <div id="profile-fields">     
        <p>
        <label><i class="fas fa-user-circle"></i><b>Name</b></label>
        <input type="text" value="' . $user_data['name'] .'" name="name" required disabled>
        </p>
        
        <p>
        <label><i class="far fa-envelope"></i><b>Email</b></label>
        <input type="text" value="' . $user_data['email'] .'" name="email" required disabled>
        </p>
        
        <p>
        <label class="will-hide password-personal" hidden><i class="fas fa-key"></i><b>Password</b>
        <span style="font-family:Calibri; font-size:14px; margin-bottom: 4px; display: block;">Password won\'t change if left empty.</span>
        </label>
        <input type="password" class="will-hide password-personal" placeholder="Enter new password" name="password" disabled hidden>
        </p>
        
        <p>
        <label class="will-hide password-personal" hidden><i class="fas fa-key"></i><b>Repeat Password</b></label>
        <input type="password" class="will-hide password-personal" placeholder="Repeat new password" name="password" disabled hidden>
        </p>
        
        <p>
        <label><i class="fas fa-id-card"></i><b>Library Card Number</b></label>
        <input type="text" name="user_card_number" value="' . $user_data['user_card_number'] .'" required disabled>
        <input type="text" name="original_user_card_number" style="display:none;" value="' . $user_data['user_card_number'] .'" disabled>
        </p>
        
        <p>
        <label><i class="fas fa-phone"></i><b>Phone</b></label>
        <input type="text" name="phone_number" value="' . $user_data['phone_number'] .'" required disabled>
        </p>
        
        <p>
        <label><i class="fas fa-address-book"></i><b>Address</b></label>
        <input type="text" name="address" value="' . $user_data['address'] .'" required disabled>
        </p>
        
        <p>
        <label><i class="far fa-calendar-alt"></i><b>Date Registered</b></label>
        <input type="text" name="date_registered" value="' . $user_data['date_registered'] .'" required disabled>
        </p>
        
        <p>
        <label><i class="fas fa-user"></i><b>Account Type</b></label>
        <input type="text" name="user_type" value="' . $user_data['user_type_name'] .'" required disabled>
        </p>
        
    </div>
    
    <input type="submit" class="will-hide" name="btn-update-user" value="Update" hidden />
    
</form>
</div>

<script>
    var profile_modal = document.getElementById(\'user-profile-modal\');
    var disabled_inputs = profile_modal.getElementsByTagName(\'input\');
    var hidden_items = profile_modal.getElementsByClassName(\'will-hide\');
    
    
    // Get the <span> element that closes the modal
    var closer = document.getElementById("modal-close");

    // When the user clicks on <span> (x), close the modal
    closer.onclick = function() {
        profile_modal.style.display = "none";
        
    }

    /* When the user clicks anywhere outside of the modal, close it
    document.onclick = function(event) {
        if (event.target == profile_modal) {
            profile_modal.style.display = "none";
        }
    }
    */
        for(i=0; i < disabled_inputs.length; i++){
            if (' . $allow_input . ') {
                disabled_inputs[i].disabled = false;
            } else {
                disabled_inputs[i].disabled = true;
            }
        }
        
        for(i=0; i < hidden_items.length; i++){
            if ('. $allow_input . ') {
                hidden_items[i].hidden = false;
            } else {
                hidden_items[i].hidden = true;
            }
        }
        
    ';
    
    if ($this->getPrivilegesByType($requesting_user_info['user_type_id'])['modify_user'] == 0) {
        echo "document.getElementsByName('user_card_number')[0].disabled = true;";
        echo "document.getElementsByName('date_registered')[0].disabled = true;";
        echo "document.getElementsByName('user_type')[0].disabled = true;";
    }
    
    if ($user_data['user_card_number'] != $requesting_user_info['user_card_number']) {
        echo "
            var password_related = profile_modal.getElementsByClassName('password-personal');
            for (k=0; k < password_related.length; k++) {
                password_related[k].hidden = true;
            }
        ";
    }
    echo '
    
    
</script>
        ';
    }

}

?>