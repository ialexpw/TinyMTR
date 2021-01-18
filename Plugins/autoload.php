<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		autoload.php
	*/
	
	#######################################################################################
	// DO NOT CHANGE THIS FILE - LEAVE VALUES AT 0, THE PLUGINS WILL ACTIVATE THEMSELVES //
	#######################################################################################
	
	/*
	 * Multi-Server Plugin
	 * Allows you to ping from external servers in other locations
	*/
	$MULTISERV = 0;
	
	/*
	 * Multi-User Plugin
	 * Allows multiple users to register and use TinyMTR
	*/
	$MULTIUSER = 0;
	
	/*
	 * Stripe Payments Plugin
	 * Allows integration of Stripe, to buy credits to use on SMS'
	 * Release: TBA
	*/
	$STRIPEINT = 0;

	/*
	 * Port Monitor Plugin
	 * Allows ports other than 80 and 243 to be monitored inside TinyMTR
	 * Release: TBA
	*/
	$PORTMONITOR = 0;

	/* Plugin Development - Do not edit unless developing your own */
	if(is_file(__DIR__ . '/MultiServer.php')) { include __DIR__ . '/MultiServer.php'; }
	if(is_file(__DIR__ . '/MultiUser.php')) { include __DIR__ . '/MultiUser.php'; }
	if(is_file(__DIR__ . '/StripeInit.php')) { include __DIR__ . '/StripeInit.php'; }
	if(is_file(__DIR__ . '/PortMonitor.php')) { include __DIR__ . '/PortMonitor.php'; }
?>