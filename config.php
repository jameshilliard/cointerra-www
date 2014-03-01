<?php
require_once 'common.php';
require_once 'includes/apiSocket.php';

// error_reporting(E_ALL); //this is a dev version
ini_set ( 'display_errors', '0' ); // dev version
date_default_timezone_set ( 'America/New_York' );
?>
 
<?php echo $head; /*common.php */?>

	<?php echo $menu; /*common.php */?>

<table border=1 cellspacing=0 cellpadding=2>
	<tr>
		<td valign='top'>
			<div id='main'></div>
		</td>
	</tr>
</table>

<?php echo $foot; /*common.php */?>

<?php

$htmlMainStr = "";

function readPoolList() {
	$res = array ();
	$conf = json_decode ( file_get_contents ( get_conf_filepath () ), True );
	
	//if ($conf == null) {
		//parsing failed, pull in default file
		//restore_default_conf();
		//$conf = json_decode ( file_get_contents ( get_conf_filepath () ), True );
		//$conf = array();
	//}
	
	$priority = 1;
	foreach ( $conf ['pools'] as $pool ) {
		$quota = array_key_exists ( 'quota', $pool ) ? substr ( $pool ['quota'], 0, strpos ( $pool ['quota'], ";" ) ) : 1;
		$url = array_key_exists ( 'quota', $pool ) ? substr ( $pool ['quota'], strpos ( $pool ['quota'], ";" ) + 1 ) : $pool ['url'];
		array_push ( $res, array (
				"url" => $url,
				"username" => $pool ['user'],
				"password" => $pool ['pass'],
				"priority" => $priority ++,
				"quota" => $quota 
		) );
	}
	return $res;
}
function addPoolToList($pool) {
	$res = array ();
	$conf = json_decode ( file_get_contents ( get_conf_filepath () ), True );
	if (! $conf ['pools'])
		$conf ['pools'] = array ();
	
	$pool_ = array (
			"user" => $pool ['username'],
			"pass" => $pool ['password'] 
	);
	
	if (array_key_exists ( 'quota', $pool )  && ($pool ['quota'] != ''))
		$pool_ ['quota'] = $pool ['quota'] . ";" . $pool ['url'];
	else
		$pool_ ['url'] = $pool ['url'];
		
		// minor validation on the input priority
	if ($pool ['priority'] < 1)
		$pool ['priority'] = 1;
	if ($pool ['priority'] > count ( $conf ['pools'] ))
		$pool ['priority'] = count ( $conf ['pools'] ) + 1;
	
	array_splice ( $conf ['pools'], $pool ['priority'] - 1, 0, array (
			$pool_ 
	) );
	
	file_put_contents ( get_conf_filepath(), json_encode ( $conf ) );
}
function rmPoolFromList($pool) {
	$res = array ();
	$conf = json_decode ( file_get_contents ( get_conf_filepath () ), True );
	$pools = array();
	
	if ($pool > 0)
		$pools = array_slice ( $conf ['pools'], 0, $pool-1, true );
	
	$pools += array_slice ( $conf ['pools'], ($pool ? $pool : 1), count ( $conf ['pools'] ) - 1, true );
	$conf ['pools'] = $pools;
	file_put_contents ( get_conf_filepath (), json_encode ( $conf ) );
}
function editPoolFromList($pool) {
	rmPoolFromList ( $pool['old_priority'] );
	addPoolToList ( $pool );
}
function getPoolStrategy() {
	$conf = json_decode ( file_get_contents ( get_conf_filepath () ), True );
	
	if (array_key_exists ( 'round-robin', $conf ))
		return 'RoundRobin';
	if (array_key_exists ( 'balance', $conf ))
		return 'Balance';
	if (array_key_exists ( 'load-balance', $conf ))
		return 'LoadBalance';
	
	return 'Failover';
}
function setPoolStrategy($new_strat) {
	$conf = json_decode ( file_get_contents ( get_conf_filepath () ), True );
	
	if (array_key_exists ( 'round-robin', $conf ))
		unset ( $conf ['round-robin'] );
	if (array_key_exists ( 'balance', $conf ))
		unset ( $conf ['balance'] );
	if (array_key_exists ( 'load-balance', $conf ))
		unset ( $conf ['load-balance'] );
	
	if ($new_strat != 'failover')
		$conf [$new_strat] = 'true';
	
	file_put_contents ( get_conf_filepath (), json_encode ( $conf ) );
}

