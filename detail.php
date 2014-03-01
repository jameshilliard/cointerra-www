<?php
require_once 'includes/apiSocket.php';
require_once 'common.php';

//error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '0');  //dev version
date_default_timezone_set('America/New_York');
?>
 
<?php echo $head; /*common.php */?>

	<?php echo $menu; /*common.php */?>	
	
	
	
	<table  border=1 cellspacing=0 cellpadding=2>
	<tr>
		<td valign='top' >
			<div id='main'>
				<div id='sum_button'><div class='expand-button' id=sum_plus>[+] </div><div class='expand-button' style='display:none;' id=sum_minus>[-] </div>&nbsp Summary</div> <div style='display:none;' id='sum_table'> Loading ... 	</div>			
				<div id='dev_button'><div class='expand-button' id=dev_plus>[+] </div><div class='expand-button' style='display:none;' id=dev_minus>[-] </div>&nbsp Devices</div> <div style='display:none;' id='dev_table'> Loading ... 	</div>			
				<div id='pool_button'><div class='expand-button' id=pool_plus>[+] </div><div class='expand-button' style='display:none;' id=pool_minus>[-] </div>&nbsp Pools</div> <div style='display:none;' id='pool_table'> Loading ... </div>			
				<div id='stat_button'><div class='expand-button' id=stat_plus>[+] </div><div class='expand-button' style='display:none;' id=stat_minus>[-] </div>&nbsp Stats</div> <div style='display:none;' id='stat_table'> Loading ... 	</div>
			</div>
		</td>
		
	</tr>
	</table>

<?php echo $foot; /*common.php */?>

<?php $htmlFoot = getVersionFooter(); ?>

<?php

$htmlMainStr = '';
$htmlDetailStr = '';
$minerOffLineArr = array();

$firstMiner = $minerList[0]; 

foreach ($minerList as $m) {

	$s = request($m,"summary");
	if($s == null){
		array_push($minerOffLineArr, $m);
	}
}

if(count($minerOffLineArr) > 0){
	$htmlMainStr = count($minerOffLineArr)." TerraMiner";
	if (count($minerList)>1) $htmlMainStr .= "s";
	$htmlMainStr .= " Offline: <ul>";
	foreach($minerOffLineArr as $minerOff){
		$htmlMainStr .= "<li> " . $minerOff . "</span>";
	}
	$htmlMainStr .= "</ul><hr>";
}

$htmlMainStr .= $htmlDetailStr;

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

<script type="text/javascript">
    document.write("\<script src='//ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js' type='text/javascript'>\<\/script>");
</script>

<script>
<?php 
if (count($minerOffLineArr) > 0) {
	echo "execEle = document.getElementById('main');";
	echo "execEle.innerHTML = \"" . $htmlMainStr ."\";";
} 
?>
	execEle = document.getElementById('foot');
	execEle.innerHTML = "<?php echo $htmlFoot; ?>";

	execEle = document.getElementById('sum_table');
	execEle.innerHTML = $.get("api_to_table.php?api=summary&miner=<?php echo $firstMiner; ?>", fill_sum);
	
	function poll(){
		$.get('api_to_table.php?api=summary&miner=<?php echo $firstMiner; ?>', fill_sum);
		$.get('api_to_table.php?api=devs&miner=<?php echo $firstMiner; ?>', fill_dev);
		$.get('api_to_table.php?api=pools&miner=<?php echo $firstMiner; ?>', fill_pool);
		$.get('api_to_table.php?api=stats&miner=<?php echo $firstMiner; ?>', fill_stat);
	}
	function fill_sum(data, textStatus){
		$('#sum_table').html(data); 
		setTimeout(poll, 5000);
	}
	
	function fill_dev(data, textStatus){
		$('#dev_table').html(data);
	}
	function fill_pool(data, textStatus){
		$('#pool_table').html(data);
	}
	function fill_stat(data, textStatus){
		$('#stat_table').html(data);
	}
	
	function toggle_div_vis(id) {
		if(document.getElementById(id).style.visibility == "visible") {
			document.getElementById(id).style.visibility = "hidden";
		} else {
			document.getElementById(id).style.visibility = "visible";
		}
	}

	$( "#sum_button" ).click( function() { $( "#sum_table" ).toggle(); $( "#sum_plus" ).toggle(); $( "#sum_minus" ).toggle();});
	$( "#dev_button" ).click( function() { $( "#dev_table" ).toggle(); $( "#dev_plus" ).toggle(); $( "#dev_minus" ).toggle();});
	$( "#pool_button" ).click( function() { $( "#pool_table" ).toggle(); $( "#pool_plus" ).toggle(); $( "#pool_minus" ).toggle();});
	$( "#stat_button" ).click( function() { $( "#stat_table" ).toggle(); $( "#stat_plus" ).toggle(); $( "#stat_minus" ).toggle();});

	$( document ).ready(function() {
		//$( "#sum_table" ).toggle();
		//$( "#dev_table" ).toggle();
		//$( "#pool_table" ).toggle();
		//$( "#stat_table" ).toggle();
	});
</script>
</body>
</html>
