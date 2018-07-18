<?php
require_once 'config.php';
require_once 'load-classes.php';

// if there's anywhere book typed in the query, look into book section first and so on.
$query_arrr=array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $query_arr = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
} else if($_SERVER['REQUEST_METHOD'] == 'GET') {
    $query_arr = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
}

/* Handle all queries here. */
if (!empty($query_arr) and $query_arr['destination']=="advanced-search") {
    if ($query_arr['subject'] == "show-hints") {
        $searchStr = $query_arr['search_string'];
        $doc = new Document();
        $str = explode(' or ', $searchStr);
        
        $result="";
        $temp_res = array();
        
        foreach ($str as $searchStr) {
            $result_arr = $doc->searchByDocTitleAndKeywords($searchStr);

            foreach ($result_arr as $res_arr) {
                $searchTag = (stripos($res_arr['title'], $searchStr) === false) ? "Keyword" : "Title";
                $res = '&#9658;&nbsp;<span class="w3-text-blue"><strong>' .$res_arr['doc-type'] . '</strong></span>, ' . $res_arr['title'] . ' <sup class="w3-text-white w3-green">&nbsp;' . $searchTag . '&nbsp;</sup>';
                if (strlen($result)==0) {
                    $result = $res;
                    array_push($temp_res, $res_arr['title']);
                } else {
                    if (in_array($res_arr['title'], $temp_res)) continue;
                    
                    $result = $result. '<br>' . $res;
                    array_push($temp_res, $res_arr['title']);
                }                
            }
        }
        echo $result;
    }
    
// No need to load document further, as the code above is handled by AJAX Requests.
exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Advanced Search | Innopolis Online Library Management System</title>
    <?php require_once 'styles.php'; ?>
    
    <style>
    .search-body {
        padding: 20px;
        text-align: left;
    }
    
    h2 {
        text-align: center;
    }
    
    .is-fieldset {
        position: relative;
        outline: 2px groove black;
    }
    
    .is-fieldset>legend {
        position: absolute;
        top: -18px;
        margin: 0 auto;
        width: max-content;
        padding: 0 4px;
        background-color: white;
        font-size: 1.4em;
    }
    
    .search-input {
        padding: 8px;
    }
    
    .search-input>input {
        width: 90%;
        padding: 8px;
        height: 40px;
    }

    @media screen and (max-width: 1150px) {
        .search-input>input {
            width: 100%;
            margin-bottom: 5px;
        }
    }

    .search-input>button {
        float: none;
        padding: 8px;
        height: 40px;
        min-width: 100px;
        background: rgba(0,0,0,0.7);
        transition: transform 1s, rotate 2s;
        
    }
    
    .search-input>button:hover {
        background: rgba(0,0,0,1);
        height: 1.5;
        transform: rotate(-360deg);
    }

    .search-hints {
        outline: 2px groove black;
        margin: 20px 0;
        padding: 8px 8px 18px;
        height: 150px;
        max-height: 180px;
    }
    
    .search-hints div {
        max-height: 120px;
        overflow: auto;
        display: none;
    }

    .search-results {
        outline: 2px groove black;
        margin: 40px 0;
        padding: 8px;
    }

    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <div class="search-body">
        <h2>Advanced Search</h2>
        <p class="">
            This page allows you to search for documents (books, journal articles, audio-video materials) available in this library according to various criterias such as document name, author name, publisher name, etc.
        </p>
        
        <div class="search-input">
            <input type="text" name="txt-search" onload="showHints();" id="search-text" placeholder="Type document title, author name, publisher name, etc." />
            <button name="btn-search"><i class="fa fa-search fa-fw"></i>&nbsp;&nbsp;Search</button>
        </div>
        
        <div class="search-hints is-fieldset">
            <legend>Search Hints</legend>
            <div id="no-hints" style="margin: 18px auto; display: block;">Type <strong>keywords</strong> in the searchbox above for suggestions.</div>
            <div><strong>Books</strong>: <span id="book-hints"></span></div>        
            <div>Journal Articles: <span id="journal-article-hints"></span></div>
            <div id="author-hints">Authors: </div>
            <div>Audio-Video Material: <span id="av-material-hints"></span></div>
        </div>
        
        <div class="search-results is-fieldset">
            <legend>Search Results</legend>
            This is where results are displayed.
        </div>
        
        <!--
        <iframe src="https://stackoverflow.com/questions/386914/how-would-i-implement-a-simple-site-search-with-php-and-mysql">
            <p>Your browser does not support iframes.</p>
        </iframe>
        -->
    </div>

<script>
    $(document).ready(function(){
        const driver = new Driver({animate: true, });
           driver.highlight({
               element: '#search-text',
               popover: {
                   title: '<span class="w3-text-deep-orange">Search Hints</span>',
                   description: 'Start typing in this searchbox for hints.',
               }
           });         
    });
    
    var search_input = document.getElementById("search-text");
    var no_hints = document.getElementById('no-hints');
    var book_hints = document.getElementById('book-hints');
    var ja_hints = document.getElementById('journal-article-hints');
    var author_hints = document.getElementById('author-hints');
    var av_hints = document.getElementById('av-material-hints');
    
    search_input.addEventListener("keyup", function() {showHints(search_input.value);});
    
    function showHints(str) {
        str = (!str) ? "":str.trim();
        if (str.length==0) {
            book_hints.style.display = "none";
            ja_hints.style.display = "none";
            author_hints.style.display = "none";
            av_hints.style.display = "none";
            no_hints.innerHTML = "<span class=\"w3-text-teal\">Type <strong>keywords</strong> in the searchbox above for suggestions.</span>";
            return;
        } else {
            no_hints.innerHTML = 'Searching ... <i class="fa fa-spinner w3-spin" style="font-size:16px"></i>';
            var temp_res="";
            
            // Using the core $.ajax() method
            $.ajax({
                // The URL for the request
                url: "advanced-search.php",
                
                // The data to send (will be converted into a query string)
                data: {
                    subject: "show-hints",
                    destination: "advanced-search",
                    search_string: str
                },
                
                // Whether this is a "GET" or a "POST" request
                type: "GET",
                
                // The type of data we expect back
                dataType: "text",
            })
                // Code to run if the request succeeds (is done);
                // The response is passed to the function
                .done(function(text) {
                    if (!text) {
                        temp_res = '<span class="w3-text-red"><strong>No matches found</strong></span>! Make sure the search words are <strong>correctly spelled</strong>, and represent <strong>broader</strong> and more <strong>relevant</strong> search queries.';
                    } else {
                        temp_res = text;
                        //temp_res = temp_res.replace(new RegExp(str, 'ig'), '<span style="background-color: lightgrey;">$&</span>');
                    }
                    no_hints.innerHTML = temp_res;
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
            
            /*
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var temp_res = this.responseText;
                    if (!temp_res) {
                        no_hints.innerHTML = "No matches found! Make sure the search words are <strong>correctly spelled</strong>, and represent <strong>broader</strong> and more <strong>relevant</strong> search queries."
                    } else {
                        temp_res = temp_res.replace(new RegExp((str), 'ig'), '<span style="background-color: lightgrey;">' + str + "</span>");
                        no_hints.innerHTML = temp_res;
                    }
                }
            };
            xmlhttp.open("POST", "advanced-search.php?subject=show-hints&destination=advanced-search&search_string=" + str, true);
            xmlhttp.send();
            */
        }
    }
    
</script>    
</body>
</html>
