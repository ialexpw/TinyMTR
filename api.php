<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		api.php
	*/

	include ("config.php");

	# Check if we have values set
	if(isset($_GET['id']) && isset($_GET['hash'])) {

		# Try and validate the ID/Hash to the correct character format
		if(validateInput($_GET['id'],'id') && validateInput($_GET['hash'],'user')) {

			# Search for our server ID
			$stmt = $dbh->prepare("SELECT * FROM servers WHERE id = :id");
			$stmt->bindParam(':id', $_GET['id']);
			$stmt->execute();
			$serCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$countServ = count($serCount);

			# See if we have found it and can validate it
			if($countServ && validateHash($_GET['id']) == $_GET['hash']) {

				# Showing the public page
				if(isset($_GET['public'])) {

					# Get uptime, and other useful information
					$sUptime = getUptime($_GET['id']);
					$sResponse = getResponse($_GET['id']);

					# Select last ping time
					$stmt = $dbh->prepare("SELECT readtime FROM records WHERE serid = :serid ORDER BY id DESC LIMIT 1");
					$stmt->bindParam(':serid', $_GET['id']);
					$stmt->execute();
					$serCountRec = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$countServRec = count($serCountRec);

					# Select last 5 downtimes
					$stmt = $dbh->prepare("SELECT * FROM records WHERE serid = :serid AND status = -1 ORDER BY id DESC LIMIT 5");
					$stmt->bindParam(':serid', $_GET['id']);
					$stmt->execute();
					$serCountDown = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$countServDown = count($serCountDown);

					# Include the template header for the API
					include ("lib/api/header.php");

					# Check if there has been any records yet
					if($countServRec) {
						echo '<div class="col-md-3" style="border-right:#ccc 1px solid;">Last Monitored<br />' . $serCountRec[0]['readtime'] . '</div>';
					}else{
						echo '<div class="col-md-3" style="border-right:#ccc 1px solid;">Last Monitored<br />Never</div>';
					}
					
					echo '<div class="col-md-3" style="border-right:#ccc 1px solid;">Uptime<br />' . $sUptime . '%</div>';
					echo '<div class="col-md-3" style="border-right:#ccc 1px solid;">Average Response<br />' . $sResponse . 'ms</div>';
					echo '<div class="col-md-3">Check Type: HTTP<br />Ping Time: 5 Minutes</div>';
					echo '</div><br />';

					echo '<p style="font-size:16px;">Downtimes across the last 31 days</p>';

					if($countServDown) {
						foreach($serCountDown as $downTime) {
							echo '<div class="row" style="margin-left:0px; margin-right:0px; padding:6px; border:#ccc 1px solid; border-radius: 4px;">';
							echo '<div class="col-md-4" style="border-right:#ccc 1px solid;">Time & Date<br />' . $downTime['readtime'] . ' - ' . date('jS F Y', $downTime['atimestamp']) . '</div>';
							echo '<div class="col-md-4" style="border-right:#ccc 1px solid;">Ping Result<br />' . $downTime['status'] . '</div>';
							echo '<div class="col-md-4">Status<br />Offline</div>';
							echo '</div>';

							echo '<br />';
						}
					}else{
						echo '<p>No downtime has been recorded on this monitor.</p>';
					}

					echo '<div>';

					# Include the template footer for the API
					include ("lib/api/footer.php");

				}else{
					if(isset($_GET['uptime'])) {

						# Uptime function
						$sUptime = getUptime($_GET['id']);

						$jsonEn = array('uptime' => "$sUptime");
						$jsonEn = json_encode($jsonEn);

						echo $jsonEn;
					}elseif(isset($_GET['response'])) {

						# Response function
						$sResponse = getResponse($_GET['id']);

						$jsonEn = array('response' => "$sResponse");
						$jsonEn = json_encode($jsonEn);

						echo $jsonEn;
					}elseif(isset($_GET['records'])) {
						$gRec = getRecords($_GET['id'], 10);
					}
				}
			}else{
				exit('Invalid ID or Hash format.');
			}
		}else{
			exit('Invalid ID or Hash format.');
		}
		
	}else{
		exit('Invalid ID or Hash format.');
	}
	
	# Get records
	function getRecords($id, $amount) {
		global $dbh;
		
		# Query records
		$stmt = $dbh->prepare("SELECT * FROM records WHERE serid = :id LIMIT :limit");
		$stmt->bindParam('id', $id);
		$stmt->bindParam('limit', $amount);
		$stmt->execute();
		
		# Select records
		$srvRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		# Loop through
		foreach($srvRecords as $record) {
			$srArray = array(
				'id' => $record['id'],
				'server_address' => $record['address'],
				'server_ip' => $record['ipaddress'],
				'response' => $record['response'],
				'memory' => $record['memory'],
				'disk' => $record['disk'],
				'load_1' => '',
				'load_5' => '',
				'load_15' => ''
			);
			
			$jEnc = json_encode($srArray);
			
			echo $jEnc;
		}
	}

	function getUptime($id) {
		global $dbh;

		/* Queries for downtime */
		$stmt = $dbh->prepare("SELECT COUNT(*) FROM records WHERE serid = :id AND status = -1");
		$stmt->bindParam(':id', $_GET['id']);
		$stmt->execute();
		$srvDowntime = $stmt->fetchAll(PDO::FETCH_ASSOC);

		/* Queries for uptime */
		$stmt = $dbh->prepare("SELECT COUNT(*) FROM records WHERE serid = :id AND status > -1");
		$stmt->bindParam(':id', $_GET['id']);
		$stmt->execute();
		$srvUptime = $stmt->fetchAll(PDO::FETCH_ASSOC);

		/* Record uptime and downtimes */
		$recUptime = $srvUptime[0]['COUNT(*)'];
		$recDowntime = $srvDowntime[0]['COUNT(*)'];

		$totalRecords = $recUptime+$recDowntime;

		/* Get the percentage */
		if($recUptime != 0) {
			$getRecPerc = round(($recDowntime / $totalRecords) * 100, 2);
			$getUptimePerc = 100 - $getRecPerc;
		}else{
			$getUptimePerc = '0';
		}

		return $getUptimePerc;
	}

	function getResponse($id) {
		global $dbh;

		/* Queries for uptime */
		$stmt = $dbh->prepare("SELECT status FROM records WHERE serid = :id");
		$stmt->bindParam(':id', $_GET['id']);
		$stmt->execute();
		$srvRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$countRec = count($srvRecords);

		$addStatus = 0;

		foreach($srvRecords as $pStatus) {
			$addStatus = $pStatus['status'] + $addStatus;
		}

		if($countRec != 0) {
			$avgResponse = round($addStatus / $countRec, 0);
		}else{
			$avgResponse = '0';
		}

		return $avgResponse;
	}