if (array_key_exists ( 'submit', $_POST )) {
	if ($_POST ['submit'] == 'Add') {
        $_POST['op'] = "addpool";
        //$_POST['priority'] = str_replace("Add", "", $_POST['submit']);
		addPoolToList ( $_POST );
		execInBackground ( "sudo /etc/init.d/S99cgminer restart" );
	} else if (strpos($_POST ['submit'], 'Remove') !== false) {
        $_POST['op'] = "rmpool";
        $_POST['priority'] = intval(str_replace("Remove", "", $_POST['submit']));
		rmPoolFromList ( $_POST['priority'] );
		execInBackground ( "sudo /etc/init.d/S99cgminer restart" );
	} else if (strpos($_POST ['submit'], '_Edit') !== false) {
        $_POST['op'] = "editpool";
        $_POST['priority'] = intval(str_replace("_Edit", "", $_POST['submit']));
    } else if (strpos($_POST ['submit'], 'Edit') !== false) {
    	$_POST['old_priority'] = intval(str_replace("Edit", "", $_POST['submit']));
        $_POST['url'] = $_POST['url'.$_POST['old_priority']];
        $_POST['username'] = $_POST['username'.$_POST['old_priority']];
        $_POST['password'] = $_POST['password'.$_POST['old_priority']];
        $_POST['priority'] = $_POST['priority'.$_POST['old_priority']];
        $_POST['quota'] = $_POST['quota'.$_POST['old_priority']];
		editPoolFromList ( $_POST );
		execInBackground ( "sudo /etc/init.d/S99cgminer restart" );
	}
	
}

if (array_key_exists ( 'setstrategy', $_POST )) {
	switch ($_POST ['setstrategy']) {
		case 'Failover' :
			setPoolStrategy ( 'failover' );
			break;
		case 'RoundRobin' :
			setPoolStrategy ( 'round-robin' );
			break;
		case 'LoadBalance' :
			setPoolStrategy ( 'load-balance' );
			break;
		case 'Balance' :
			setPoolStrategy ( 'balance' );
			break;
		default :
			break;
	}
}

function getSysLoad(){
	$v = file_get_contents("/Angstrom/Cointerra/cta_load");
	if (($v>=0)&&($v<=255)) $v = round(8*$v/255);
        else $v = 8;
        return $v + 1;
}

function setSysLoad($v){
	$v = round(255*($v-1)/8);
        if ($v == 0) $v = 1; // the min value cannot be 0
	file_put_contents("/Angstrom/Cointerra/cta_load", $v);
}

if (array_key_exists ( 'setsysload', $_POST )) {
        setSysLoad($_POST['setsysload']);
	execInBackground ( "sudo /etc/init.d/S99cgminer restart" );
}


if (array_key_exists ( 'panel_password', $_POST )) {
	exec("echo admin:" . $_POST['panel_password'] . " > /Angstrom/Cointerra/lighttpd.password");
}

$formSwitchPoolHTML = "";
$formSwitchPoolHTML .= "<h3>CGMiner Configuration</h3><span style='font-size: 18px'>Pool Settings</span> <form method=POST><table><tr><th>URL</th><th>Worker Name</th><th>Password</th><th>Priority</th><th>Quota</th><th style='width: 230px'>Actions</th></tr>";

