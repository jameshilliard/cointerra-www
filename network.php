<?php
require_once 'common.php';
//error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '0');  //dev version
date_default_timezone_set('America/New_York');
?>

<?php 

$ip = (isset($_SERVER['SERVER_ADDR']))?$_SERVER['SERVER_ADDR']:"0.0.0.0";
$net = "255.255.255.0";
$gate = "0.0.0.0";
$dhcp_enabled = false;

if ( file_exists("C:/workspace/cointerra/interfaces")) {
	$ifile = "C:/workspace/cointerra/interfaces";
} else {
	$ifile = "/Angstrom/Cointerra/interfaces";
}

if ( file_exists("C:/workspace/cointerra/resolv")) {
	$rfile = "C:/workspace/cointerra/resolv";
} else {
	$rfile = "/Angstrom/Cointerra/resolv.conf";
}

//read dhcp, ip, etc:
if ( file_exists($ifile)) {

	$interfaces = file($ifile);

	$dhcp = preg_grep("/dhcp/", $interfaces);
	$static = preg_grep("/static/", $interfaces);

	if(!empty($dhcp)) {
		$dhcp_enabled = true;
		$ip = $_SERVER['SERVER_ADDR'];
	} else if (!empty($static)) {
		$dhcp_enabled = false;

		$ret = preg_grep("/address /", $interfaces);
		foreach($ret as $str) {
			$ip = substr(trim($str), strlen("address "));
		}

		$ret = preg_grep("/netmask /", $interfaces);
		foreach($ret as $str) {
			$net = substr(trim($str), strlen("netmask "));
		}

		$ret = preg_grep("/gateway /", $interfaces);
		foreach($ret as $str) {
			$gate = substr(trim($str), strlen("gateway "));
		}
	}

} else {
	//echo "no interfaces file";
}

//dns:
$dnsArray = array();
if ( file_exists($rfile)) {

	$dns = file($rfile);
	$ret = preg_grep("/nameserver/", $dns);
	$i=1;

	foreach($ret as $nameserver) {
		$dnsArray[$i] = strtok(substr(trim($nameserver), strlen("nameserver ")), '#');
		$i++;
	}
} else {
	//echo "no dns file";
}

$write_ifile = 0;
$write_rfile = 0;

if(isset($_POST['ip']) && ($_POST['ip'] != '')) {
	$ip = $_POST['ip'];
	$write_ifile = 1;
}
if(isset($_POST['mask']) && ($_POST['mask'] != '')) {
	$net = $_POST['mask'];
	$write_ifile = 1;
}
if(isset($_POST['gateway']) && ($_POST['gateway'] != '')) {
	$gate = $_POST['gateway'];
	$write_ifile = 1;
}

if (isset($_POST['dhcp'])) {
	//rewrite interfaces with dhcp enabled
	$new_file = array("# Configure Loopback\n", "auto lo eth0\n", "iface lo inet loopback\n", "iface eth0 inet dhcp\n");
	file_put_contents($ifile, $new_file);
	$dhcp_enabled = true;
	exec("sudo /sbin/reboot");
} else if ($write_ifile) {
	//rewrite interfaces with input settings
	$new_file = array("# Configure Loopback\n", "auto lo eth0\n", "iface lo inet loopback\n",
			"iface eth0 inet static\n", "address ".trim($ip)."\n", "netmask  ".trim($net)."\n", "gateway  ".trim($gate)."\n");
	file_put_contents($ifile, $new_file);
	exec("sudo /sbin/reboot");
} 

//dns:
$new_file = array();
if(isset($_POST['dns1']) && ($_POST['dns1'] != '')) {
	array_push($new_file, "nameserver ".$_POST['dns1']."\n");
	$write_rfile = 1;
} else if (isset($dnsArray[1]) && $dnsArray[1] != '') {
	array_push($new_file, "nameserver ".$dnsArray[1]."\n");
}
if(isset($_POST['dns2']) && ($_POST['dns2'] != '')) {
	array_push($new_file, "nameserver ".$_POST['dns2']."\n");
	$write_rfile = 1;
} else if (isset($dnsArray[2]) && $dnsArray[2] != '') {
	array_push($new_file, "nameserver ".$dnsArray[2]."\n");
}
if(isset($_POST['dns3']) && ($_POST['dns3'] != '')) {
	array_push($new_file, "nameserver ".$_POST['dns3']."\n");
	$write_rfile = 1;
} else if (isset($dnsArray[3]) && $dnsArray[3] != '') {
	array_push($new_file, "nameserver ".$dnsArray[3]."\n");
}

if ($write_rfile) {
	file_put_contents($rfile, $new_file);
	exec("sudo /sbin/reboot");
}

//read dhcp, ip, etc:
if ( file_exists($ifile)) {

	$interfaces = file($ifile);

	$dhcp = preg_grep("/dhcp/", $interfaces);
	$static = preg_grep("/static/", $interfaces);
	
	if(!empty($dhcp)) {
		$dhcp_enabled = true;
		$ip = $_SERVER['SERVER_ADDR'];
	} else if (!empty($static)) {
		$dhcp_enabled = false;
		
		$ret = preg_grep("/address /", $interfaces);
		foreach($ret as $str) {
			$ip = substr(trim($str), strlen("address "));
		}
		
		$ret = preg_grep("/netmask /", $interfaces);
		foreach($ret as $str) {
			$net = substr(trim($str), strlen("netmask "));
		}
		
		$ret = preg_grep("/gateway /", $interfaces);
		foreach($ret as $str) {
			$gate = substr(trim($str), strlen("gateway "));
		}
	}

} else {
	//echo "no interfaces file";
}

