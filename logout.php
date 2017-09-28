<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		logout.php
	*/
	
	include ("config.php");
	session_destroy();
	header("Location: " . $siteLoc . "login" . $x);
?>