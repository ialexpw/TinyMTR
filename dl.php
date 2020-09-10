<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		dl.php
	*/
	
	include 'config.php';

	header("Content-Description: File Transfer"); 
	header("Content-Type: application/octet-stream"); 
	header("Content-Disposition: attachment; filename=\"$externalFile\""); 

	readfile($externalFile);
?>