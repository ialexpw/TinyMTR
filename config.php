<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		config.php
	*/
	
	if(!headers_sent()) {
		session_start();
	}

	date_default_timezone_set("Europe/London");

	include 'inc/HTTP_R.class.php';
	include 'inc/tinymtr.class.php';
	include 'Plugins/autoload.php';
	
	$TinyMTR = new TinyMTR();
	
	# Site info
	$siteName = 'TinyMTR';
	$siteLoc = 'http://yoursite.com/TinyMTR/';	// With final '/'
	$externalFile = 'TinyMTR.php';	// View the documentation
	$usePrettyURLs = 0;	// Do not need to change ~ Docs coming in v1.3.0

	# Language
	$siteLang = 'en';

	# Version
	$version = '1.5.0';

	# Option to disable the need for a TinyMTR.php file
	$reqMtr = false;
	
	# Records
	$keepLast = 31; // How many days to keep records for (should normally leave this as-is) put 0 to not remove any

	# SQL credentials
	define('HOST', 'localhost');
	define('DBSE', 'Database_name');
	define('USER', 'Database_user');
	define('PASS', 'Database_pass');

	# Email Sending
	$useEmail = 0;
	
	############################
	/* No need to edit below! */
	############################
	
	# Cron debug
	$cDebug = 0;

	# Cron interval in minutes ~ THIS IS WHAT YOU HAVE SET IN CRONTAB/CPANEL
	$cInterval = 5;
	
	# Using pretty URLs, so no extension
	if($usePrettyURLs) {
		$x = '';
	}else{
		$x = '.php';
	}

	# Find the language file, if not - default to English
	if(is_file(__DIR__ . '/inc/' . $siteLang . '.php')) {
		include __DIR__ . '/inc/' . $siteLang . '.php';
	}else{
		include __DIR__ . '/inc/en.php';
	}
	
	# Work out how many records to keep
	$keepLast = $keepLast * (1440/$cInterval);
	
	# Database connection
	$sqlError = 0;
	
	try {
		$dbh = new PDO('mysql:host=' . HOST . ';dbname=' . DBSE, USER, PASS);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		$sqlError = 1;
		$errorLogged = $e->getMessage();
	}
?>