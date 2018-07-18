<?php
require_once 'config.php';
require_once 'load-classes.php';

$user = new User();
$msg = "";

// Check if this is a librarian and has privilege to do so.
if ($_SESSION['user-data']['user_type_id'] == 1 and $user->getLibrarianPrivilege($_SESSION['user-data']['user_card_number'])['modify_doc'] == 0) {
    $_SESSION['msg'] = "Sorry! You are currently not authorized to view this page.";
    header('Location: ' . ROOT_URL . 'dashboard.php');
    exit;
}

$query_string = $_SERVER['QUERY_STRING'];
parse_str($query_string, $query_arr);

if (!empty($query_arr)) {
    $_SESSION['execute-doc-query'] = $query_arr;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['execute-doc-query'])) {
    $execute_info = $_SESSION['execute-doc-query'];
    unset($_SESSION['execute-doc-query']);
    
    $doc = new Document();
    
    switch ($execute_info['subject']) {
        case "book":
            $doc_type_id = 1;
            break;
        case "journal-article":
            $doc_type_id = 2;
            break;
        case "av-material":
            $doc_type_id = 3;
            break;
    }
    
    switch ($execute_info['action']) {
        case "explore":
            if ($_SESSION['user-data']['user_type_id'] == 1 and $user->getLibrarianPrivilege($_SESSION['user-data']['user_card_number'])['modify_doc'] == 0) {
                $msg = "Sorry! You are not authorized to view the information of this document.";
            } else {
                $doc->docInfoModal(1, $execute_info['id']);
            }
            break;
        case "update":
            break;
        case "delete":
            if ($_SESSION['user-data']['user_type_id'] == 1 and $user->getLibrarianPrivilege($_SESSION['user-data']['user_card_number'])['remove_doc'] == 0) {
                $msg = "Sorry! You are not authorized to remove any copies of this document.";
            } else {
                $doc->removeCopy($execute_info['id']);
                (new Message('error', '1 Copy of this Document Removed', '<strong>One</strong> copy of this document has been removed from the database.'))->display();
            }
        
    }
    
}

if ($msg) {
    (new Message('error', 'Action Results', $msg))->display();
}


?>

