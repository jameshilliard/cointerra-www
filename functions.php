<?php

function __version__()
{              
  return (float)file_get_contents("/version.txt");
}

?>
