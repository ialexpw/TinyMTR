<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		tinymtr.class.php
	*/

	class TinyMTR {
		function getSystemMemInfo() {       
			$memData = explode("\n", file_get_contents("/proc/meminfo"));
			$memInfo = array();
			foreach ($memData as $line) {
				list($key, $val) = explode(":", $line);
				$memInfo[$key] = trim($val);
			}
			return $memInfo;
		}
	}

	/* Remove prefixes of websites */
	function removePrefix($input) {
		$serAdd = str_replace("http://", "", $input);
		$serAdd = str_replace("https://", "", $serAdd);
		$serAdd = str_replace("www.", "", $serAdd);
		$serAdd = str_replace("ftp.", "", $serAdd);
		//$serAdd = strtolower($serAdd);

		return $serAdd;
	}
	
	/* Validate different input */
	function validateInput($input, $type) {
		/* Validate email */
		if($type == 'email') {
			$result = filter_var($input, FILTER_VALIDATE_EMAIL);
			
			/* Check result */
			if($result) {
				return true;
			}else{
				return false;
			}
		}
		
		/* Validate username */
		if($type == 'user') {
			if(preg_match('/^[a-zA-Z0-9]{3,}$/', $input)) {
				return true;
			}else{
				return false;
			}
		}
		
		/* Validate server ID */
		if($type == 'id') {
			if (preg_match('/^[1-9][0-9]{0,20}$/', $input)) {
				return true;
			}else{
				return false;
			}
		}
	}

	# Function to validate the API hash
	function validateHash($input) {
		$hashString = sha1(md5($input));
		$reHash = md5(sha1($input));
		$finalHash = sha1($hashString . $reHash);

		$cutString = substr($finalHash,0,6);
		return $cutString;
	}
	
	# Ping a domain
	function pingDomain($domain, $secure) {
		$staTime = microtime(true);
		
		# If using SSL
		if($secure) {
			$fsOpen = @fsockopen ($domain, 443, $errNo, $errstr, 2);
		}else{
			$fsOpen = @fsockopen ($domain, 80, $errNo, $errstr, 2);
		}

		$stoTime = microtime(true);
		$theStatus = 0;

		# Site is down
		if (!$fsOpen) {
			@fclose($fsOpen);
			$theStatus = -1;  
		}else{
			fclose($fsOpen);
			$theStatus = ($stoTime - $staTime) * 1000;
			$theStatus = floor($theStatus);
		}
		return $theStatus;
	}

	# Check for an external file
	function checkExternal($domain, $exfile) {
		$theFile = 'http://' . $domain . '/' . $exfile;

		if (@fopen($theFile, "r")) {
			return 1;
		}else{
			return 0;
		}
	}

	# Check to see if they have the TinyMTR file uploaded
	function checkMTR($domain, $external, $secure, $skip=0) {
		if(!$skip) {
			if($secure) {
				$headers = get_headers('https://' . $domain);
			}else{
				$headers = get_headers('http://' . $domain);
			}
			
			$headers = substr($headers[0], 9, 3);
		}
		

    	if(($headers != 503 && $headers != 404) || $skip ) {
    		if($secure){
				$getMTR = HTTP_R::M0('https://' . $domain . '/' . $external . '?verify');
			}else{
				$getMTR = HTTP_R::M0('http://' . $domain . '/' . $external . '?verify');
			}

			# Is the string there?
			$foundPos = strpos($getMTR, 'active');

			# Cannot find the file
			if ($foundPos === false) {
				return false;
			}else{
				return true;
			}
    	}else{
    		return false;
    	}
	}

	# Check system uptime
	function sysUptime() {
		$array = array();
		$fh = fopen('/proc/uptime', 'r');
		$uptime = fgets($fh);
		fclose($fh);
		$uptime = explode('.', $uptime, 2);
		$array['uptime'] = sec2human($uptime[0]);
		return $array['uptime'];
	}

	# Convert to readable format
	function sec2human($time) {
		$seconds = $time%60;
		$mins = floor($time/60)%60;
		$hours = floor($time/60/60)%24;
		$days = floor($time/60/60/24);
		return $days > 0 ? $days . ' day'.($days > 1 ? 's' : '') : $hours.':'.$mins.':'.$seconds;
	}

	# Valid domain check
	function validDomain($domainCheck) {
	    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domainCheck) //valid chars check
			&& preg_match("/^.{1,253}$/", $domainCheck) //overall length check
			&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domainCheck)   ); //length of each label
	}

	####################
	// Cron functions //
	####################

	# Function to ping from external server
	function pingFrom($srv, $toping, $secure) {
		/* Create the URL to the external file */
		$output = HTTP_R::M0('http://' . $srv . '/Ex-MTR.php?domain=' . $toping . '&secure=' . $secure);

		# Get just the part we need
		$getPing = json_decode($output, true);
		return $getPing['response'];
	}
	
	# Function to send an SMS message ~ unused
	function sendSMS($txtNum, $nexKey, $nexSecret, $txtTitle, $serAddr, $time, $id, $back_up = 0) {
		$nexmoSMS = new NexmoMessage($nexKey, $nexSecret);
		/* Server is coming back up! */
		if($back_up) {
			$info = $nexmoSMS->sendText($txtNum, $txtTitle, 'Server: ID (' . $id . ') -> ' . $serAddr . ' seems to be back online @ ' . $time . '!');
		}else{
			$info = $nexmoSMS->sendText($txtNum, $txtTitle, 'Server: ID (' . $id . ') -> ' . $serAddr . ' seems to be offline @ ' . $time . '!');
		}
	}

	# Send the email ~ need to update
	function sendEmail($email, $serAddr, $id, $back_up = 0) {
		$mailer = new Simple_Mail();
		
		if($back_up) {
			$send = $mailer->setTo($email, 'TinyMTR')
				->setSubject('Status-Alert - ' . $serAddr)
				->setFrom('noreply@picotory.com', 'TinyMTR')
				->addMailHeader('Reply-To', 'noreply@picotory.com', 'TinyMTR')
				->addGenericHeader('X-Mailer', 'PHP/' . phpversion())
				->addGenericHeader('Content-Type', 'text/html; charset="utf-8"')
				->setMessage('<p>Status<br /><br />ID(' . $id . ') ' . $serAddr . ' seems to be back online!</p>')
				->setWrap(78)
				->send();
		}else{
			$send = $mailer->setTo($email, 'TinyMTR')
				->setSubject('Status-Alert - ' . $serAddr)
				->setFrom('noreply@picotory.com', 'TinyMTR')
				->addMailHeader('Reply-To', 'noreply@picotory.com', 'TinyMTR')
				->addGenericHeader('X-Mailer', 'PHP/' . phpversion())
				->addGenericHeader('Content-Type', 'text/html; charset="utf-8"')
				->setMessage('<p>Status Alert!<br /><br />ID(' . $id . ') ' . $serAddr . ' seems to be offline!</p>')
				->setWrap(78)
				->send();
		}
	
		return true;
	}

	# Get server details
	function getServerInfo($domain, $exfile) {
		# Create the URL to the external file
		$output = HTTP_R::M0('http://' . $domain . '/' . $exfile);
		
		# Get just the part we need
		$expData = explode('::', $output);
		$expData1 = json_decode($expData[0], true);
		$expData2 = json_decode($expData[1], true);
		$expData3 = json_decode($expData[2], true);
		$expData4 = json_decode($expData[3], true);

		# Merge the arrays
		$expData = array_merge($expData1, $expData2, $expData3, $expData4);
		return $expData;
	}
?>