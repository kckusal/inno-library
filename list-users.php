<?php
require_once 'config.php';
require_once 'load-classes.php';

$query_string = $_SERVER['QUERY_STRING'];
parse_str($query_string, $query_arr);

if (isset($_SESSION['execute-query'])) {
    $user = new User();
    if ($_SESSION['execute-query']['action']=="explore") {
        $user->viewModal($user->returnUserInfo($_SESSION['execute-query']['id']), false, $_SESSION['user-data']);
    }
    
    if ($_SESSION['execute-query']['action']=="update") {
        $user->viewModal($user->returnUserInfo($_SESSION['execute-query']['id']), true, $_SESSION['user-data']);
    }
    
    if ($_SESSION['execute-query']['action']=="delete") {
        if ($user->getPrivilegesByType($_SESSION['user-data']['user_type_id'])['remove_user'] == 0) {
            $user->setError("You currently do <strong>not</strong> have the permission to <strong>remove</strong> other users of this library.");
        } else {
            $user->remove($_SESSION['execute-query']['id']);
            (new Message('error', 'User Account Removed!', "You've successfully removed the user with library card number -> " . $_SESSION['execute-query']['id'] . " from the database."))->display();
        }
    }
    
    if ($user->getError()) {
        $_SESSION['msg'] = $user->getError();
        header('Location: ' . ROOT_URL . 'dashboard.php');
        exit;
    }
    unset($_SESSION['execute-query']);
}
if (!empty($query_arr)) {
    $_SESSION['execute-query'] = $query_arr;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}



?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo $_SESSION['user-data']['name']; ?> | Profile Information</title>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<?php require_once 'styles.php'; ?>

<style>

.w3-container {
    text-align: left;
    font-family: "Arial";
    font-size: 15.5px;
    margin-top: 30px;
    padding:0;
}    

#container > h4 {
    text-align: center;
}

#container > p {
    text-align: left;
    padding-top: 20px;
}


.table-user-list-title {
    display: block;
    font-family: sans-serif;
    font-size: 16px;
    text-transform: uppercase;
    font-weight: bold;
    color: rgb(7, 41, 77);
    margin: 35px 0 0;
}

.table-user-list {
    border-collapse: collapse;
    width: 100%;
    font-size: 14px;
    margin: 10px 0;
    overflow: auto;
    text-align: center;
    outline: 1px solid black;
}


.table-user-list th, tr, td {
    height: 40px;
    padding: 8px 10px;
}

/* First row containing table heading selection */
.table-user-list th {
    top: 2px;
    background: #AACCB0
}


.table-user-list tr:nth-child(even) {
    background-color: #f2f2f2;
}

.table-user-list th:nth-child(1), td:nth-child(1) {
    width: 1px;
}

.table-user-list th > i {
    margin: 0 5px;
}

.table-user-list tr:hover {
    outline: 2px solid black;
    outline-color: teal;
    cursor: pointer;
}

.table-user-list .user-type {
    padding: 0 4px;
    color: white;
    border-radius: 4px;
    width: 5px;
}

.btn-manage-profile {
    padding: 3px 5px;
    margin:0;
}

.btn-manage-profile:hover {
    text-decoration: none;
    color: white;
    background-color: black;
    border-radius: 4px;
}

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

</head>
<body>
<?php require_once 'header.php'; ?>

