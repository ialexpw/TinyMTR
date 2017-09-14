<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		iAlex (http://codecanyon.net/iAlex)
		statistics.php
	*/
	
	include ("config.php");
	
	if(!isset($_SESSION['Logged_In']) || !isset($_SESSION['User'])) {
		header("Location: " . $siteLoc . "login" . $x);
	}

	# Updating alerts
	if(isset($_GET['update-alerts']) && !empty($_POST) && !empty($_GET['id'])) {
		
		# Server ID
		$srvID = $_GET['id'];
		
		# Both alerts on
		if($_POST['sms-option'] == 'on' && $_POST['email-option'] == 'on') {
			$altValue = '1:1';
		}
		
		# SMS only
		if($_POST['sms-option'] == 'on' && $_POST['email-option'] == 'off') {
			$altValue = '1:0';
		}
		
		# Email only
		if($_POST['sms-option'] == 'off' && $_POST['email-option'] == 'on') {
			$altValue = '0:1';
		}
		
		# Both off
		if($_POST['sms-option'] == 'off' && $_POST['email-option'] == 'off') {
			$altValue = '0:0';
		}
		
		# Update the database
		$stmt = $dbh->prepare("UPDATE servers SET alerts = :alerts WHERE id = :id");
		$stmt->bindParam(':alerts', $altValue);
		$stmt->bindParam(':id', $srvID);
		$stmt->execute();
		
		# Redirect back
		header("Location: " . $siteLoc . "statistics" . $x . "?id=" . $srvID . "&5min");
	}

	/* Looking at an ID? */
	if(!empty($_GET['id'])) {
		$resString = '';
		$timeString = '';
		
		/* Select everything to do with the server */
		$stmt = $dbh->prepare("SELECT * FROM servers WHERE id = :id AND userid = :userid LIMIT 1");
		$stmt->bindParam(':id', $_GET['id']);
		$stmt->bindParam(':userid', $_SESSION['UserID']);
		$stmt->execute();
		$srvDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$sCount = count($srvDetails);
		
		/* Cannot find the server with that ID that belongs to you? */
		if($sCount == 0) {
			header("Location: " . $siteLoc . "monitor" . $x);
		}
		
		/* Lets check the alerts, to see if they are set */
		$wAlerts = explode(':', $srvDetails[0]['alerts']);

		/*
			$alrText
			
			0 - Both alerts disabled
			1 - SMS enabled
			2 - Email enabled
			3 - Both alerts enabled
		*/
		
		// Both enabled
		if($wAlerts[0] && $wAlerts[1]) {
			$alrText = '3';
		}

		// Both disabled
		if(!$wAlerts[0] && !$wAlerts[1]) {
			$alrText = '0';
		}

		// SMS enabled
		if($wAlerts[0] && !$wAlerts[1]) {
			$alrText = '1';
		}

		// Email enabled
		if(!$wAlerts[0] && $wAlerts[1]) {
			$alrText = '2';
		}
		
		/* What time period are we looking at? */
		if(isset($_GET['5min'])) {
			/* Select the records for the server with the ID (5min) */
			$stmt = $dbh->prepare("SELECT * FROM records WHERE serid = :serid AND userid = :userid ORDER BY atimestamp DESC LIMIT 24");
			$stmt->bindParam(':serid', $_GET['id']);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
		}
		elseif(isset($_GET['15min'])) {
			/* Select the records for the server with the ID (15min) */
			$stmt = $dbh->prepare("SELECT * FROM (SELECT @row := @row +1 AS rownum, id, readtime, status, atimestamp FROM (SELECT @row :=0) r, records WHERE serid = :serid AND userid = :userid ORDER BY atimestamp DESC) ranked WHERE rownum %3 =1 LIMIT 48");
			$stmt->bindParam(':serid', $_GET['id']);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
		}
		elseif(isset($_GET['30min'])) {
			/* Select the records for the server with the ID (30min) */
			$stmt = $dbh->prepare("SELECT * FROM (SELECT @row := @row +1 AS rownum, id, readtime, status, atimestamp FROM (SELECT @row :=0) r, records WHERE serid = :serid AND userid = :userid ORDER BY atimestamp DESC) ranked WHERE rownum %6 =1 LIMIT 48");
			$stmt->bindParam(':serid', $_GET['id']);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
		}elseif(isset($_GET['all'])) {
			/* Select ALL of the records */
			$stmt = $dbh->prepare("SELECT * FROM records WHERE serid = :serid AND userid = :userid ORDER BY atimestamp DESC");
			$stmt->bindParam(':serid', $_GET['id']);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
		}else{
			header("Location: " . $siteLoc . "statistics" . $x . "?id=" . $_GET['id'] . "&5min");
		}
		
		$recDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$i=0;
		$j=0;
		
		/* Hack the results into a string for the graph*/
		foreach($recDetails as $result) {			
			if($i==0) {
				$resString = $result['status'] . $resString;
				$i++;
			}else{
				$resString = $result['status'] . ',' . $resString;
			}

			if($j==0) {
				$timeString = '"' . $result['readtime'] . '"' . $timeString;
				$j++;
			}else{
				$timeString = '"' . $result['readtime'] . '",' . $timeString;
			}
		}
	/* No ID given, go back */
	}else{
		header("Location: " . $siteLoc . "monitor" . $x);
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>TinyMTR : Painless Server Monitoring</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="author" content="iAlex@CodeCanyon">
		<meta name="description" content="TinyMTR is a simple server monitoring tool">
		<link rel="shortcut icon" href="img/favicon.ico" />
	
		<link href='//brick.a.ssl.fastly.net/Open+Sans:300i,400i,600i,700i,400,300,600,700' rel='stylesheet' type='text/css'>
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
		<link href="<?php echo $siteLoc; ?>css/main.css" rel="stylesheet" media="screen">
		<link rel="stylesheet" href="<?php echo $siteLoc; ?>css/morris-0.4.3.min.css">

		<style type="text/css">
		  body {
			padding-top: 20px;
			padding-bottom: 40px;
		  }

		  /* Custom container */
		  .container-narrow {
			margin: 0 auto;
			max-width: 960px;
			overflow:hidden;
		  }
		  .container-narrow > hr {
			margin: 30px 0;
		  }

		  /* Supporting marketing content */
		  .marketing {
			margin: 60px 0;
		  }
		  .marketing p + h4 {
			margin-top: 28px;
		  }

		  .modal {
			overflow-y:hidden;
		}

		.modal-open {
			overflow:hidden;
		}
		</style>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
		<script src="<?php echo $siteLoc; ?>js/morris-0.4.3.min.js"></script>
	</head>
	
	<body>
		<div class="container-narrow">
			<div class="masthead">
				<img style="margin:0 auto; display:block;" src="<?php echo $siteLoc; ?>img/logo.png" /><br />
				<!--<h2 align="center" class="muted sideSpace"><?php //echo $siteName; ?></h2><br />-->

				<ul class="nav nav-pills nav-justified">
					<li class="sideSpace"><a href="<?php echo $siteLoc; ?>overview<?php echo $x; ?>"><?php echo $l['Overview']; ?></a></li>
					<li class="active sideSpace"><a href="<?php echo $siteLoc; ?>monitor<?php echo $x; ?>"><?php echo $l['Monitors']; ?></a></li>
					<?php
						if($MULTISERV) {
							echo '<li class="sideSpace"><a href="' . $siteLoc . 'remote' . $x . '">' . $l['Remote'] . '</a></li>';
						}

						echo '<li class="sideSpace"><a href="' . $siteLoc . 'settings' . $x . '">' . $l['Settings'] . '</a></li>';
					?>
					<li class="sideSpace"><a href="<?php echo $siteLoc; ?>logout<?php echo $x; ?>"><?php echo $l['Logout']; ?></a></li>
				</ul>
			</div>

			<hr>
			<h4 align="center"><?php echo $l['StatsFor']; ?> <?php echo $srvDetails[0]['address']; ?> (<?php echo $srvDetails[0]['ipaddress']; ?>)</h4>
			<br />

			<p align="center">
				<a href="<?php echo $siteLoc; ?>statistics<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;5min"><?php echo $l['Every5']; ?></a> | 
				<a href="<?php echo $siteLoc; ?>statistics<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;15min"><?php echo $l['Every15']; ?></a> | 
				<a href="<?php echo $siteLoc; ?>statistics<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;30min"><?php echo $l['Every30']; ?></a>
			</p>

			<br />
			<a style="margin-left:3px;" href="<?php echo $siteLoc; ?>graphing<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;5min" class="btn btn-default btn-xs pull-right">View All Graphs</a>
			<button class="btn btn-default btn-xs modalButtons pull-right" data-toggle="modal" data-target="#myModalSub">Edit Alerts</button>
			<?php
				/* What text to use, depending on the monitoring time */
				if(isset($_GET['5min'])) {
					echo '<p class="sideSpace">' . $l['Int5Min'] . ' (2 hours)</p>';
				}
				elseif(isset($_GET['15min'])) {
					echo '<p>' . $l['Int15Min'] . ' (&#189; day)</p>';
				}
				elseif(isset($_GET['30min'])) {
					echo '<p>' . $l['Int30Min'] . ' (1 day)</p>';
				}
			?>
			
			<br />
			<div id="pingMTR" style="height: 250px;"></div>

			<script>
				new Morris.Line({
					// ID of the element in which to draw the chart.
					element: 'pingMTR',
					hideHover: true,
					lineWidth: 1,
					pointSize: 1,
					// Chart data records -- each entry in this array corresponds to a point on
					// the chart.
					data: [
						<?php
							foreach($recDetails as $dS) {
								$tStamp = date('Y-m-d', $dS['atimestamp']);
								echo "{ time: '$tStamp " . $dS['readtime'] . "', value: " . $dS['status'] . " },";
							}
						?>
					],
					// The name of the data record attribute that contains x-values.
					xkey: 'time',
					// A list of names of data record attributes that contain y-values.
					ykeys: ['value'],
					// Labels for the ykeys -- will be displayed when you hover over the
					// chart.
					labels: ['Ping']
				});
			</script>
			<br />
			<hr>
			<?php
				/* We must ping the server before, to avoid timeouts and hanging php */
				$pingDom = pingDomain($srvDetails[0]['address'], $srvDetails[0]['secure']);

				/* Check external available? For mem and load usage */
				if($pingDom != -1 && checkMTR($srvDetails[0]['address'], $externalFile, $srvDetails[0]['secure'])) {
					$getInfo = getServerInfo($srvDetails[0]['address'], $externalFile);
					$getMemPercent = round(($getInfo['memoryused'] / $getInfo['memorytotal']) * 100);
					$memLeft = $getInfo['memorytotal'] - $getInfo['memoryused'];

					
					/* Style the progress bar according to usage */
					if($getMemPercent < 75) {
						$memClass = 'progress-bar-success';
					}else if($getMemPercent < 90 && $getMemPercent >= 75){
						$memClass = 'progress-bar-warning';
					}else if($getMemPercent >= 90){
						$memClass = 'progress-bar-danger';
					}

					/* Queries for downtime/uptime */
					$stmt = $dbh->prepare("SELECT COUNT(*) FROM records WHERE serid = :id AND userid = :userid AND status = -1");
					$stmt->bindParam(':id', $_GET['id']);
					$stmt->bindParam(':userid', $_SESSION['UserID']);
					$stmt->execute();
					$srvDowntime = $stmt->fetchAll(PDO::FETCH_ASSOC);

					$stmt = $dbh->prepare("SELECT COUNT(*) FROM records WHERE serid = :id AND userid = :userid AND status > -1");
					$stmt->bindParam(':id', $_GET['id']);
					$stmt->bindParam(':userid', $_SESSION['UserID']);
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

					/* Show the stats */
					echo '<h4 align="center">' . $l['SerStats'] . '</h4>';
					echo '<div class="row">';

  					echo '<div class="col-md-4" align="center">';
  					echo '<h5>' . $l['CurrLoad'] . '</h5> <br />';
  					echo '<span class="label label-success">' . $getInfo['load1'] . '</span> - ';
  					echo '<span class="label label-info">' . $getInfo['load5'] . '</span> - ';
  					echo '<span class="label label-warning">' . $getInfo['load15'] . '</span>';
  					echo '</div>';

					echo '<div class="col-md-4" align="center">';
					echo '<h5>' . $l['CurrUptime'] . ' (last ' . $keepLast / (1440/$cInterval) . ' days)</h5> <br />';
					echo $getUptimePerc . '%';
					echo '</div>';
					
  					echo '<div class="col-md-4" align="center">';
  					echo '<h5>' . $l['CurrMemUse'] . '</h5> <br />';
					
					echo '<div class="progress" style="width:80%; margin: 0 auto;" align="center">
						<span style="font-size:10px;">' . $memLeft . 'MB</span>
						<div class="progress-bar ' . $memClass . '" role="progressbar" aria-valuenow="' . $getMemPercent . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $getMemPercent . '%">
							<span style="font-size:10px;">' . $getInfo['memoryused'] . 'MB</span><span class="sr-only">' . $getMemPercent . '% Complete</span>
						</div>
					</div>';
  					echo '</div>';
					echo '</div>';
				}else{
					echo '<h4 align="center">' . $l['SerStats'] . '</h4>';
					echo '<div class="row">';
					echo '<div class="col-md-3"></div>';
					echo '<div class="col-md-6">';
					if($pingDom >= 0) {
						echo '<p>If you would like to view memory and load usage, please upload <a href="dl.php">this file</a> to the root of ' . $srvDetails[0]['address'] . '</p>';
					}else{
						echo '<p>It seems that the server is offline so I cannot retrieve the servers stats until it is back up.</p>';
					}
					echo '</div>';
					echo '<div class="col-md-3"></div>';
					echo '</div>';
				}
			?>
			<br />
			<hr>
			<br />
			<table class="table table-striped table-bordered">
				<thead>
					<tr>
						<th><?php echo $l['Address']; ?></th>
						<th><?php echo $l['Time']; ?></th>
						<th><?php echo $l['PingRes']; ?></th>
						<th><?php echo $l['Status']; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						$timeString = str_replace('"', '', $timeString);
						$resArray = explode(",", $resString);
						$timeArray = explode(",", $timeString);

						if(empty($timeArray[0]) && empty($resArray[0])) {
							echo '<p align="center">' . $l['NoData'] . '</p>';
						}else{
							$resArray = array_reverse($resArray);
							$timeArray = array_reverse($timeArray);
							
							$m = new MultipleIterator();
							$m->attachIterator(new ArrayIterator($resArray), 'resArr');
							$m->attachIterator(new ArrayIterator($timeArray), 'timeArr');
							
							foreach ($m as $unit) {
								if(($unit[0] != -1 && !empty($unit[0])) || $unit[0] == 0) { // Horrible bug, returns 0ms if in the same datacenter
									$state = '<img class="icons" style="padding-bottom:5px;" src="' . $siteLoc . 'img/tick.png" alt="Tick" /> ' . $l['Online'];
								}else{
									$state = '<img class="icons" style="padding-bottom:5px;" src="' . $siteLoc . 'img/cross.png" alt="Tick" /> ' . $l['Offline'];
								}
								
								echo '<tr>';
								echo '<td>' . $srvDetails[0]['address'] . '</td>';
								echo '<td>' . $unit[1] . '</td>';
								echo '<td>' . $unit[0] . ' ms</td>';
								echo '<td>' . $state . '</td>';
								echo '</tr>';
							}
						}
					?>
				</tbody>
			</table>
			
			<hr>

			<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
			</div>
		</div>
		
		<?php
			/*
				$alrText

				0 - Both alerts disabled
				1 - SMS enabled
				2 - Email enabled
				3 - Both alerts enabled
			*/
		?>

		<!-- Modal box for alerts -->
		<div class="modal fade" id="myModalSub" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="myModalLabel">Settings</h4>
					</div>
					<div class="modal-body">
						<form class="form-horizontal" action='?id=<?php echo $_GET['id']; ?>&5min&update-alerts' method="POST">
							<div class="row">
								<p style="margin-left:20px;"><b>Alerts</b></p>
								<div class="col-md-2"></div>
								<div class="col-md-4" align="center">
									<p>
										SMS Alerts for this monitor
										<div class="btn-group" data-toggle="buttons">
											<?php
												if($useNexmo) {
													// SMS enabled
													if($alrText == '1' || $alrText == '3') {
														echo '<label class="btn btn-success btn-xs active">';
														echo '<input type="radio" name="sms-option" id="sms-1" value="on" checked=""> On';
													}else{
														echo '<label class="btn btn-success btn-xs">';
														echo '<input type="radio" name="sms-option" id="sms-1" value="on"> On';
													}
													echo '</label>';

													// SMS disabled
													if($alrText == '0' || $alrText == '2') {
														echo '<label class="btn btn-danger btn-xs active">';
														echo '<input type="radio" name="sms-option" id="sms-2" value="off" checked=""> Off';
													}else{
														echo '<label class="btn btn-danger btn-xs">';
														echo '<input type="radio" name="sms-option" id="sms-2" value="off"> Off';
													}
													echo '</label>';
												}else{
													echo '<p>Disabled</p>';
												}
											?>
										</div>
									</p>
								</div>
								<div class="col-md-4" align="center">
									<p>
										Email Alerts for this monitor
										<div class="btn-group" data-toggle="buttons">
											<?php
												if($useEmail) {
													// Email enabled
													if($alrText == '2' || $alrText == '3') {
														echo '<label class="btn btn-success btn-xs active">';
														echo '<input type="radio" name="email-option" id="email-1" value="on" checked=""> On';
													}else{
														echo '<label class="btn btn-success btn-xs">';
														echo '<input type="radio" name="email-option" id="email-1" value="on"> On';
													}
													echo '</label>';

													// Email disabled
													if($alrText == '0' || $alrText == '1') {
														echo '<label class="btn btn-danger btn-xs active">';
														echo '<input type="radio" name="email-option" id="email-2" value="off" checked=""> Off';
													}else{
														echo '<label class="btn btn-danger btn-xs">';
														echo '<input type="radio" name="email-option" id="email-2" value="off"> Off';
													}
													echo '</label>';
												}else{
													echo '<p>Disabled</p>';
												}
											?>
										</div>
									</p>
								</div>
								<div class="col-md-2"></div>
							</div>

							<br />

							<div class="row">
								<div class="col-md-3"></div>

								<div class="col-md-6" align="center">
									<button type="submit" class="btn btn-default btn-sm">Update Alerts</button>
								</div>

								<div class="col-md-3"></div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>