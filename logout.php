<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		logout.php
	*/
	
	include ("config.php");

	# Destroy the session
	session_destroy();
	header("Location: " . $siteLoc . "login" . $x);
?>