<div class="w3-container">
    <h4><strong>USERS</strong> of Innopolis Online Library Management Portal</h4>
    <p>As a <strong>librarian</strong>, you are able to see all the registered users as well as new applicants of this library system.</p>

    <div class=" table-user-list-title">
        <span class="w3-show-inline-block"><span style="font-size:20px;">&#8658;</span>&nbsp;All Registered Users [Librarians & Patrons]</span>
        <div class="w3-show-inline-block w3-right">
            <input type="text" class="input-search-table" onkeyup='searchTable("table-users-all", this.value)' placeholder="Search in this table..." title="Type to search in the users table to follow.">
        </div>
    </div>
    
    <table id="table-users-all" class="table-user-list" style="width: 100%;">
            <tr>
                <th width=1%>S.N.</th>
                <th width=15%>Name<i class="fas fa-user-circle"></i></th>
                <th width=18% class="w3-hide-small w3-hide-medium">Email<i class="far fa-envelope"></i></th>
                <th width=40% class="w3-hide-small">Currently Booked Document(s)</th>
                <th width=15% class="w3-hide-small w3-hide-medium">User Type<i class="fas fa-user"></i></th>
                <th width=15%>Actions</th>
            </tr>
        <?php
            $user = new User();
            $records = $user->returnAllUsers();
            $doc = new Document();
            
            $i = 1;
            foreach ($records as $row) {
                echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td class=\"w3-hide-small w3-hide-medium\">" . $row['email'] . "</td>";
                    
                    echo '<td class="w3-hide-small">';
                        $booking = new Booking();
                        $user_booking = $booking->getCurrentBookings($row['user_card_number']);
                        
                        if (empty($user_booking)) {
                            echo "&mdash;";
                        } else {
                            foreach ($user_booking as $curr_record) {
                                $title = '<i class="fas fa-caret-right"></i>&nbsp;&nbsp;' . $doc->getDocTitleByItemId($doc->getDocTypeIdByDocItemId($curr_record['item_id']), $curr_record['item_id']);
                                $overdue=' |&nbsp;';
                                $due_date = $booking->calculateDueDate($curr_record['booking_log_id']);
                                if ($due_date > date('Y-m-d')) {
                                    $overdue .= '<strong>Due</strong>: ' . $due_date . '<br />';
                                } else {
                                    $title = '<span style="float: left;">' . $title . '</span>';
                                    $overdue = '<br/><span style="margin-left: 2px;">&#x21AA; <strong style="color: red;">OverDue</strong> by ' . $booking->formatDateDifference($due_date, date('Y-m-d')) . ' | Expected Fine = ' . $booking->calculateFine($due_date) . ' <em>Rubles</em></span>';
                                }
                                echo $title.$overdue;
                            }
                        }
                    echo "</td>";
                    
                    echo '<td class="w3-hide-small w3-hide-medium">'. ucwords($user->getUserTypeName($row['user_type_id'])) .'</td>';
                    
                    echo '<td><a class="btn-manage-profile" title="Click to see user\'s full profile." href="' . $_SERVER['PHP_SELF'] . '?action=explore&id='. $row['user_card_number'] . '"><i class="fas fa-info-circle"></i></a>&nbsp;
                            <a class="btn-manage-profile" title="Click here to remove this user." href="' . $_SERVER['PHP_SELF'] . '?action=delete&id='. $row['user_card_number'] . '"><i class="far fa-trash-alt"></i></a>&nbsp;
                            <a class="btn-manage-profile" title="Click to update user info." href="' . $_SERVER['PHP_SELF'] . '?action=update&id='. $row['user_card_number'] . '"><i class="far fa-edit"></i></a>
                            </td>';                                
                echo "</tr>";
                
                $i += 1;
            }

        ?>

    </table>
    
<?php
    $user = new User();
    $user_types = $user->individualTypes();
    foreach ($user_types as $userType) {
        if ($userType['type_id']==0) continue;
        echo '<span class="table-user-list-title"><span style="font-size:20px;">&#8658;</span>&nbsp;'. ucwords($userType['type_name']) .'S</span>';
        echo '<table class="table-user-list">
                <tr>
                    <th>S.N.</th>
                    <th>Name<i class="fas fa-user-circle"></i></th>
                    <th>Email<i class="far fa-envelope"></i></th>
                    <th>Library Card No.<i class="fas fa-id-card"></i></th>
                    <th>Date Registered<i class="far fa-calendar-alt"></i></th>
                    
                    <th>Actions</th>
                </tr>';

        $records = $user->returnAllUsers($userType['type_id']);
                
        $i = 1;
        foreach ($records as $row) {
            echo "<tr>";
            echo "<td>" . $i . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['user_card_number'] . "</td>";
            echo "<td>" . $row['date_registered'] . "</td>";
            
            echo '<td><a class="btn-manage-profile" title="Click to see user\'s full profile." href="' . $_SERVER['PHP_SELF'] . '?action=explore&id='. $row['user_card_number'] . '"><i class="fas fa-info-circle"></i></a>&nbsp;
                                <a class="btn-manage-profile" title="Click here to remove this user." href="' . $_SERVER['PHP_SELF'] . '?action=delete&id='. $row['user_card_number'] . '"><i class="far fa-trash-alt"></i></a>&nbsp;
                                <a class="btn-manage-profile"  title="Click to update user info." href="' . $_SERVER['PHP_SELF'] . '?action=update&id='. $row['user_card_number'] . '"><i class="far fa-edit"></i></a>
                                </td>';                                
            echo "</tr>";
            $i += 1;
        }

        echo '</table>';
    }
?>

</div>

<?php require_once 'footer.php'; ?>

<script>
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

</script>

</body>
</html>