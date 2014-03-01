<?php
require_once 'includes/apiSocket.php';
require_once 'common.php';

//error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '0');  //dev version
date_default_timezone_set('America/New_York');
?>
 
<?php echo $head; /*common.php */?>

	<?php echo $menu; /*common.php */?>

<table border=1 cellspacing=0 cellpadding=2>
	<tr>
		<td valign='top'>
			<div id='execSummary'></div>
		</td>
	</tr>
</table>

<?php echo $foot; /*common.php */?>

<script type="text/javascript">
    document.write("\<script src='//ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js' type='text/javascript'>\<\/script>");
</script>

<script>
	execEle = document.getElementById('execSummary');
	execEle.innerHTML = $.get('status_frame.php', myCallback);
 	execEle = document.getElementById('foot');
	execEle.innerHTML = "<?php echo getVersionFooter(); ?>";
	
	function poll(){
	 $.get('status_frame.php', myCallback);
	}
	function myCallback(data, textStatus){
	  $('#execSummary').html(data); // just replace a chunk of text with the new text
	  setTimeout(poll, 5000);
	}
	
</script>

</body>
</html>
