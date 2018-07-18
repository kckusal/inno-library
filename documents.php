<?php
require_once 'config.php';
require_once 'load-classes.php';

if (!isset($_SESSION['is-logged-in'])) {
    header('Location: '.ROOT_URL.'login.php');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Documents in Library</title>

    <?php include_once 'styles.php'; ?>
    <style>
    .doc-table {
        outline: darkgrey 2px solid;
        max-height: 500px;
    }

    .doc-table th, tr, td {
        padding: 15px;
        outline: darkgrey 1px solid;
    }

    .record-heading {
        height: 10px;
    }


    </style>
</head>
<body>
<?php require_once 'header.php'; ?>

<!-- BOOKS in the library in the order they were added -->
<div class="center-text" style="margin: 20px auto; width:80%; padding-top:45px;">
    <h2>Books in Library</h2>
    <p>The following table shows the <strong>book</strong> items available in library.</p>
    <table class="doc-table">
        <tr class="record-heading">
            <th>Book Title</th>
            <th width=25%>Author(s)</th>
            <th width=10%>Edition(s)</th>
            <th width=15%>Publisher's Name</th>
            <th width=5%>Copies<sub><strong>&nbsp;TOTAL</strong></sub></th>
            <th width=5%>Copies<sub><strong>&nbsp;CHECKED&nbsp;OUT</strong></sub></th>
        </tr>
        <tr>
        <?php
            $sql_books = "SELECT * FROM books ORDER BY book_title ASC";
            if ($books_result=mysqli_query($connect, $sql_books)) {
                while ($books_row = mysqli_fetch_row($books_result)) {
                    echo "<td>" . $books_row[1] . "</td>";

                    echo "<td>";
                    $output_td="";
                    $authors_by_id = explode(',', $books_row[2]);
                    for ($i=0; $i<count($authors_by_id); $i++) {
                        $authors_sql = "SELECT * FROM authors WHERE author_id='" . $authors_by_id[$i] . "'";
                        $authors_row = mysqli_fetch_row(mysqli_query($connect, $authors_sql));
                        $output_td = $output_td . $authors_row[1] . ", ";
                    }
                    echo substr($output_td, 0, count($output_td)-3) . "</td>";
                    echo "</td>";

                    echo "<td>";
                    $items_by_id = explode(',', $books_row[3]);
                    for ($i=0; $i<count($items_by_id); $i++) {
                        $edition_info_sql = "SELECT DISTINCT edition_number FROM 'book-edition-info' WHERE item_id='" . $items_by_id[$i] ."'";

                    }
                    echo "</td>";

                    $sql_items = "SELECT * FROM items WHERE ";
                    echo "<td>" . "Publisher" . "</td>";
                    echo "<td>" . $books_row[3] . "</td>";
                    echo "<td>" . "No." . "</td>";
                }
            }
        ?>
        </tr>
        <?php
        //$book_info = "SELECT * FROM users WHERE email='".$user_name."' AND password='".$password."'";
        //$item_copy_info = "SELECT * FROM users WHERE email='".$user_name."' AND password='".$password."'";


        ?>
        <th></th>
    </table>
</div>

<?php require_once 'footer.php'; ?>
<?php require_once 'javascript.php'; ?>
</body>
</html>