//dns:
$dnsArray = array();

if ( file_exists($rfile)) {

	$dns = file($rfile);
	$ret = preg_grep("/nameserver/", $dns);
	$i=1;
	$dnsArray = array();

	foreach($ret as $nameserver) {
		$dnsArray[$i] = strtok(substr(trim($nameserver), strlen("nameserver ")), '#');
		$i++;
	}	
} else {
	//echo "no dns file";
}

?>

<?php echo $head; /*common.php */?>

	<?php echo $menu; /*common.php */?>

	<table  border=1 cellspacing=0 cellpadding=2>
		<tr>
			<td valign='top' >
				<div id='execPools'>
					<ul>
						<div id='network'>
							<div id='static_ip' >
								<form name='network_settings' method='post' action='network.php'>
								  &nbsp&nbsp<input type=checkbox name='dhcp' <?php if($dhcp_enabled) {echo " checked ";} ?>  			onclick="toggleDisabled(this.checked)"></input>&nbsp&nbsp&nbsp&nbspEnable DHCP<br><br>
								  <input type='text' id='ip' name='ip' size='30' placeholder='IP Address: <?php echo $ip?>' 			oninput="ValidateAddress(this, 'IP')" <?php if($dhcp_enabled) {echo " disabled ";} ?>> <br>
								  <input type='text' id='mask' name='mask' size='30' placeholder='Subnet Mask: <?php echo $net?>' 		oninput="ValidateAddress(this, 'Subnet Mask')" <?php if($dhcp_enabled) {echo " disabled ";} ?>> <br>
								  <input type='text' id='gateway' name='gateway' size='30' placeholder='Gateway: <?php echo $gate?>' 	oninput="ValidateAddress(this, 'Gateway')"<?php if($dhcp_enabled) {echo " disabled ";} ?>> <br><br>
								  <input type='text' id='dns1' name='dns1' size='30' placeholder='DNS 1: <?php if(isset($dnsArray[1])) echo $dnsArray[1];?>' oninput="ValidateAddress(this, 'DNS')"  <?php if($dhcp_enabled) {echo " disabled ";} ?>> <br>
								  <input type='text' id='dns2' name='dns2' size='30' placeholder='DNS 2: <?php if(isset($dnsArray[2])) echo $dnsArray[2];?>' oninput="ValidateAddress(this, 'DNS')"  <?php if($dhcp_enabled) {echo " disabled ";} ?>> <br>
								  <input type='text' id='dns3' name='dns3' size='30' placeholder='DNS 3: <?php if(isset($dnsArray[3])) echo $dnsArray[3];?>' oninput="ValidateAddress(this, 'DNS')"  <?php if($dhcp_enabled) {echo " disabled ";} ?>> <br><br>
								  <input type='submit' value='Apply' name='update_network'>
								</form>
							</div>
						</div>
					</ul>
				</div>
			</td>
		</tr>
	</table>
 
 	<?php echo $foot; /*common.php */?>
 
<?php $htmlFoot = getVersionFooter(); ?>
 
<script>
 
function toggleDisabled(_checked) {
	document.getElementById('ip').disabled = _checked ? true : false;
    document.getElementById('mask').disabled = _checked ? true : false;
    document.getElementById('gateway').disabled = _checked ? true : false;
    document.getElementById('dns1').disabled = _checked ? true : false;
    document.getElementById('dns2').disabled = _checked ? true : false;
    document.getElementById('dns3').disabled = _checked ? true : false;
}

function inet_pton (a) {
  // From: http://phpjs.org/functions
  // +   original by: Theriault
  // *     example 1: inet_pton('::');
  // *     returns 1: '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0'
  // *     example 2: inet_pton('127.0.0.1');
  // *     returns 2: '\x7F\x00\x00\x01'

  var r, m, x, i, j, f = String.fromCharCode;
  m = a.match(/^(?:\d{1,3}(?:\.|$)){4}/); // IPv4
  if (m) {
    m = m[0].split('.');
    m = f(m[0]) + f(m[1]) + f(m[2]) + f(m[3]);
    // Return if 4 bytes, otherwise false.
    return m.length === 4 ? m : false;
  }
  r = /^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/;
  m = a.match(r); // IPv6
  if (m) {
    // Translate each hexadecimal value.
    for (j = 1; j < 4; j++) {
      // Indice 2 is :: and if no length, continue.
      if (j === 2 || m[j].length === 0) {
        continue;
      }
      m[j] = m[j].split(':');
      for (i = 0; i < m[j].length; i++) {
        m[j][i] = parseInt(m[j][i], 16);
        // Would be NaN if it was blank, return false.
        if (isNaN(m[j][i])) {
          return false; // Invalid IP.
        }
        m[j][i] = f(m[j][i] >> 8) + f(m[j][i] & 0xFF);
      }
      m[j] = m[j].join('');
    }
    x = m[1].length + m[3].length;
    if (x === 16) {
      return m[1] + m[3];
    } else if (x < 16 && m[2].length > 0) {
      return m[1] + (new Array(16 - x + 1)).join('\x00') + m[3];
    }
  }
  return false; // Invalid IP.
}

function ValidateAddress(input, note)
{
	if (input.value == "0.0.0.0") {
		input.setCustomValidity(note + ' of ' + input.value + ' is probably not what you intended, try something else');
	} else if (inet_pton(input.value)) {
		input.setCustomValidity('');
	} else {
		input.setCustomValidity(input.value + ' is not a valid ' + note);
	}
	return;
	
}

execEle = document.getElementById('foot');
execEle.innerHTML = "<?php echo $htmlFoot; ?>";
 
</script>
 
</body>
</html>
