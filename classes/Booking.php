<?php
require_once 'Document.php';
class PriorityQueue extends SplPriorityQueue { 
    public function compare($priority1, $priority2) { 
        if ($priority1 === $priority2) return 0;
        // For max-heap implementation: return $priority1 < $priority2 ? -1 : 1;
        // For min-heap implementation, code below: 
        return $priority1 < $priority2 ? 1 : -1; 
    } 
}

class Booking {

    private $db;
    private $error;
    
    public function __construct() {
        $this->db = new DatabaseConnection();
    }       
    public function getError() {
        return $this->error;    
    }
    
    // adds booking record to the database
    public function addNewRecord($doc_type, $doc_title, $doc_author, $duration, $outstanding_request, $user_data) {
        //$this->error = "Book not available.";
        
        // find document item first
        $this->db->query("SELECT * FROM `doc-types` WHERE type_name= :doc_type");
        $this->db->bind(':doc_type', $doc_type);
        $doc_type_result = $this->db->nextResult();
        
        $doc_type_id = $doc_type_result['type_id'];
        $doc_table = $doc_type_result['type_table'];
        
        // Now look into respective document table, e.g.books
        $this->db->query("SHOW INDEX FROM `" . $doc_table . "` where Key_name = 'PRIMARY'");
        $doc_id_column = ($this->db->nextResult())['Column_name'];
        
        $this->db->query("SELECT * FROM `". $doc_table . "` WHERE title=:doc_title");
        $this->db->bind(':doc_title', $doc_title);
        $doc_result = $this->db->nextResult();
        $doc_table_id = $doc_result[$doc_id_column];
        
        if ($doc_table == "books") {
            if ($doc_result['is_best_seller'] == 0) {
                $best_seller = false;
            } else {
                $best_seller = true;
            }
        }
        
        // check if there are any items with this doc_table_id that can be checked, ONLY REFERENCE COPIES
        $doc = new Document();
        $bookable_items = $doc->getNonReferenceCopies($doc_type_id, $doc_table_id);
        if (empty($bookable_items)) {
            $this->error="Sorry, there are <strong>no bookable copies</strong> of this document in the library. The only available copies, if any, are for <strong>references only</strong>.<br /><br />Try contacting a librarian to get more specific help regarding the availability of reference copies for booking!";
            return;
        }
        
        // Now, since there are non-reference copies too, proceed by getting all those copies in an array
        $item = $doc->getBookableDocItems($doc_type_id, $doc_table_id);
        
        $max_duration = $user_data['maximum_booking_duration'];
        if (isset($best_seller)) {
            $duration = ($max_duration > 14) ? 14 : $max_duration;
        } else {
            $duration = ($max_duration > $duration) ? $duration : $max_duration;
        }
        
            $item_id = $item['item_id'];
            $this->db->query("INSERT INTO `booking-log`(`item_id`, `user_id`, `booking_status`, `booked_date`, `duration_days`, `is_outstanding_request`) VALUES (0, :user_id, :booking_status, :booked_date, :duration, :outstanding)");
            $this->db->bind(':user_id', $user_data['user_card_number']);
            $this->db->bind(':booking_status', "PENDING-" . $doc_type_id . "-" . $doc_table_id);
            $this->db->bind(':booked_date', date("Y-m-d"));
            $this->db->bind(':duration', $duration);
            $this->db->bind(':outstanding', $outstanding_request);
            $this->db->execute();
            
            /*
            // update item's details
            $this->db->query("UPDATE `doc-items` SET `is_currently_checked_out`=1 WHERE item_id=:item_id");
            $this->db->bind(":item_id", $item_id);
            $this->db->execute();
            */
    }

    // librarian approves booking request
    public function approveRequest($booking_id) {
        $db = $this->db;
        
        $db->query("SELECT `booking_status` FROM `booking-log` WHERE `booking_log_id`=:booking_id");
        $db->bind(':booking_id', $booking_id);
        $booking_status = $db->nextResult()['booking_status'];
        
        $doc_info = explode('-', substr($booking_status, 8, strlen($booking_status)));

        // give specific item now
        $item_id = (new Document())->getBookableDocItems($doc_info[0], $doc_info[1])[0]['item_id'];
        
        $db->query('UPDATE `booking-log` SET item_id=:item_id, booked_date=:date, booking_status="BOOKED" WHERE booking_log_id=:booking_id');
        $db->bind(':item_id', $item_id);
        $db->bind(':date', date("Y-m-d"));
        $db->bind(':booking_id', $booking_id);
        $db->execute();
        
        $db->query('UPDATE `doc-items` SET `is_currently_checked_out`=1 WHERE item_id=:item_id');
        $db->bind(':item_id', $item_id);
        $db->execute();
        
    }

