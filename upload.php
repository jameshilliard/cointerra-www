<?php
require_once 'common.php';
require_once 'includes/apiSocket.php';

//error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '0');  //dev version
date_default_timezone_set('America/New_York');
?>
 
<?php echo $head; /*common.php */?>

	<?php echo $menu; /*common.php */?>	

	<table  border=1 cellspacing=0 cellpadding=2>
	<tr>
		<td valign='top' >
			<div id='main'></div>
		</td>
	</tr>
	</table>

	<?php echo $foot; /*common.php */?>
 
 <?php $htmlFoot = getVersionFooter(); ?>

<?php

$htmlMainStr = "";

if(isset($_SESSION['cgminer_restarting']) && $_SESSION['cgminer_restarting'] > 0) {
	$htmlMainStr .= "Waiting for CGMiner to restart ";
	if (test_cgminer_socket($minerList[0])) {
		$_SESSION['cgminer_restarting'] = 0;
		header( "refresh:0;url=config.php" );
		$htmlMainStr = "And we're back";
	} else {
		header( "refresh:5;url=upload.php" );
	}
	
	if ($_SESSION['cgminer_restarting'] > 4) {
		$htmlMainStr .= ", but it probably crashed, try another config file, I'll forward you back to the config page";
		header( "refresh:2;url=config.php" );
		$_SESSION['cgminer_restarting'] = 0;
	} else if ($_SESSION['cgminer_restarting'] > 1) {
		$htmlMainStr .= ", should be any second now...";
	}
	$_SESSION['cgminer_restarting']++;
} 

if (isset($_SESSION['system_restarting']) && $_SESSION['system_restarting'] > 0) {
	$htmlMainStr .= "System upgrade in progress, not a good time to power off";
	header( "refresh:60;url=status.php" );
	$_SESSION['system_restarting'] = 0;
	if (exec("sudo /opt/do_upgrade.sh /tmp/upgrade.tgz; echo $?") == "0") {
		exec("sudo /sbin/reboot");
	} else {
		$htmlMainStr = "System upgrade failed :(";
	}
}

if (isset ($_POST['conf'])) {
	$allowedExts = array("conf");
} else if (isset($_POST['fw'])) {
	$allowedExts = array("tgz");
}

if (isset($_FILES["file"]["name"])) {
	$temp = explode(".", $_FILES["file"]["name"]);
	$extension = end($temp);
	if (in_array($extension, $allowedExts))
	{
		if ($_FILES["file"]["error"] > 0)
		{
			$htmlMainStr .= "File Error Code: " . $_FILES["file"]["error"] . "<br>";
		}
		else
		{
			$htmlMainStr .= "Upload: " . $_FILES["file"]["name"] . "<br>";
			$htmlMainStr .= "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";

			$fw_filepath = '';
			$conf_filepath = '';
			$filepath = '';

			if(file_exists("/home/debian/ctweb.conf")) {
				// first rev BBB
				$conf_filepath = "/home/debian/ctweb.conf";
				$fw_filepath = "/home/debian/upgrade.tgz";
			} else if (file_exists("/Angstrom/Cointerra/cgminer.conf")) {
				// second rev BBB
				$conf_filepath = "/Angstrom/Cointerra/cgminer.conf";
				$fw_filepath = "/tmp/upgrade.tgz";
			} else if (file_exists(get_conf_filepath ())) {
				//windows dev machine
				$conf_filepath = get_conf_filepath ();
				$fw_filepath = "C:\Program Files\AES\cgminer-3.7.0-windows\upgrade.tgz";
			} else {
				echo "Unknown upload point<br>";
			}

			if (isset ($_POST['conf'])) {
				$filepath = $conf_filepath;
			} else if (isset($_POST['fw'])) {
				$filepath = $fw_filepath;
			}

			if (move_uploaded_file($_FILES["file"]["tmp_name"],"$filepath")) {
				//echo "Stored in: " . "/home/debian/" . $_FILES["file"]["name"];
				//$htmlMainStr .= "Stored in: " . $filepath;
			} else {
				$htmlMainStr .= "Store failed :(";
			}
			
			if (isset ($_POST['conf'])) {
				$htmlMainStr .= "Restarting CGMiner in <span id=timer>5 secs</span>";
				$_SESSION['cgminer_restarting'] = 1;
				execInBackground("sudo /etc/init.d/S99cgminer restart");
				header( "refresh:5;url=upload.php" );
			} else if (isset($_POST['fw'])) {
				$htmlMainStr .= "Restarting System in <span id=timer>60 secs</span>";
				$_SESSION['system_restarting'] = 1;
				header( "refresh:55;url=upload.php" );
			}

		}
		 
	}
	else
	{
		$htmlMainStr .= "Invalid file";
	}
} else if (isset($_POST["submit"]) && $_POST["submit"] == "Restart CGMiner") {
	
	//  a bit of a tease...
	$htmlMainStr = "Restarting CGMiner in <span id=timer>5 secs</span>";
	header( "refresh:5;url=upload.php" );
	$_SESSION['cgminer_restarting'] = 1;
	
	//try a hard start
	if (file_exists("C:\Program Files\AES\cgminer-3.7.0-windows\cgminer.exe")) {
		execInBackground("\"C:\Program Files\AES\cgminer-3.7.0-windows\cgminer.exe\" -c \"C:\Program Files\AES\cgminer-3.7.0-windows\cgminer.conf\"");
	} else {
		execInBackground("sudo /etc/init.d/S99cgminer restart");
	}
} 
?>	
	
<script>
	execEle = document.getElementById('main');
	execEle.innerHTML = "<?php echo $htmlMainStr; ?>";
	execEle = document.getElementById('foot');
	execEle.innerHTML = "<?php echo $htmlFoot; ?>";

	<?php 
	if (isset($_POST['fw'])) 
		echo "var count=60";  
	else
		echo "var count=5";
	?>
	
	var counter=setInterval(timer, 1000); //1000 will  run it every 1 second
	function timer()
	{
	  count=count-1;
	  if (count <= 0)
	  {
	     clearInterval(counter);
	     return;
	  }

	 document.getElementById("timer").innerHTML=count + " secs"; // watch for spelling
	}
	
</script>
	
</body>
</html>