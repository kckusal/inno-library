<?php

class Document {
    private $db;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public function listAuthors() {
        $this->db->query("SELECT author_name FROM authors ORDER BY author_name ASC");
        $this->db->execute();
        return $this->db->resultset();
    }
    
    public function getAuthorsByDocTypeAndId($doc_type_id, $doc_id) {
        $db = $this->db;
        $db->query("SELECT `author_id` FROM `doc-authors` WHERE `doc_type_id`=:type_id AND `doc_table_id`=:doc_id");
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':doc_id', $doc_id);
        
        $auth_ids = $db->resultset();
        $result = array();
        foreach ($auth_ids as $id) {
            $db->query("SELECT `author_id`, `author_name` FROM `authors` WHERE `author_id`=:auth_id");
            $db->bind(':auth_id', $id['author_id']);
            $temp_result = $db->nextResult();
            $result[$temp_result['author_id']] = $temp_result['author_name'];
        }
        return $result;
    }
    
    public function countCopies($doc_type_id, $doc_id) {
        $db = $this->db;
        $db->query("SELECT COUNT(*) FROM `doc-items` WHERE doc_type_id=:type_id AND doc_table_id=:doc_id");
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':doc_id', $doc_id);
        return $db->nextResult()['COUNT(*)'];
    }
    
    public function countBookedCopies($doc_type_id, $doc_id) {
        $db = $this->db;
        $db->query("SELECT COUNT(*) FROM `doc-items` WHERE doc_type_id=:type_id AND doc_table_id=:doc_id AND is_currently_checked_out=1");
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':doc_id', $doc_id);
        return $db->nextResult()['COUNT(*)'];
    }
    
    public function countReferenceCopies($doc_type_id, $doc_id) {
        $db = $this->db;
        $db->query("SELECT COUNT(*) FROM `doc-items` WHERE doc_type_id=:type_id AND doc_table_id=:doc_id AND is_reference_copy=1");
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':doc_id', $doc_id);
        return $db->nextResult()['COUNT(*)'];
    }
    
    public function returnBookEditionInfo($book_id) {
        $db = $this->db;
        $db->query("SELECT * FROM `book-edition-info` WHERE `book_id`=:book");
        $db->bind(':book', $book_id);
        return $db->resultset();
    }
    
    public function returnDocuments($doc_type) {
        $db = $this->db;
        
        switch ($doc_type) {
            case "books":
                $db->query("SELECT * FROM `books`");
                return $db->resultset();
                
            case "journal-articles":
                $db->query("SELECT * FROM `journal-articles`");
                return $db->resultset();
            case "av-materials":
                $db->query("SELECT * FROM `av-materials`");
                return $db->resultset();
        }
        
    }
    
    // removes one copy of a document at a time.
    public function removeCopy($doc_id) {
        $db = $this->db;
        $db->query("DELETE FROM `doc-items` WHERE doc_table_id=:doc_id LIMIT 1");
        $db->bind(':doc_id', $doc_id);
        $db->execute();
    }
    
    /* Assumes that an array containing info is passed.
    
        -- get type_table using doc_type id
        type_table (books, journal-articles, or av-items)
        
        -- Check in that table, if exists already, else insert into that table
        if book, insert title, is_best_seller
            insert into book-edition-info if book already doesn't exist.
        if journal, insert title, journal_name, issue, editor, date
        if av, insert title
        Then get id
        
        -- store authors if not already in authors table
        authors
        insert into doc-authors: type_id, book_id, author_id
        
        -- Insert items first in doc-items table
        doc-type-id
        title
        number of copies
        storage location
        
    */
    public function addDocuments($document_info) {
        // with typeid, we know which table to look.
        $type_table = $this->typeTableNameById($document_info['doc-type-id']);
        
        $doc_table_id = $this->documentExists($document_info['title'], $type_table);
        
        // If the document doesn't exist already in the library, add it.
        if ($doc_table_id == -1) {
            switch ($type_table) {
                case "books":
                    $this->db->query("INSERT INTO `books`(`title`, `keywords`) VALUES (:title, :keywords)");
                    $this->db->bind(':title', $document_info['title']);
                    $this->db->bind(':keywords', $document_info['keywords']);
                    $this->db->execute();
                    $doc_table_id = $this->db->lastInsertId();
                    break;
                    
                case "journal-articles":
                    $this->db->query("INSERT INTO `journal-articles`(`title`, `journal_name`, `issue_name`, `issue_editor`, `issue_publication_date`, `keywords`) VALUES (:title, :journal_name, :issue_name, :issue_editor, :issue_pub_date, :keywords)");
                    $this->db->bind(':title', $document_info['title']);
                    $this->db->bind(':journal_name', $document_info['journal-name']);
                    $this->db->bind(':issue_name', $document_info['journal-issue-name']);
                    $this->db->bind(':issue_editor', $document_info['journal-issue-editor']);
                    $this->db->bind(':issue_pub_date',$document_info['journal-issue-publication-date']);
                    $this->db->bind(':keywords', $document_info['keywords']);
                    $this->db->execute();
                    $doc_table_id = $this->db->lastInsertId();
                    break;
                    
                case "av-materials":
                    $this->db->query("INSERT INTO `av-materials`(`title`, `keywords`) VALUES (:title, :keywords)");
                    $this->db->bind(':title', $document_info['title']);
                    $this->db->bind(':keywords', $document_info['keywords']);
                    $this->db->execute();
                    $doc_table_id = $this->db->lastInsertId();
                    
                    break;
            }
            
            // add authors if not already in table
            $authors = array_map('trim', explode(';', $document_info['authors']));
            
            foreach ($authors as $author) {
                $author_id = $this->addAuthor($author);   
                $this->db->query("INSERT INTO `doc-authors`(`doc_type_id`, `doc_table_id`, `author_id`) VALUES (:doc_type_ID, :doc_table_ID, :author_ID)");
                $this->db->bind(':doc_type_ID', $document_info['doc-type-id']);
                $this->db->bind(':doc_table_ID', $doc_table_id);
                $this->db->bind(':author_ID', $author_id);
                $this->db->execute();
            }
        }
        
        // if book-edition info is not in the table, add it
        if ($type_table == "books") {
            if (!($this->bookEditionInfoExists($doc_table_id, $document_info['book-edition-number']))) {
                $this->db->query("INSERT INTO `book-edition-info`(`book_id`, `edition_number`, `publisher_name`, `publication_date`) VALUES (:book_id,:edition,:publisher,:publication)");
                $this->db->bind(':book_id', $doc_table_id);
                $this->db->bind(':edition', $document_info['book-edition-number']);
                $this->db->bind(':publisher', $document_info['book-publisher-name']);
                $this->db->bind(':publication', $document_info['book-published-date']);
                $this->db->execute();
            }
        }
        
        
        // add individual items to the doc-items table
        for ($i=0; $i<$document_info['count-copies']; $i++) {
            $this->db->query("INSERT INTO `doc-items`(`doc_type_id`, `doc_table_id`, `storage_location`, `is_reference_copy`) VALUES (:doc_type_ID,:doc_table_ID,:storage,:reference_copy)");
            $this->db->bind(':doc_type_ID', $document_info['doc-type-id']);
            $this->db->bind(':doc_table_ID', $doc_table_id);
            $this->db->bind(':storage', $document_info['storage-location']);
            $this->db->bind(':reference_copy', $document_info['are_reference_copies']);
            $this->db->execute();
            
        }
        
    }
    
    
    // insert author in the authors table if not exists already
    // return the id of the author just inserted
    private function addAuthor($author_name) {
        $this->db->query("SELECT `author_id` FROM `authors` WHERE `author_name` = :author_name");
        $this->db->bind(':author_name', $author_name);
        $author = $this->db->nextResult();
        
        if (empty($author)) {
            $this->db->query("INSERT INTO `authors`(`author_name`) VALUES (:author_name)");
            $this->db->bind(':author_name', $author_name);
            $this->db->execute();
            
            return $this->db->lastInsertId();
        }
        
        return $author['author_id'];
    }
    
    private function bookEditionInfoExists($book_id, $edition_num) {
        $this->db->query("SELECT * FROM `book-edition-info` WHERE book_id=:book_id AND edition_number=:edition");
        $this->db->bind(':book_id', $book_id);
        $this->db->bind(':edition', $edition_num);
        
        if ($this->db->nextResult()) {
            return true;
        } else {
            return false;
        }
    }
    
    // returns document's primary key ID if it exists otherwise returns -1
    private function documentExists($title, $table_name) {
        
        switch ($table_name) {
            case "books":
                $primary_key_col = "book_id";
                break;
            case "journal-articles":
                $primary_key_col = "article_id";
                break;
            case "av-materials":
                $primary_key_col = "av_id";
                break;
        }
        
        $this->db->query("SELECT `" . $primary_key_col . "` FROM `" . $table_name . "` WHERE title=:title" );
        $this->db->bind(':title', $title);
        
        $doc_id = $this->db->nextResult();
        if ($doc_id) {
            return $doc_id[$primary_key_col];
        } else {
            return -1;
        }
    }
    
    public function getDocTitleByDocId($type_id, $doc_table_id) {
        $tbl_name = $this->typeTableNameById($type_id);
        
        $sql = "SELECT `title` FROM `" . $tbl_name . "` WHERE `";
        
        switch ($type_id) {
            case 1:
                $sql .= "book_id";
                break;
            case 2:
                $sql .= "article_id";
                break;
            case 3:
                $sql .= "av_id";
        }
        
        $sql .= "`";
        $this->db->query($sql . "=:table_id");
        $this->db->bind(':table_id', $doc_table_id);
        return $this->db->nextResult()['title'];
        
    }
    
    private function typeTableNameById($type_id) {
        $this->db->query("SELECT type_table FROM `doc-types` WHERE type_id=:type_id");
        $this->db->bind(':type_id', $type_id);
        return $this->db->nextResult()['type_table'];
    }
    
    public function getDocTypeByTypeId($type_id) {
        $db = $this->db;
        $db->query("SELECT `type_name` FROM `doc-types` WHERE `type_id` = :type_id");
        $db->bind(':type_id', $type_id);
        return $db->nextResult()['type_name'];
    }
    
    public function getDocTypeIdByDocItemId($doc_id) {
        $db = $this->db;
        $db->query("SELECT `doc_type_id` FROM `doc-items` WHERE `item_id` = :item_id");
        $db->bind(':item_id', $doc_id);
        return $db->nextResult()['doc_type_id'];
    }
    
    public function getDocTableIdByDocItemId($doc_id) {
        $db = $this->db;
        $db->query("SELECT `doc_table_id` FROM `doc-items` WHERE `item_id` = :item_id");
        $db->bind(':item_id', $doc_id);
        return $db->nextResult()['doc_table_id'];
    }
    
    public function getDocTitleByItemId($type_id, $item_id) {
        $tbl_name = $this->typeTableNameById($type_id);
        
        $this->db->query("SELECT `doc_table_id` FROM `doc-items` WHERE `item_id`=:item_id");
        $this->db->bind(':item_id', $item_id);
        $doc_table_id = $this->db->nextResult()['doc_table_id'];
        
        $sql = "SELECT `title` FROM `" . $tbl_name . "` WHERE `";
        
        switch ($type_id) {
            case 1:
                $sql .= "book_id";
                break;
            case 2:
                $sql .= "article_id";
                break;
            case 3:
                $sql .= "av_id";
        }
        
        $sql .= "`";
        $this->db->query($sql . "=:table_id");
        $this->db->bind(':table_id', $doc_table_id);
        return $this->db->nextResult()['title'];    
    }
    
    
    public function returnTypes() {
        $this->db->query("SELECT * FROM `doc-types`");
        $this->db->execute();
        return $this->db->resultset();
    }
    
    public function getNonReferenceCopies($doc_type_id, $doc_table_id) {
        $db = $this->db;
        $db->query("SELECT * FROM `doc-items` WHERE `doc_type_id` = :type_id AND `doc_table_id` = :id_in_table AND `is_reference_copy`=0");
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':id_in_table', $doc_table_id);
        
        return $db->resultset();
    }
    
    public function getCurrentlyBookedCopies($doc_type_id, $doc_table_id) {
        $db = $this->db;
        $db->query("SELECT * FROM `doc-items` WHERE `doc_type_id` = :type_id AND `doc_table_id` = :id_in_table AND `is_currently_checked_out`=1");
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':id_in_table', $doc_table_id);
        
        return $db->resultset();
    }
    
    public function getBookableDocItems($doc_type_id, $doc_table_id) {
        $db = $this->db;
        $db->query("SELECT * FROM `doc-items` WHERE `doc_type_id` = :type_id AND `doc_table_id` = :id_in_table AND `is_reference_copy`=0 AND `is_currently_checked_out`=0");
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':id_in_table', $doc_table_id);
        
        return $db->resultset();
    }

    public function searchByDocTitleAndKeywords($search_str) {
        $db = $this->db;
        
        // get all the doc-types and tables to search
        $doc_type_tables=array();
        
        foreach ($this->returnTypes() as $type) {
            $doc_table = array();
            $doc_table['type_table'] = $type['type_table'];
            $doc_table['type_name'] = $type['type_name'];
            array_push($doc_type_tables, $doc_table);
        }

        $result = array();
        // look in the doc of each type
        foreach ($doc_type_tables as $table) {
            $db->query("SELECT `title` FROM `". $table['type_table'] . "` WHERE `title` LIKE '%" . $search_str . "%' OR `keywords` LIKE '%" . $search_str . "%'");
            foreach ($db->resultset() as $res) {
                $temp_result = array();
                $temp_result['doc-type'] = $table['type_name'];
                $temp_result['title'] = $res['title'];
                array_push($result, $temp_result);
            }
        }
        
        return $result;
    }
    
    public function docInfoModal($doc_type_id, $doc_table_id) {
        $title = $this->getDocTitleByDocId($doc_type_id, $doc_table_id);
        
        // modal top bar that includes title and/or common property of every doc type
        echo '
            <style>
                .w3-modal-content {
                    border-radius: 4px !important;
                }
            </style>
            <div id="doc-info-modal" class="w3-modal" style="display: block;">
                <div class="w3-modal-content w3-round">
                    <div class="w3-button w3-hover-none w3-display-topright w3-right w3-xlarge" onclick="document.getElementById(\'doc-info-modal\').style.display=\'none\'"><i class="far fa-times-circle"></i></div>
                    <div class="w3-margin-top w3-padding"><h3>' . $title . '</h3></div>
                    <div class="w3-container w3-padding w3-left-align" style="font: Arial; font-size: 15.5px;">
                        <div class="w3-left"><img src="img/document-icon.png" alt="Document Icon" width=100px;/></div>
                        <div><strong>Type</strong>: '. $this->getDocTypeByTypeId($doc_type_id) .'</div>
        ';
        
        // echo modal content here which are properties specific to each doc type
        echo '
            
        ';
        
        // closing tags of the modal title
        echo '
                    </div>
                </div>
            </div>
        ';
    }
}

?>