    // removes pending booking request
    public function declineRequest($booking_id) {
        $db = $this->db;
        $db->query("DELETE FROM `booking-log` WHERE `booking_log_id`=:booking_id");
        $db->bind(':booking_id', $booking_id);
        $db->execute();
        
    }
    
    // this is the method that patrons use to say that they have returned the document
    // this is achieved by changing booking-status to "RETURNED-UNCONFIRMED"
    // the item is not considered return until confirmed though.
    public function sayBookedDocReturned($booking_id) {
        $db = $this->db;
        $db->query("UPDATE `booking-log` SET `booking_status`='RETURNED-UNCONFIRMED', `returned_date`=:return_date WHERE `booking_log_id`=:record_id");
        $db->bind(':return_date', date("Y-m-d"));
        $db->bind(':record_id', $booking_id);
        $db->execute();
        
    }
    
    // returns all bookings that has been said returned, but not yet confirmed
    public function returnUnconfirmedReturns() {
        $this->db->query("SELECT * FROM `booking-log` WHERE `booking_status`='RETURNED-UNCONFIRMED'");
        return $this->db->resultset();
    }
    
    // changes Booking_status from 'RETURNED-UNCONFIRMED' to 'RETURNED' and 
    // changes the item copy's is_currently_Checked_out to 0.
    public function confirmBookedDocReturned($booking_id) {
        $db = $this->db;
        $db->query("UPDATE `booking-log` SET `booking_status`='RETURNED' WHERE `booking_log_id`=:record_id");
        $db->bind(':record_id', $booking_id);
        $db->execute();
        
        $db->query("SELECT `item_id` FROM `booking-log` WHERE `booking_log_id`=:booking_id");
        $db->bind(':booking_id', $booking_id);
        $item_id = $db->nextResult()['item_id'];
        
        $db->query("UPDATE `doc-items` SET `is_currently_checked_out`='0' WHERE `item_id`=:item_id");
        $db->bind(":item_id", $item_id);
        $db->execute();
    }
    
    // selects documents which have the status "BOOKED" and the booked_date + duration < today
    public function returnOverdueBookings() {
        $db = $this->db;
        $db->query('SELECT * FROM `booking-log` WHERE `booking_status`="BOOKED"');
        $records = $db->resultset();
        
        $result=array();
        foreach ($records as $booking) {
            $due_date = $this->calculateDueDate($booking['booking_log_id']);
            
            if ($due_date < date("Y-m-d") ) {
                array_push($result, $booking);
            }
        }
        
        return $result;
    }
    
    // clears the overdue status of a booking record
    public function clearOverdue($booking_id) {
        $db = $this->db;
        $db->query("UPDATE `booking-log` SET `booking_status`='RETURNED' WHERE `booking_log_id`=:record_id");
        $db->bind(':record_id', $booking_id);
        $db->execute();
    }
    
    // formats difference in days in like: 4 years, 2 months, and 1 days.
    public function formatDateDifference($dateYounger, $dateOlder) {
        $datetime1 = new DateTime($dateYounger);
        $datetime2 = new DateTime($dateOlder);

        $interval = $datetime1->diff($datetime2);
        $years = $interval->format('%y');
        $months = $interval->format('%m');

        if ($years=="0") {
            if ($months=="0") {
                return $interval->format('<strong>%d</strong> days');
            } else {
                return $interval->format('<strong>%m</strong> months, and <strong>%d</strong> days');
            }
        }
        
        return $interval->format('<strong>%y</strong> years, <strong>%m</strong> months, and <strong>%d</strong> days');
    }

    // returns all booking records
    public function listAllRecords() {

    }
    
    // calculates and returns the due Date of a booking id
    public function calculateDueDate($booking_id) {
        $db = $this->db;
        $db->query("SELECT `booked_date`,`duration_days` FROM `booking-log` WHERE `booking_log_id`=:booking_id");
        $db->bind(':booking_id', $booking_id);
        
        $booking_record = $db->nextResult();
        $booked_date = $booking_record['booked_date'];
        $duration = $booking_record['duration_days'];
        
        $due_date = date('Y-m-d', strtotime($booked_date . ' + ' . $duration . ' days'));
        return $due_date;
    }
    
    // returns current booking records by a particular user 
    public function getCurrentBookings($user_id) {
        $db = $this->db;
        $db->query("SELECT * FROM `booking-log` WHERE `booking_status`='BOOKED' AND user_id=:user_by_id");
        $db->bind(':user_by_id', $user_id);
        return $db->resultset();
    }

