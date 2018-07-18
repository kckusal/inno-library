<?php

class Message {

    public function __construct($type, $msgTitle, $message) {
        if ($type == 'error') {
            $_SESSION['errorMsg'] = $message;
        } else {
            $_SESSION['successMsg'] = $message;
        }
        $_SESSION['msgTitle'] = $msgTitle;
    }

    public static function display() {
        if (isset($_SESSION['errorMsg'])) {
            require_once "styles.php";
            echo '<!-- The Modal -->
                <style>
                #mymodal {
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
                
                .close:hover {
                    background-color: black;
                    color: white;
                    border-radius: 50%;
                }
                </style>
                  <div id="myModal" style=" margin: 0 auto; padding: 104px; position: fixed; display: block; z-index: 999; width: 100%; min-width: 360px; height: 100%; overflow: auto; background-color: rgba(0,0,0, 0.4);">

                  <!-- Modal content -->
                    <div style="background-color: #fefefe; position: relative; margin: 0 auto; padding: 10px; border: 1px solid #888; width: 80%; max-width: 750px; min-width: 360px; min-height: 400px; border-radius: 10px;">
                        <p class="close"><i class="far fa-times-circle"></i></p>
                        <div id="msgTitle" style="font-size: 26px; padding: 15px 10px 0; color:teal; font-weight: light;">'. $_SESSION['msgTitle'].'</div>
                        <p style="position: relative; top: 12px; background-color:white; display:inline; padding: 0 9px;"><strong>Message Details</strong></p>
                        <div style="font-size: 16px; border: 1px solid black; padding: 15px; margin: 2px 16px; border-radius: 6px; min-height: 280px; text-align: left;">' . $_SESSION['errorMsg'] . '</div>
                    </div>
                  </div>

                  <script>
                    // Get the modal
                    var modal = document.getElementById("myModal");

                    // Get the <span> element that closes the modal
                    var span = document.getElementsByClassName("close")[0];

                    // When the user clicks on <span> (x), close the modal
                    span.onclick = function() {
                        modal.style.display = "none";
                    }

                    /* When the user clicks anywhere outside of the modal, close it
                    window.onclick = function(event) {
                        if (event.target == modal) {
                            modal.style.display = "none";
                        }
                    }*/
                  </script>
                  ';

            unset($_SESSION['errorMsg']);
        }


        if (isset($_SESSION['successMsg'])) {
            echo '<div class="alert alert-success">'. $_SESSION['successMsg']. '</div>';
            unset($_SESSION['successMsg']);
        }
    }

}

?>