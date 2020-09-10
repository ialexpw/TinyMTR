<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		cron.php
	*/

	include 'config.php';
	include 'lib/class.simple_mail.php';

	# Cron Debugging
	if($cDebug) {
		$tBefore = time();
	}
	
	$readTime = date("H:i", time());

	# Select all the servers that are inactive
	$stmt = $dbh->prepare("SELECT * FROM servers WHERE active = 0");
	$stmt->execute();
	$inactDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

	# Check for inactives and activate if the file is found
	if(!empty($inactDetails)) {
		foreach ($inactDetails as $inactiveServer) {
			if(checkMTR($inactiveServer['address'], $externalFile, $inactiveServer['secure'])) {
				$stmt = $dbh->prepare("UPDATE servers SET active = 1 WHERE id = :id");
				$stmt->bindParam(':id', $inactiveServer['id']);
				$stmt->execute();
			}
		}
	}
	
	# Select all the servers that are active
	$stmt = $dbh->prepare("SELECT * FROM servers WHERE active = 1");
	$stmt->execute();
	$serDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

	# Only run with existing values
	if(!empty($serDetails)) {
		# Run through each server and ping it
		foreach ($serDetails as $serverRow) {
			# Multi server pinging
			if($MULTISERV) {
				# Server must be more than 0 for external
				if($serverRow['location'] > 0){
					$exServer = $aServers[$serverRow['location']]['Address'];

					# Check if the TinyMTR file is in sight, or revert to local
					if(checkMTR($exServer, $externalFile, 0)) {
						$localPing = false;
						$pingDom = pingFrom($exServer, $serverRow['ipaddress'], $serverRow['secure']);
					}else{
						# Cannot find the file, ping locally
						$localPing = true;
						$pingDom = pingDomain($serverRow['ipaddress'], $serverRow['secure']);
					}
				}else{
					# Ping locally
					$localPing = true;
					$pingDom = pingDomain($serverRow['ipaddress'], $serverRow['secure']);
				}
			}else{
				# Not multi-server
				$localPing = true;
				$pingDom = pingDomain($serverRow['ipaddress'], $serverRow['secure']);
			}

			# If it seems down or empty, do a retry just to make sure (external servers retry already)
			if(($pingDom == -1 && $localPing) || empty($pingDom)) {
				$pingDom = pingDomain($serverRow['ipaddress'], $serverRow['secure']);
			}
			
			# CRON DEBUGGING
			if($cDebug) {
				echo 'Address: ' . $serverRow['address'] . '<br />Ping: ' . $pingDom . '<br />';
			}

			# Was the server down 5 minutes ago?
			$stmt = $dbh->prepare("SELECT * FROM records WHERE serid = :serid ORDER BY id DESC LIMIT 1");
			$stmt->bindParam(':serid', $serverRow['id']);
			$stmt->execute();
			$getDet = $stmt->fetchAll(PDO::FETCH_ASSOC);

			# No data yet
			if(empty($getDet)) {
				$lastStatus = 0;
			}else{
				$lastStatus = $getDet[0]['status'];
			}
			
			# CRON DEBUGGING
			if($cDebug) {
				echo 'Last Status: ' . $lastStatus . '<br />';
			}
			
			# Check which alerts are enabled
			$wAlerts = explode(':', $serverRow['alerts']);
			
			# CRON DEBUGGING
			if($cDebug) {
				echo 'Email Alerts: ' . $wAlerts[1] . ' SMS Alerts: ' . $wAlerts[0] . '<br />';
			}
			
			# Server is down, do not try and get more data
			if($pingDom == -1) {
				$data = array( 'userid' => $serverRow['userid'], 'serid' => $serverRow['id'], 'status' => $pingDom, 'atimestamp' => time(), 'readtime' => $readTime );			
				$stmt = $dbh->prepare("INSERT INTO records (userid, serid, status, atimestamp, readtime) VALUES (:userid, :serid, :status, :atimestamp, :readtime)");
				$stmt->execute($data);
				
				# Using Email and the server was not down last time
				if($useEmail && $lastStatus != -1) {
					# CRON DEBUGGING
					if($cDebug) {
						echo 'Using Email and server was NOT down 5 minutes ago<br />';
					}
					
					$stmt = $dbh->prepare("SELECT * FROM users WHERE id = :userid");
					$stmt->bindParam(':userid', $serverRow['userid']);
					$stmt->execute();
					$userDet = $stmt->fetchAll(PDO::FETCH_ASSOC);
					
					# Send the email! - alerts on?
					if($wAlerts[1]) {
						sendEmail($userDet[0]['email'], $serverRow['address'], $serverRow['id']);
					}
				}
			}else{				
				# Using Email and the server was down 5 minutes ago
				if($useEmail && $lastStatus == -1) {
					# CRON DEBUGGING
					if($cDebug) {
						echo 'Using Email and server WAS down 5 minutes ago<br />';
					}
					
					$stmt = $dbh->prepare("SELECT * FROM users WHERE id = :userid");
					$stmt->bindParam(':userid', $serverRow['userid']);
					$stmt->execute();
					$userDet = $stmt->fetchAll(PDO::FETCH_ASSOC);

					# Send the email! - email alerts?
					if($wAlerts[1]) {
						sendEmail($userDet[0]['email'], $serverRow['address'], $serverRow['id'], 1);
					}
				}

				# Server is up, we can try and get more data from the server
				$getData = getServerInfo($serverRow['address'], $externalFile);

				# Insert new record
				$data = array(
					'userid' => $serverRow['userid'],
					'serid' => $serverRow['id'],
					'status' => $pingDom,
					'atimestamp' => time(),
					'readtime' => $readTime,
					'load_1' => round($getData['load1'], 2),
					'load_5' => round($getData['load5'], 2),
					'load_15' => round($getData['load15'], 2),
					'memory' => $getData['memoryused'],
					'disk' => $getData['diskpercent'],
				);

				$stmt = $dbh->prepare("INSERT INTO records (userid, serid, status, atimestamp, readtime, load_1, load_5, load_15, memory, disk) VALUES (:userid, :serid, :status, :atimestamp, :readtime, :load_1, :load_5, :load_15, :memory, :disk)");
				$stmt->execute($data);
			}
			
			# Delete old records ~ keep down records
			if($keepLast > 0 && $readTime == '01:00') {
				$stmt = $dbh->prepare("SELECT * FROM records WHERE serid = :serid AND userid = :userid ORDER BY id DESC LIMIT 1 OFFSET $keepLast");
				$stmt->bindParam(':serid', $serverRow['id']);
				$stmt->bindParam(':userid', $serverRow['userid']);
				$stmt->execute();
				$getID = $stmt->fetchAll(PDO::FETCH_ASSOC);

				# See if we have a record
				$countRec = count($getID);

				# We do! Delete the excess
				if($countRec > 0) {
					$pruneSum = $getID[0]['id'];

					# Delete records matching and below ID
					$stmt = $dbh->prepare("DELETE FROM records WHERE serid = :serid AND userid = :userid AND id <= $pruneSum");
					$stmt->bindParam(':serid', $serverRow['id']);
					$stmt->bindParam(':userid', $serverRow['userid']);
					$stmt->execute();
				}
			}
			# CRON DEBUGGING
			if($cDebug) {
				echo '<br />';
			}
		}
	}
	
	# Manual Cron
	if(isset($_GET['manual']) && !$MULTIUSER) {
		header("Location: " . $siteLoc . "settings" . $x . "?cron-success");
	}
	
	# Cron Debugging
	if($cDebug) {
		$tAfter = time();
		
		$cTime = $tAfter - $tBefore;
		
		$data = array( 'readtime' => $readTime, 'atimestamp' => time(), 'timetook' => $cTime );			
		$stmt = $dbh->prepare("INSERT INTO cron (readtime, atimestamp, timetook) VALUES (:readtime, :atimestamp, :timetook)");
		$stmt->execute($data);
	}

	# Send the webhooks
	//include 'sendhooks.php';
?>