    // gets the booking_priority of individual user: 1 (being the highest priority)
    public function getMyBookingPriority($user_id) {
        $db = $this->db;
        $db->query("SELECT `user_type_id` FROM `users` WHERE `user_card_number`=:user_id");
        $db->bind(':user_id', $user_id);
        $type_id = $db->nextResult()['user_type_id'];
        
        $db->query("SELECT `booking_priority` FROM `user-type` WHERE `type_id`=:type_id");
        $db->bind(":type_id", $type_id);
        return $db->nextResult()['booking_priority'];
        
    }

    // returns booking records that are not approved yet in order of their respective priority of requesting user
    public function listPendingRecords() {
        $db = $this->db;
        $db->query('SELECT * FROM `booking-log` WHERE (`booking_status` LIKE "%PENDING%") AND is_outstanding_request=0 ORDER BY booked_date ASC');
        $pending_records = $db->resultset();
        
        $pq = new PriorityQueue();
        foreach($pending_records as $rec) {
            $pq->insert($rec, $this->getMyBookingPriority($rec['user_id']));            
        }
        
        //mode of extraction 
        $pq->setExtractFlags(PriorityQueue::EXTR_BOTH);
        
        $result = array();
        if ($pq->count()>0) {
            //Go to TOP 
            $pq->top(); 
            
            while($pq->valid()) { 
                array_push($result, $pq->current()['data']);
                $pq->next(); 
            }
        }
        
        return $result;
    }
    
    // returns outstanding pending booking requests
    public function listOutstandingPendingRecords() {
        $db = $this->db;
        $db->query('SELECT * FROM `booking-log` WHERE (`booking_status` LIKE "%PENDING%") AND is_outstanding_request=1');
        return $db->resultset();
    }
    
    // calculates fine taking the expected return date
    public function calculateFine($expected_return) {
        $date1 = new DateTime($expected_return);
        $date2 = new DateTime(date('Y-m-d'));
        $diff = $date2->diff($date1)->format("%a");
        return 100*($diff);
    }
    
    // The booking record can be in one of these three status: PENDING, BOOKED, RETURNED
    public function myBookingRecords($user_id, $booking_status1, $booking_status2="", $booking_status3="") {
        $booking_status2 = (empty($booking_status2)) ? "" : $booking_status2;
        $booking_status3 = (empty($booking_status3)) ? "" : $booking_status3;
        $booking_status1 = ($booking_status1=="PENDING") ? "%".$booking_status1."%" : $booking_status1;
        
        $db = $this->db;
        $db->query('SELECT * FROM `booking-log` WHERE `user_id`=:user_id AND (`booking_status` LIKE :status1 OR `booking_status` IN (:status2, :status3))');
        $db->bind(':user_id', $user_id);
        $db->bind(':status1', $booking_status1);
        $db->bind(':status2', $booking_status2);
        $db->bind(':status3', $booking_status3);
        
        return $db->resultset();
    }
    
    // calculates rank of given pending booking request in the priority queue of requested document
    public function calculateMyRank($booking_id, $doc_type_id, $doc_id) {
        // For this particular document, the 'booking_status' would look like PENDING-23-12
        $exp_booking_status = "PENDING-" . $doc_type_id . "-" . $doc_id;
        
        $pending_requests = $this->listPendingRecords();
        $result_queue = array();
        foreach($pending_requests as $each) {
            if ($each['booking_status'] == $exp_booking_status) {
                array_push($result_queue, $each);
            }
        }
        
        $rank = 1;
        foreach($result_queue as $result) {
            if ($result['booking_log_id'] == $booking_id) {
                return $rank;
            } else {
                $rank += 1;
            }
        }
        return $rank;
    }
    
    public function filterRequestsByDoc($requests) {
        
        $result = array();
        
        foreach($requests as $req) {
            // $doc_info representing doc_type_id-doc_table_id distinguishes documents requested
            $doc_info = substr($req['booking_status'], 8, strlen($req['booking_status']));
            if (!array_key_exists($doc_info, $result)) {
                $result[$doc_info] = array();
            }
            array_push($result[$doc_info], $req);
            
        }
        return $result;
        
    }
    
    public function earliestDocAvailDate($item_id) {
        $db = $this->db;
        $doc = new Document();
        $doc_type_id = $doc->getDocTypeIdByDocItemId($item_id);
        $doc_table_id = $doc->getDocTableIdByDocItemId($item_id);
        
        $db->query('SELECT * FROM  `doc-items` WHERE `doc_type_id`=:type_id AND `doc_table_id`=:table_id');
        $db->bind(':type_id', $doc_type_id);
        $db->bind(':table_id', $doc_table_id);
        
        $item = $db->nextResult();
        
        $db->query("SELECT * FROM `booking-log` WHERE `item_id`=24 AND `booking_status`='BOOKED' ORDER BY (`booked_date` + duration_days) ASC");
        $db->bind(':item_id', $item['item_id']);
        $result = $db->nextResult();
        
        return $result['booked_date'] + $result['duration_days'];
    }

}

?>