<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="Kusal KC">

    <title>View Documents | Inno-Library</title>

    <?php require_once 'styles.php'; ?>
    
    <style>
    #body-view {
        text-align: left;
        font-family: "Arial";
        font-size: 15.5px;
    }
    
    #body-view > h4 {
        text-align: center;
        margin-bottom: 16px;
    }
    
    .list-container {
        margin: 0px 0 40px;
    }
    
    .list-container>h5{
        margin: 12px 0;
    }
    
    .list-container table {
        font-family: "Arial", Verdana, sans-serif;
        
        font-size: 14.5px;
        text-align: center;
    }
    
    .list-container table td, th {
        border: 1px solid #ddd;
        padding: 8px;
    }
    
    .list-container table tr:nth-child(even){background-color: #f2f2f2;}

    .list-container table tr:hover {background-color: #ddd;}

    .list-container table th {
        padding-top: 12px;
        padding-bottom: 12px;
        background-color: #4CAF50;
        color: white;
    }
    
    .list-container table .actions i {
        padding: 4px;
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
<div id="body-view" class="w3-margin-top">
    <h4>View Documents in the Library</h4>
    <p>
        This page lists all the documents available in the library. The different types of documents available in this library system include <strong>books</strong>, <strong>journal-articles</strong>, and <strong>audio-video materials</strong>. A patron can only view the list below and see document details; however, a librarian can edit/update those details too.
    </p>    
    
    <div class="list-container">
        <h5 class="w3-xlarge w3-show-inline-block">Books</h5>
        <div class="w3-show-inline-block w3-right">
            <input type="text" class="input-search-table" onkeyup='searchTable("table-books", this.value)' placeholder="Search in books..." title="Type to search in Books table below.">
        </div>
        <table id="table-books">
            <tr>
                <th width=1%>S.N.</th>
                <th width=25%>Book Title</th>
                <th width=25%>Author(s)</th>
                <th width=4% class="w3-hide-small">Editions Available</th>
                <th width=10% class="">Total No. of Copies</th>
                <th width=15% class="w3-hide-small">No. of Copies currently booked</th>
                <th width=15% class="w3-hide-small w3-hide-medium">No. of Reference Copies</th>
                <th width=10% class="">&nbsp;&nbsp;&nbsp;Actions&nbsp;&nbsp;&nbsp;</th>
            </tr>
            
            <?php
                $i = 1;
                $doc = new Document();
                $books = $doc->returnDocuments("books");
                
                foreach ($books as $book) {
                    echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td>" . $book['title'] . "</td>";
                    
                    $authors = $doc->getAuthorsByDocTypeAndId(1, $book['book_id']);
                    $author_str='';
                    foreach ($authors as $author) {
                        $author_str = (!$author_str) ? $author : $author_str . ', ' . $author;
                    }
                    echo "<td>" . $author_str . "</td>";
                    
                    $editions = $doc->returnBookEditionInfo($book['book_id']);
                    $edition_str="";
                    foreach ($editions as $edition) {
                        $edition_str = (!$edition_str) ? $edition['edition_number'] : $edition_str . ", " . $edition['edition_number'];
                    }
                    echo '<td  class="w3-hide-small">' . $edition_str . '</td>';
                    
                    echo "<td>". $doc->countCopies(1, $book['book_id']) ."</td>";
                    echo "<td class=\"w3-hide-small\">". $doc->countBookedCopies(1, $book['book_id']) ."</td>";
                    echo '<td class="w3-hide-small w3-hide-medium">'. $doc->countReferenceCopies(1, $book['book_id']) ."</td>";
                    echo '<td class="actions">
                        <a href="' . $_SERVER['PHP_SELF'] . '?subject=book&action=explore&id='. $book['book_id'] .'" title="See details of this record."><i class="fas fa-info-circle"></i></a>
                    ';
                    
                    if ($_SESSION['user-data']['user_type_id'] == 1) {
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?subject=book&action=update&id='. $book['book_id'] .'" title="Update this record."><i class="far fa-edit"></i></a>';
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?subject=book&action=delete&id='. $book['book_id'] .'" title="Delete this record."><i class="far fa-trash-alt"></i></a>';
                    }
                        
                    echo '</td>';
                    echo "</tr>";
                    $i++;
                }
            ?>
        </table>
    </div>
    
    <div class="list-container">
        <h5 class="w3-show-inline-block w3-xlarge">Journal Articles</h5>
        <div class="w3-show-inline-block w3-right">
            <input type="text" class="input-search-table" onkeyup='searchTable("table-journal-articles", this.value)' placeholder="Search in Journal Articles..." title="Type to search in Journal Articles table below.">
        </div>
        <table id="table-journal-articles">
            <tr>
                <th>S.N.</th>
                <th>Article Title</th>
                <th>Journal Name</th>
                <th>Issue Name</th>
                <th>Issue Published Date</th>
                <th>Total No. of Copies</th>
                <th>No. of Copies currently booked</th>
                <th>No. of Reference Copies</th>
                <th>&nbsp;&nbsp;&nbsp;Actions&nbsp;&nbsp;&nbsp;</th>
            </tr>
            
            <?php
            $i = 1;
            $doc = new Document();
            $journals = $doc->returnDocuments("journal-articles");
            
            foreach ($journals as $article) {
                echo "<tr>";
                echo "<td>" . $i . "</td>";
                echo "<td>" . $article['title'] . "</td>";
                echo "<td>" . $article['journal_name'] . "</td>";
                echo "<td>" . $article['issue_name'] . "</td>";
                echo "<td>" . $article['issue_publication_date'] . "</td>";
                echo "<td>" . $doc->countCopies(2, $article['article_id']) . "</td>";
                echo "<td>" . $doc->countBookedCopies(2, $article['article_id']) . "</td>";
                echo "<td>" . $doc->countReferenceCopies(2, $article['article_id']) . "</td>";
                echo '<td class="actions">
                        <a href="' . $_SERVER['PHP_SELF'] . '?subject=journal-article&action=explore&id='. $article['article_id'] .'" title="See details of this record."><i class="fas fa-info-circle"></i></a>
                    ';
                    
                    if ($_SESSION['user-data']['user_type_id'] == 1) {
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?subject=journal-article&action=update&id='. $article['article_id'] .'" title="Update this record."><i class="far fa-edit"></i></a>';
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?subject=journal-article&action=delete&id='. $article['article_id'] .'" title="Delete this record."><i class="far fa-trash-alt"></i></a>';
                    }
                        
                echo '</td>';
                echo "</tr>";
                $i++;
            }
            
            ?>
        </table>
    </div>
    
    <div class="list-container">
        <h5 class="w3-show-inline-block w3-xlarge">Audio-Video Materials</h5>
        <div class="w3-show-inline-block w3-right">
            <input type="text" class="input-search-table" onkeyup='searchTable("table-av-materials", this.value)' placeholder="Search in AV section..." title="Type to search in Audio-video materials table below.">
        </div>
        <table id="table-av-materials" style="width: 100%;">
            <tr>
                <th>S.N.</th>
                <th>Audio/Video Title</th>
                <th>Total No. of Copies</th>
                <th>No. of Copies currently booked</th>
                <th>No. of Reference Copies</th>
                <th>&nbsp;&nbsp;&nbsp;Actions&nbsp;&nbsp;&nbsp;</th>
            </tr>
            
            <?php
            $i = 1;
            $doc = new Document();
            $avs = $doc->returnDocuments("av-materials");
            
            foreach ($avs as $av) {
                echo "<tr>";
                echo "<td>" . $i . "</td>";
                echo "<td>" . $av['title'] . "</td>";
                echo "<td>" . $doc->countCopies(3, $av['av_id']) . "</td>";
                echo "<td>" . $doc->countBookedCopies(3, $av['av_id']) . "</td>";
                echo "<td>" . $doc->countReferenceCopies(3, $av['av_id']) . "</td>";
                echo '<td class="actions">
                        <a href="' . $_SERVER['PHP_SELF'] . '?subject=av-material&action=explore&id='. $av['av_id'] .'" title="See details of this record."><i class="fas fa-info-circle"></i></a>
                    ';
                    
                    if ($_SESSION['user-data']['user_type_id'] == 1) {
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?subject=av-material&action=update&id='. $av['av_id'] .'" title="Update this record."><i class="far fa-edit"></i></a>';
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?subject=av-material&action=delete&id='. $av['av_id'] .'" title="Delete this record."><i class="far fa-trash-alt"></i></a>';
                    }
                        
                echo '</td>';
                echo "</tr>";
                $i++;
            }
            ?>
            
        </table>
    </div>
    
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