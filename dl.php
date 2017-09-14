<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		iAlex (http://codecanyon.net/iAlex)
		dl.php
	*/
	
	include 'config.php';

	header("Content-Description: File Transfer"); 
	header("Content-Type: application/octet-stream"); 
	header("Content-Disposition: attachment; filename=\"$externalFile\""); 

	readfile($externalFile);
?>