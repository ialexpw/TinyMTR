<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		iAlex (http://codecanyon.net/iAlex)
		logout.php
	*/
	
	include ("config.php");
	session_destroy();
	header("Location: " . $siteLoc . "login" . $x);
?>