<?php
#
# Sample Socket I/O to CGMiner API
#
function getsock($addr, $port)
{
 $socket = null;



 $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
 $timeout = array('sec'=>1,'usec'=>500000);
 socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$timeout);

 if ($socket === false || $socket === null)
 {
	$error = socket_strerror(socket_last_error());
	//print_r($error);
	$msg = "socket create(TCP) failed";
	echo "ERR: $msg '$error'\n";
	//echo "returning null socket_create <br>";
	return null;
 }

 $res = socket_connect($socket, $addr, $port);

 if ($res === false)
 {
	$error = socket_strerror(socket_last_error());
	$msg = "socket connect($addr,$port) failed";
	//echo "ERR: $msg '$error'\n";
	socket_close($socket);
	//echo "returning null socket_connect <br>";
	return null;
 }
 return $socket;
}
#
# Slow ...
function readsockline($socket)
{
 $line = '';
 while (true)
 {
	$byte = socket_read($socket, 1);
	if ($byte === false || $byte === '')
		break;
	if ($byte === "\0")
		break;
	$line .= $byte;
 }
 return $line;
}
function request_no_rx($miner, $cmd)
{
	if(ping($miner)){
		$socket = getsock($miner, 4028);
		if ($socket != null)
		{
			socket_write($socket, $cmd, strlen($cmd));
			socket_close($socket);
		}
	}
}
function test_cgminer_socket($miner)
{
	if(ping($miner)){
		$socket = getsock($miner, 4028);
		if ($socket != null)
		{
			socket_close($socket);
			return true;
		}
	}
	return false;
}
#
function request($miner, $cmd)
{
	if(ping($miner)){
	 $socket = getsock($miner, 4028);
	 if ($socket != null)
	 {
		socket_write($socket, $cmd, strlen($cmd));
		$line = readsockline($socket);
		socket_close($socket);

		//print "CMD: " . $cmd . "\nLINE: " . $line . "\n";
		
		if (strlen($line) == 0)
		{
			echo "WARN: '$cmd' returned nothing\n";
			return $line;
		}

		//print "$cmd returned '$line'\n";

		if (substr($line,0,1) == '{')
			return json_decode($line, true);

		$data = array();

		$objs = explode('|', $line);
		foreach ($objs as $obj)
		{
			if (strlen($obj) > 0)
			{
				$items = explode(',', $obj);
				$item = $items[0];
				$id = explode('=', $items[0], 2);
				if (count($id) == 1 or !ctype_digit($id[1]))
					$name = $id[0];
				else
					$name = $id[0].$id[1];

				if (strlen($name) == 0)
					$name = 'null';

				if (isset($data[$name]))
				{
					$num = 1;
					while (isset($data[$name.$num]))
						$num++;
					$name .= $num;
				}

				$counter = 0;
				foreach ($items as $item)
				{
					$id = explode('=', $item, 2);
					if (count($id) == 2)
						$data[$name][$id[0]] = $id[1];
					else
						$data[$name][$counter] = $id[0];

					$counter++;
				}
			}
		}

		return $data;
	 }
	}
	return null;
}

function api($rig, $cmd)
{
	$haderror=0;
	$error=0;
	$miner = "127.0.0.1";
	$port = "4028";
	$hidefields = array();
	
	echo "\n<!--" . $miner . " " . $port . " " . $rig . " " . $cmd . "\n";
	print_r($hidefields);
	echo "-->";

	$socket = getsock($rig, $miner, $port);
	 if ($socket != null)
	 {
		socket_write($socket, $cmd, strlen($cmd));
		$line = readsockline($socket);
		socket_close($socket);	

		print_r($line);
		
		if (strlen($line) == 0)
		{
			$haderror = true;
			$error = "WARN: '$cmd' returned nothing\n";
			return $line;
		}

		print "$cmd returned '$line'\n";

		$line = api_convert_escape($line);

		$data = array();

		$objs = explode('|', $line);
		foreach ($objs as $obj)
			{
			if (strlen($obj) > 0)
				{
					$items = explode(',', $obj);
					$item = $items[0];
					$id = explode('=', $items[0], 2);
					if (count($id) == 1 or !ctype_digit($id[1]))
						$name = $id[0];
						else
						$name = $id[0].$id[1];

						if (strlen($name) == 0)
					$name = 'null';

					$sectionname = preg_replace('/\d/', '', $name);

					if (isset($data[$name]))
					{
					$num = 1;
					while (isset($data[$name.$num]))
						$num++;
						$name .= $num;
				}

				$counter = 0;
						foreach ($items as $item)
						{
						$id = explode('=', $item, 2);

								if (isset($hidefields[$sectionname.'.'.$id[0]]))
							continue;

							if (count($id) == 2)
							$data[$name][$id[0]] = revert($id[1]);
						else
							$data[$name][$counter] = $id[0];

				$counter++;
			}
		}
	}
	return $data;
 }
 return null;
}

function ping($host, $timeout = 2) {
	return true;
}

function pingo($host, $timeout = 2) {
		/* ICMP ping packet with a pre-calculated checksum */
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
		$socket  = socket_create(AF_INET, SOCK_RAW, 1);
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
		socket_connect($socket, $host, null);

		$ts = microtime(true);
		socket_send($socket, $package, strLen($package), 0);
		if (socket_read($socket, 255))
				$result = microtime(true) - $ts;
		else    $result = false;
		socket_close($socket);

		return $result;
}

?>
