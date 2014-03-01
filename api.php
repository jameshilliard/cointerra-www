<?php

include "functions.php";                    
                        
if (isset($_GET['jsonp'])) header('Content-type: text/javascript');       
else  header('Content-type: text/json');
                          
$f = "__".$_GET['f']."__";
$args = $_GET['args'];                                                    
if (function_exists($f)){                                                 
  $a = array("result" => $f($args));                               
  if (isset($_GET['jsonp'])) $a['jsonpinfo_servername'] = $_SERVER['SERVER_NAME'];
  $res = json_encode($a);                                          
}                                                                  
else $res = json_encode(array("error" => "unknown function '$f'"));
                                
if (isset($_GET['jsonp'])) echo $_GET['jsonp']."($res);";
else echo $res;                                         
               
?>