foreach ( readPoolList () as $pool ) {
	if ( (array_key_exists ( 'op', $_POST ) && ($_POST ['op'] == 'editpool')) 
	     && (array_key_exists ( 'op', $_POST ) && ($pool ['priority'] == $_POST ['priority'])) ) {
		
		$formSwitchPoolHTML .= "<tr>";
		$formSwitchPoolHTML .= "  <td> <input required type='url' name='url" . $pool ['priority'] . "' style='width: 100%' value='" . $pool ['url'] . "'>			  </td>";
		$formSwitchPoolHTML .= "  <td> <input required type='text' name='username" . $pool ['priority'] . "' style='width: 100%' value='" . $pool ['username'] . "'> </td>";
		$formSwitchPoolHTML .= "  <td> <input type='text' name='password" . $pool ['priority'] . "' style='width: 100%' value='" . $pool ['password'] . "'> </td>";
		$formSwitchPoolHTML .= "  <td> <input pattern='[0-9]*' type='number' min='1' name='priority" . $pool ['priority'] . "' style='width: 100%' value='" . $pool ['priority'] . "'> </td>";
		$formSwitchPoolHTML .= "  <td> <input pattern='[0-9]*' type='number' min='0' name='quota" . $pool ['priority'] . "' style='width: 100%' value='" . $pool ['quota'] . "'>	      </td>";
		$formSwitchPoolHTML .= "  <td>";
		$formSwitchPoolHTML .= "  		<button class='submit' type='submit' style='float: left' name='submit' value='Edit" . $pool ['priority'] . "'>Edit</button>";
		$formSwitchPoolHTML .= "  		<input type=hidden name=priority value='" . $pool ['priority'] . "'>";
		$formSwitchPoolHTML .= "  		<button class='submit' type='submit' style='float: left; margin-left: 20px;' name='submit' value='Remove" . $pool ['priority'] . "'>Remove</button>";
		$formSwitchPoolHTML .= "  </td>";
		$formSwitchPoolHTML .= "</tr>";
	} else {
		$formSwitchPoolHTML .= "<tr> ";
		$formSwitchPoolHTML .= "	<td>" . $pool ['url'] . "</td>";
		$formSwitchPoolHTML .= "	<td>" . $pool ['username'] . "</td>";
		$formSwitchPoolHTML .= "	<td>" . $pool ['password'] . "</td>";
		$formSwitchPoolHTML .= "	<td>" . $pool ['priority'] . "</td>";
		$formSwitchPoolHTML .= "	<td>" . $pool ['quota'] . "</td>";
		$formSwitchPoolHTML .= "	<td> ";
		$formSwitchPoolHTML .= "		<div style='width: 100%; overflow: hidden;'>";
		$formSwitchPoolHTML .= "			<div style='width: 100px; float: left;'>";
		$formSwitchPoolHTML .= "					<input type=hidden name=priority value='" . $pool ['priority'] . "'>";
		$formSwitchPoolHTML .= "					<button class='submit' type='submit' style='float: left' name='submit' value='_Edit" . $pool ['priority'] . "'>Edit</button>";
		$formSwitchPoolHTML .= "			</div>";
		$formSwitchPoolHTML .= "	    	<div style='width: 100px; float: right;'>";
		$formSwitchPoolHTML .= "	    			<input type=hidden name=priority value='" . $pool ['priority'] . "'>";
		$formSwitchPoolHTML .= "	    			<button class='submit' type='submit' style='float: right;' name='submit' value='Remove" . $pool ['priority'] . "'>Remove</button>";
		$formSwitchPoolHTML .= "			</div>";
		$formSwitchPoolHTML .= "		</div>";
		$formSwitchPoolHTML .= "	</td>";
		$formSwitchPoolHTML .= "</tr>";
	}
}


$formSwitchPoolHTML .= "<tr>";
$formSwitchPoolHTML .= "		<td><input type='url' value='' placeholder='http://pool.com:port' name='url' style='width: 100%'></td>";
$formSwitchPoolHTML .= "		<td><input type='text' value='' placeholder='worker.1' name='username' style='width: 100%'></td>";
$formSwitchPoolHTML .= "		<td><input type='text' value='' name='password' style='width: 100%'></td>";
$formSwitchPoolHTML .= "		<td><input pattern='[0-9]*' min='1' placeholder='1' type='number' value='' name='priority' style='width: 100%'></td>";
$formSwitchPoolHTML .= "		<td><input pattern='[0-9]*' type='number' min='0' placeholder='1' value='' name='quota' style='width: 100%'></td>";
$formSwitchPoolHTML .= "		<td><button class='submit' type='submit' name='submit' value='Add'>Add</button></td>";
$formSwitchPoolHTML .= "</tr>";

$formSwitchPoolHTML .= "</table></form>";

$formSwitchPoolHTML .= "<span style='font-size: 18px'>Pool Strategy</span> <form method=post action=config.php> <select onchange=this.form.submit() name=setstrategy>";
$formSwitchPoolHTML .= "<option value=Failover " . ((getPoolStrategy () == "Failover") ? " selected" : "") . ">Failover</option>";
$formSwitchPoolHTML .= "<option value=RoundRobin " . ((getPoolStrategy () == 'RoundRobin') ? " selected" : "") . ">Round Robin</option>";
$formSwitchPoolHTML .= "<option value=LoadBalance " . ((getPoolStrategy () == 'LoadBalance') ? " selected" : "") . ">Load Balance</option>";
$formSwitchPoolHTML .= "<option value=Balance " . ((getPoolStrategy () == 'Balance') ? " selected" : "") . ">Balance</option>";
$formSwitchPoolHTML .= "</select></form>";

$htmlMainStr .= $formSwitchPoolHTML;

