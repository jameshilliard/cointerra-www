<?php
require_once 'common.php';

//error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '0');  //dev version
date_default_timezone_set('America/New_York');
?>

<?php

	$filepath = get_conf_filepath();
	if($filepath == '') {
		die("Server error, the file doesn't seem to exist.");
	}

    $type = filetype($filepath);
    // Get a date and timestamp
    $today = date("F j, Y, g:i a");
    $time = time();
    // Send file headers
    header("Content-type: $type");
    header("Content-Disposition: attachment;filename=cgminer.conf");
    header("Content-Transfer-Encoding: binary"); 
    header('Pragma: no-cache'); 
    header('Expires: 0');
    // Send the file contents.
    set_time_limit(0); 
    readfile($filepath);
?>

