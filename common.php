
<?php

session_start();

//list of miners to monitor
$minerList= array(
		"127.0.0.1",
		//"192.168.4.116",
		//"192.168.16.64"
		//"192.168.16.30",
);

$minerConfigedOffLine = array();

$menu = <<<HTML_MENU

<div id="wrapper" class="loaded">
	<div id="inner-wrapper">
		<header id="header">
			<a id="logo" href="http://cointerra.com/" >
				<img src="./img/cointerra-tm_logo.png" alt="CoinTerra" title="CoinTerra">
			</a>
			 
			<nav id="navigation" class="col-full" role="navigation">
				<section class="menus">
					<a href="http://cointerra.com/" class="nav-home"><span>Home</span></a>
					<ul id="main-nav" class="nav">
						<li id="menu-item-stat" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-80">
							<a href="./status.php">Status</a>
						</li>
						<li id="menu-item-setup" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-2120">
							<a href="./network.php">Network</a>
						</li>
						<li id="menu-item-detail" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-2120">
							<a href="./config.php">Configuration</a>
						</li>
						<li id="menu-item-detail" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-2120">
							<a href="./detail.php">Advanced</a>
						</li>
					</ul>
				</section><!--/.menus-->
			</nav><!-- /#navigation -->
		</header><!-- /#header -->
	</div><!-- /#inner-wrapper -->
</div><!-- /#wrapper -->
HTML_MENU;

$head = <<<HTML_HEAD
<!DOCTYPE HTML >
<html>
		
<head>
  <title>GoldStrike</title>
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="stylesheet" id="theme-stylesheet-css" href="./css/style.css" type="text/css" media="all">
	<link rel="stylesheet" id="woo-layout-css" href="./css/layout.css" type="text/css" media="all">
	<link rel="stylesheet" href="./css/css"  type="text/css">
	<link rel="stylesheet" id="custom-css-css" type="text/css" href="./css/saved_resource">
	<link rel="stylesheet" type="text/css" id="gravatar-card-css" href="./css/hovercard.css"><link rel="stylesheet" type="text/css" id="gravatar-card-services-css" href="./css/services.css">
 	<script src="/jquery.js"></script>
		
	<style>
	.execsum{
		vertical-align: top;
	}
	
	.execsum td{
		border-right: 1px dotted #deddd9;
		margin-left: 9px;	
	}
		
	.metric-sum td{
		padding-left: 9px;
	}		
	
	.detail {
		padding:none;	
	}
	.tight-table td{
		padding-top: 0.1em;
		padding-right: 1.387em;
		padding-bottom: 0.1em;
		padding-left: 1.387em;
	}
	
	.expand-button 
	{
		float: left; 
		cursor: pointer;
	}
		
	.red-bottom{
		
		border-bottom: solid 2px;
		border-color: #ff0033;
	}
	.red-box{
		color: #ff0033;
		border: solid 1px;
		border-color: #ff0033;
	}
	.red-box-bold{
		color: #ff0033;
		font-weight: bold;
		border: solid 1px;
		border-color: #ff0033;
	}
	.green-bold{
		color: #009900;
		font-weight: bold;
	}
	.green-box-bold{
		color: #009900;
		font-weight: bold;
		border: solid 1px;
		border-color: #009900;
	}
	
	.message{
		color: #006600;
	}
	.messageGreen{
		color: #009900;
	}
	.messageRed{
		color: #ff0033;
	}
	.message-bold{
		color: #ff0033;
		font-weight: bold;
	}
	.message-container{
		padding: 6px;
		width: 575px;
		border: solid 1px;
		border-color: #000066;
	}
	.tiny{
		font-size: 12px;
	}
	
	</style>
</head>
		
<body class="single single-post postid-2215 single-format-standard chrome alt-style-default layout-default  page" style="">
HTML_HEAD;

$foot = <<<HTML_FOOT
<table style="table-layout:fixed;border-style:none;">
	<tr>
		<td valign='top' >
			<div id='foot'></div>
		</td>
	</tr>
</table>
HTML_FOOT;

require_once 'includes/apiSocket.php';

function getVersionFooter(){

	global $minerList;
	global $minerConfigedOffLine;

	foreach ($minerList as $m) {
		if(! in_array($m, $minerConfigedOffLine) ){

			$d = request($m,"devs");
			$stats = request($m,"stats");

			$starter = 1;
			$version = '<p align=right><font size=1>';
				
			foreach ($stats as $value){
				if (array_key_exists('STATS', $value)) {
					if(strncmp($value['ID'],'CTA', 3) == 0) {
						if ($starter == 0) {
							$version .= "| ";
						}
						$starter = 0;
						$version .= $value['ID'] . ": " . $value['FW Revision'] . " (" .  $value['FW Date'] . ") ";
					}
				}
					
			}

			if(isset($d['STATUS']['Description'])) {
				if ($starter == 0) {
					$version .= "| ";
				}
				$starter = 0;
				$version .= $d['STATUS']['Description'] . " ";
			}

			if(file_exists("/version.txt")) {
				if ($starter == 0) {
					$version .= "| ";
				}
				$starter = 0;
				$handle = fopen("/version.txt", 'r');
				$sysver = trim(fgets($handle));
				$version .= "System Version: " . $sysver . " ";
			}
				
			$version .= '</font></p>';
		}
	}
	return $version;
}

?>

<?php
function execInBackground($cmd) {
	if (substr(php_uname(), 0, 7) == "Windows"){
		//pclose(popen("start /B ". $cmd, "r"));
	}
	else {
		exec($cmd . " > /dev/null &");
	}
}

function get_conf_filepath() {
	if (file_exists ( "/home/debian/ctweb.conf" )) {
		// first rev BBB
		return "/home/debian/ctweb.conf";
	} else if (file_exists ( "/Angstrom/Cointerra/cgminer.conf" )) {
		// second rev BBB
		return "/Angstrom/Cointerra/cgminer.conf";
	} else if (file_exists ( "C:\workspace\cointerra\cgminer.conf" )) {
		// windows dev machine
		return "C:\workspace\cointerra\cgminer.conf";
	} else if (file_exists ( "/tmp/cgminer.conf" )) {
		// linux dev machine
		return "/tmp/cgminer.conf";
	}
	return '';
}

function restore_default_conf() {
	if (file_exists ( "/opt/cgminer.conf" )) {
		copy("/opt/cgminer.conf", "/Angstrom/Cointerra/cgminer.conf");
	} else {
		$new_json_file = "{\"pools\" : [{\"url\" : \"\",\"user\" : \"\",\"pass\" : \"\"}]}";
		file_put_contents ( "/Angstrom/Cointerra/cgminer.conf", $new_json_file );
	}
}

?>