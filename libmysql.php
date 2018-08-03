<?php
$mysql_dataconn["h"]="localhost";	// hostname
$mysql_dataconn["u"]="root";		// username
$mysql_dataconn["p"]="toor";		// password
$mysql_dataconn["d"]="database";	// database
$mysql_dataconn["n"]="3306";		// port (default: 3306)
$mysql_autostart=true;				// autostart mysql_on() at the first mysql_do() or mysql_es()
$mysql_printerrors=true;			// print all errors or keep them secret
$mysql_dieifstarterror=true;		// die() if error in mysql_on()
$mysql_ratelimit["a"]=false;		// rate limiter activation, works only in mysql_do()
$mysql_ratelimit["n"]=100;			// rate limiting after N queries if active
$mysql_ratelimit["t"]=4;			// rate limiter sleep(seconds) time
$mysql_resfree=false;				// use free() at the end mysql_do()

function mysql_on() {	// open mysql connection
	global $mysql_conn, $mysql_conn_active, $mysql_dataconn, $mysql_printerrors, $mysql_dieifstarterror;
	// check if already connected and then mysql_off()
	if ($mysql_conn_active) {
		mysql_off();
	};
	// connection
	$mysql_conn=mysqli_init();
	if (!$mysql_conn->real_connect($mysql_dataconn["h"], $mysql_dataconn["u"], $mysql_dataconn["p"], $mysql_dataconn["d"], $mysql_dataconn["n"])) {
		// error triggered
		if ($mysql_printerrors) {	//report
			echo("[MySQL_on error: (".mysqli_connect_errno().") ".mysqli_connect_error()."]");
		};
		if ($mysql_dieifstarterror) {	//die
			die();
		};
		return false;
	};
	// set active
	$mysql_conn_active=true;
	return true;
};

function mysql_off() {	// close mysql connection
	global $mysql_conn, $mysql_conn_active;
	$mysql_conn->close();
	$mysql_conn_active=false;
	return true;
};

function mysql_do($sql, $return=false, $many=false) {	// execute mysql query, if returns results $return=true, if returns bidimensional array $return=true and $many=true
	global $mysql_conn, $mysql_conn_active, $mysql_autostart, $mysql_printerrors, $mysql_ratelimit, $mysql_resfree;
	// check if active mysql connection
	if (!$mysql_conn_active) {
		if ($mysql_autostart) {
			mysql_on();
		} else {
			return false;
		};
	};
	// ratelimiter
	if ($mysql_ratelimit["a"]) {
		if (!isset($mysql_ratelimit["c"])) {
			$mysql_ratelimit["c"]=0;
		};
		$mysql_ratelimit["c"]++;
		if ($mysql_ratelimit["c"] > $mysql_ratelimit["n"]) {
			$mysql_ratelimit["c"]=0;
			sleep($mysql_ratelimit["t"]);
		};
	};
	// query
	$res=$mysql_conn->query($sql);
	if (!$res) { //error
		if ($mysql_printerrors) {	//report
			echo("[MySQL_do error: ".$mysql_conn->error."]");
		};
		return false;
	};
	// results
	if ($return) {	// parse returned results as array
		if ($many) {	// bidimensional
			$finale=array();
			while ($row=$res->fetch_assoc()) {
				$finale[]=$row;
			};
		} else {		// only one row
			$finale=$res->fetch_assoc();
		};
	} else {		// only query execution
		$finale=true;
	};
	// close and return
	if ($mysql_resfree) {
		$res->free();
	};
	return $finale;
};

function mysql_es($in, $html=true) {	// you have to escape strings before using in mysql_do(), htmlspecialchars if $html=true
	global $mysql_conn, $mysql_conn_active, $mysql_autostart;
	// check if active mysql connection
	if (!$mysql_conn_active) {
		if ($mysql_autostart) {
			mysql_on();
		} else {
			return false;
		};
	};
	// other funcions
	if ($html) {	// (default)
		$temp=trim(htmlspecialchars($in));
	} else {		// nothing else
		$temp=$in;
	};
	//return
	return $mysql_conn->real_escape_string($temp);
};
?>