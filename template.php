<?php
require_once 'common.php';

error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '1');  //dev version
date_default_timezone_set('America/New_York');
?>
 
<?php echo $head; /*common.php */?>

	<?php echo $menu; /*common.php */?>	

	<table  border=1 cellspacing=0 cellpadding=2>
	<tr>
		<td valign='top' >
			<div id='main'></div>
		</td>
		<td valign='top' > 
			<div id='side'>
				<div id='side1'></div>
				<hr>
				<div id='side2'></div>
				<hr>
			</div>
		</td>
	</tr>
	</table>

	<?php echo $foot; /*common.php */?>
 
 <?php $htmlFoot = getVersionFooter(); ?>
	
<?php

$htmlMainStr = "main";
$htmlSide1Str = "side1";
$htmlSide2Str = "side2";

?>	
	
<script>
	execEle = document.getElementById('main');
	execEle.innerHTML = "<?php echo $htmlMainStr; ?>";
	execEle = document.getElementById('side1');
	execEle.innerHTML = "<?php echo $htmlSide1Str; ?>";
	execEle = document.getElementById('side2');
	execEle.innerHTML = "<?php echo $htmlSide2Str; ?>";
	execEle = document.getElementById('foot');
	execEle.innerHTML = "<?php echo $htmlFoot; ?>";
</script>

</body>
</html>