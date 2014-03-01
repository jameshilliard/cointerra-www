<?php
require_once 'includes/apiSocket.php';
require_once 'common.php';

//error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '0');  //dev version
date_default_timezone_set('America/New_York');
?>



<?php

if (isset($_GET['api']) && isset($_GET['miner'])) {  
	$ret = request($_GET['miner'],$_GET['api']);
	echo array_to_table_2d($ret); 
} else {
	echo "I've got nothin";
}


function array_to_table_2d($a) {
	$zombie_cnt = 0;
	$html = "<table class='tight-table'></tr>";
	
	foreach ($a as $key => $val){
		if (isset($val['No Device']) && ($val['No Device'] == "true")) {
			$zombie_cnt++;
			//continue;
		} 
		
		$html .= "<tr><td>".$key."</td>";
		
		if (is_array($val)) {
			$html .= "<td></td><td></td></tr>";
			foreach ($val as $akey => $aval){
				$html .= "<tr><td></td><td>".$akey."</td><td>".$aval."</td></tr>";
			}
		} else {
			$html .= "<td>".$val."</td><td></td></tr>";
		}
	}
	if ($zombie_cnt > 0) {
		$html .= "<tr><td>".$zombie_cnt." zombies detected</td></tr>";
	}
	$html .= "</table>";
	return $html;
}
?>	
