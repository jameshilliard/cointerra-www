<?php

if(isset($_POST["submit"])){

echo "<div class='message-container'>";
	echo " <a id='adjustRig_href'    href=\"javascript:toggleDisplay('adjustRig');\">Hide</a><div id='adjustRig'>";
	echo "<span class='message'> Click the Home button to reload without reposting data.</span> <br>";
	
		
	if($_POST["submit"] == "addPool"){
		echo "<pre>"; echo print_r($_POST);	echo "</pre>";
		if($_POST["purl"] && $_POST["rig"]  && $_POST["username"]  && $_POST["password"] ){

			if( $_POST["rig"] == "all"){
				foreach(explode(",",$_POST["rigList"]) as $val){
					$command = "addpool|".$_POST["purl"]. "," .$_POST["username"] . "," . $_POST["password"] ;
					$switch = request($val,$command);
					echo("Rig: ".$_POST["rig"]." Message: " . $switch["STATUS"]["Msg"] . "<br>");
				}
			}
			else{
				$command = "addpool|".$_POST["purl"]. "," .$_POST["username"] . "," . $_POST["password"] ;
				$switch = request($_POST["rig"],$command);
				echo $command . "<br>";
				echo("Rig: ".$_POST["rig"]." Message: " . $switch["STATUS"]["Msg"] . "<br>");
			}
		}
		else{
			echo "<span class='message-bold'>Must select a pool, username, password, and a rig, or all</span>";
		}
	}
	if($_POST["submit"] == "switchpool"){
		if($_POST["poolchange"] && $_POST["rig"] ){
			foreach(explode(",",$_POST["poolchange"]) as $val){
				$arr = explode("|",$val);
				$rig = $arr[0];
				$pid = $arr[1];
				if($rig == $_POST["rig"] || $_POST["rig"] == "all" ){
					$command = "switchpool|".$pid;
					$switch = request($rig,$command);
					echo("Rig: ".$rig." Message: " . $switch["STATUS"]["Msg"] . "<br>");
				}
			}	
		}
		else{
			echo "<span class='message-bold'>Must select a pool and a rig, or all</span>";
		}
	}
	if( $_POST["submit"] == "adjustRig"){
			echo "<pre>"; echo print_r($_POST);	echo "</pre>";
		//  rig, command, gpu, value 
		if($_POST["rig"] && $_POST["command"] && $_POST["gpu"] != 99 && isset($_POST["newValue"]) ){
			if( $_POST["gpu"] == "all"){
				foreach(explode(",",$_POST["gpuList"]) as $val){
					$command = $_POST["command"] . "|".$val. "," .$_POST["newValue"] ;
					$switch = request($_POST["rig"],$command);
					echo("Rig: ".$_POST["rig"]." Message: " . $switch["STATUS"]["Msg"] . "<br>");
				}
			}
			else{
				$command = $_POST["command"] . "|".$_POST["gpu"]. "," .$_POST["newValue"] ;
				$switch = request($_POST["rig"],$command);
				echo("Rig: ".$_POST["rig"]." Message: " . $switch["STATUS"]["Msg"] . "<br>");
			}
		}
		else{
			echo "<span class='message-bold'>Must select rig, command, gpu, and value..  be careful.</span>";
		}
	}

	if( $_POST["submit"] == "lower100"){
				echo "<pre>"; echo print_r($_POST["commandArr"]);	echo "</pre>";
			//  rig, command, gpu, value 
			if($_POST["rig"] && is_array($_POST["commandArr"])){
				foreach($_POST["commandArr"] as $val){
					$switch = request($val);
					echo("Rig: ".$_POST["rig"]." Message: " . $switch["STATUS"]["Msg"] . "<br>");
				}
			}
			else{
				echo "<span class='message-bold'>Must select rig, command, gpu, and value..  be careful.</span>";
			}
		}


	if( $_POST["submit"] == "adjustMulti"){
			echo "<pre>"; echo print_r($_POST);	echo "</pre>";
		//  rig, command, gpu, value 
		if($_POST["rigList"] && $_POST["command"]  && isset($_POST["newValue"]) ){
			foreach(explode(",",$_POST["rigList"]) as $rig){
				foreach(explode(",",$_POST["gpuList"]) as $val){
					$command = $_POST["command"] . "|".$val. "," .$_POST["newValue"] ;
					$switch = request($rig,$command);
					echo("Rig: ".$rig." Message: " . $switch["STATUS"]["Msg"] . "<br>");
				}
			}
		}
		else{
			echo "<span class='message-bold'>Must select rig, command, gpu, and value..  be careful.</span>";
		}
	}



	echo " </div><hr />";
	echo "</div>";
}

?>