$formUploadConfHTML = "<span style='font-size: 18px'>Advanced</span><div style='font-size: 5px'><br></div>";
$formUploadConfHTML .= "<form id='upload1' action=upload.php method=post enctype=multipart/form-data>";
$formUploadConfHTML .= "<input type=hidden name=conf value=1>";
$formUploadConfHTML .= "<div><input type=submit value='Import Configuration File' onclick='return false' style='cursor: pointer; position: absolute; float: left'><input type=file onchange='document.getElementById(\\\"upload1\\\").submit()' style='cursor: pointer !important; position: absolute; float: left; display: block !important; width: 175px !important; height: 35px !important; opacity: 0 !important; overflow: hidden !important;' name=file id=file></div>";
$formUploadConfHTML .= "</form><br><br><input style='/*position: absolute; left: 230px*/' type=submit onclick='location.href=\\\"dlconf.php\\\";' value='Export Configuration File'><br>";

$formUploadConfHTML .= "<form method='post' action='upload.php'>";
$formUploadConfHTML .= "<input type=hidden name=rig value=" . $minerList [0] . ">";
$formUploadConfHTML .= "<br><br><input type='submit' value='Restart CGMiner' name='submit' style='width: 250px'>";
$formUploadConfHTML .= "</form><br><hr>";
$htmlMainStr .= $formUploadConfHTML;

$formSysLoadHTML =  "<h3>System Configuration</h3>";
$formSysLoadHTML .= "<span style='font-size: 18px'>Power Stepping</span> <form method=post action=config.php> <select onchange=this.form.submit() name=setsysload>";
$formSysLoadHTML .= "<option value=9 " . ((getSysLoad () == 9) ? " selected" : "") . ">9 (highest)</option>";
$formSysLoadHTML .= "<option value=8 " . ((getSysLoad () == 8) ? " selected" : "") . ">8</option>";
$formSysLoadHTML .= "<option value=7 " . ((getSysLoad () == 7) ? " selected" : "") . ">7</option>";
$formSysLoadHTML .= "<option value=6 " . ((getSysLoad () == 6) ? " selected" : "") . ">6</option>";
$formSysLoadHTML .= "<option value=5 " . ((getSysLoad () == 5) ? " selected" : "") . ">5</option>";
$formSysLoadHTML .= "<option value=4 " . ((getSysLoad () == 4) ? " selected" : "") . ">4</option>";
$formSysLoadHTML .= "<option value=3 " . ((getSysLoad () == 3) ? " selected" : "") . ">3</option>";
$formSysLoadHTML .= "<option value=2 " . ((getSysLoad () == 2) ? " selected" : "") . ">2</option>";
$formSysLoadHTML .= "<option value=1 " . ((getSysLoad () == 1) ? " selected" : "") . ">1 (lowest)</option>";
$formSysLoadHTML .= "</select></form>";
$htmlMainStr .= $formSysLoadHTML;

$formUploadFwHTML = "<br>";
$formUploadFwHTML .= "<form id='upload2' action=upload.php method=post enctype=multipart/form-data><input type=hidden name=fw value=1><input type=submit value='System Upgrade' onclick='return false' style='cursor: pointer; position: absolute; float: left'><input type=file onchange='document.getElementById(\\\"upload2\\\").submit()' style='cursor: pointer !important; position: absolute; float: left; display: block !important; width: 160px !important; height: 35px !important; opacity: 0 !important; overflow: hidden !important;' name=file id=file></form>";
$formUploadFwHTML .= "<br><br><hr>";

$htmlMainStr .= $formUploadFwHTML;


$formSetPassHTML = "<h3>Web Panel Configuration</h3>";
$formSetPassHTML .= "<form action=config.php method=post>";
$formSetPassHTML .= "<span style='font-size: 18px'>Password</span><br><input type=text name=panel_password>";
$formSetPassHTML .= "<input type='submit' value='Apply' style='margin-left: 20px'></form>";
$formSetPassHTML .= "<br><br>";
$htmlMainStr .= $formSetPassHTML;

$htmlFoot = getVersionFooter ();

$_SESSION ['cgminer_restarting'] = 0;
$_SESSION ['system_restarting'] = 0;

?>

<script>
	execEle = document.getElementById('main');
	execEle.innerHTML = "<?php echo $htmlMainStr; ?>";
	execEle = document.getElementById('foot');
	execEle.innerHTML = "<?php echo $htmlFoot; ?>";
</script>

</body>
</html>
