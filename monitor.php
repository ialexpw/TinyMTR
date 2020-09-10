<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		monitor.php
	*/
	
	include ("config.php");
	
	# Set out our variables
	$page = 1;
	$Other = 0;
	$serPerPage = 6;
	$pagination = 1;
	$doesExist = 0;
	$pageAmount = 0;
	$toMany = 0;

	# Not logged in
	if(!isset($_SESSION['Logged_In']) || !isset($_SESSION['User'])) {
		header("Location: " . $siteLoc . "login" . $x);
	}
	
	# No page number - load page 1
	if(empty($_GET['page'])) {
		header("Location: " . $siteLoc . "monitor" . $x . "?page=1");
	}
	
	# Get the user details
	$stmt = $dbh->prepare("SELECT * FROM users WHERE id = :id");
	$stmt->bindParam(':id', $_SESSION['UserID']);
	$stmt->execute();
	$useDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	# Credits, maybe used for a future plugin..
	$myCredits = $useDetails[0]['credits'];

	# Trying to add another server
	if(isset($_GET['addserver']) && !empty($_POST['serveradd'])) {
		$serAdd = $_POST['serveradd'];
		
		# If Multi-server enabled ~ addon plugin tba
		if(!empty($_POST['servers']) && $MULTISERV) {
			$useServer = $_POST['servers'];
		}else{
			$useServer = 0;
		}
		
		# If it contains https, set to secure
		if(strpos($serAdd, 'https://') !== false) {
			$useSecure = 1;
		}else{
			$useSecure = 0;
		}

		# Remove bits we do not need
		$serAdd = removePrefix($serAdd);

		# Get the IP from domain
		$serIP = gethostbyname($serAdd);

		# Limit the user to a certain amount if multi-user
		if($MULTIUSER) {
			$stmt = $dbh->prepare("SELECT * FROM servers WHERE userid = :userid");
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
			$serCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$countServ = count($serCount);

			if($countServ > $serverLimit) {
				$toMany = 1;
			}
		}

		# See if we can find an existing server with the same name
		$stmt = $dbh->prepare("SELECT * FROM servers WHERE userid = :userid AND address = :address AND ipaddress = :ipaddress AND location = :location");
		$stmt->bindParam(':userid', $_SESSION['UserID']);
		$stmt->bindParam(':address', $serAdd);
		$stmt->bindParam(':ipaddress', $serIP);
		$stmt->bindParam(':location', $useServer);
		$stmt->execute();
		$serDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$countSer = count($serDetails);

		# Server exists already in the list
		if($countSer > 0) {
			$doesExist = 1;
		}

		# Domain is valid, and does not exist, adding a new one!
		if(validDomain($serAdd) && !$doesExist && !$toMany) {
			/* Do we require the TinyMTR.php file? */
			if($reqMtr) {
				$data = array( 'userid' => $_SESSION['UserID'], 'ipaddress' => $serIP, 'address' => $serAdd, 'location' => $useServer, 'secure' => $useSecure );			
				$stmt = $dbh->prepare("INSERT INTO servers (userid, ipaddress, address, location, secure) VALUES (:userid, :ipaddress, :address, :location, :secure)");
			}else{
				$data = array( 'userid' => $_SESSION['UserID'], 'ipaddress' => $serIP, 'address' => $serAdd, 'location' => $useServer, 'secure' => $useSecure, 'active' => '1' );			
				$stmt = $dbh->prepare("INSERT INTO servers (userid, ipaddress, address, location, secure, active) VALUES (:userid, :ipaddress, :address, :location, :secure, :active)");
			}
			$stmt->execute($data);

			header("Location: monitor" . $x . "?page=" . $_GET['page']);
		}else{
			if($doesExist) {
				header("Location: monitor" . $x . "?page=" . $_GET['page'] . "&exists");
			}else if($toMany){
				header("Location: monitor" . $x . "?page=" . $_GET['page'] . "&tomany");
			}else{
				header("Location: monitor" . $x . "?page=" . $_GET['page'] . "&invalid");
			}
		}
	}

	# Trying to remove a server
	if(!empty($_GET['remove'])) {
		$remID = $_GET['remove'];

		# Select the ID with the username
		$stmt = $dbh->prepare("SELECT * FROM servers WHERE id = :remid AND userid = :userid");
		$stmt->bindParam(':remid', $remID);
		$stmt->bindParam(':userid', $_SESSION['UserID']);
		$stmt->execute();
		$remDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		# See if there actually is a server
		$countRem = count($remDetails);
		
		# Cannot find a server with that ID by this user, redirect
		if($countRem == 0) {
			header("Location: monitor" . $x . "?page=" . $_GET['page'] . "&bad-id");
		}else{
			# Delete the server
			$stmt = $dbh->prepare("DELETE FROM servers WHERE id = :remid AND userid = :userid");
			$stmt->bindParam(':remid', $remID);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
			
			# Delete the records
			$stmt = $dbh->prepare("DELETE FROM records WHERE serid = :remid AND userid = :userid");
			$stmt->bindParam(':remid', $remID);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();

			header("Location: monitor" . $x . "?page=" . $_GET['page']);
		}
	}

	# Using Stripe module
	if(isset($_GET['charge']) && isset($_POST['stripeToken'])) {
		# Get payment token
		$token  = $_POST['stripeToken'];
		
		# Set success variable
		$chargeSuccess = 0;

		try {
			$charge = \Stripe\Charge::create(array(
				'amount'   => 150,
				'currency' => 'gbp',
				'card' => $token,
				'description' => '25 SMS Credits for £1.50'
			));

			$chargeSuccess = 1;
		} catch(Stripe_CardError $e) {
			$error = $e->getMessage();
		} catch(Stripe_InvalidRequestError $e) {
			/* Invalid parameters were supplied to Stripe's API */
			$error = $e->getMessage();
		} catch(Stripe_AuthenticationError $e) {
			/* Authentication with Stripe's API failed */
			$error = $e->getMessage();
		} catch(Stripe_ApiConnectionError $e) {
			/* Network communication with Stripe failed */
			$error = $e->getMessage();
		} catch(Stripe_Error $e) {
			/* Display a very generic error to the user */
			$error = $e->getMessage();
		} catch(Exception $e) {
			/* Something else happened, completely unrelated to Stripe */
			$error = $e->getMessage();
		}

		# Add credits here
		if($chargeSuccess) {
			$stmt = $dbh->prepare("UPDATE users SET credits = credits+25 WHERE id = :userid");
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();

			# Redirect to success
			header("Location: " . $siteLoc . "monitor" . $x . '?page=' . $_GET['page'] . '&success');
		}else{
			# Set the error message into a session
			$_SESSION['Stripe-Error'] = $error;

			# Redirect to error
			header("Location: " . $siteLoc . "monitor" . $x . '?page=' . $_GET['page'] . '&stripe-error');
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>TinyMTR : Painless Server Monitoring</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="author" content="iAlex">
		<meta name="description" content="TinyMTR is a simple server monitoring tool">
		<link rel="shortcut icon" href="img/favicon.ico" />

		<link href='//brick.a.ssl.fastly.net/Open+Sans:300i,400i,600i,700i,400,300,600,700' rel='stylesheet' type='text/css'>
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
		<link href="css/main.css" rel="stylesheet" media="screen">
		<style type="text/css">
		  body {
			padding-top: 20px;
			padding-bottom: 40px;
		  }

		  /* Custom container */
		  .container-narrow {
			margin: 0 auto;
			max-width: 960px;
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
		</style>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
	</head>
	
	<body>
		<div class="container-narrow">
			<div class="masthead">
				<img style="margin:0 auto; display:block;" src="img/logo.png" /><br />

				<ul class="nav nav-pills nav-justified">
					<li class="sideSpace"><a href="overview<?php echo $x; ?>"><?php echo $l['Overview']; ?></a></li>
					<li class="active sideSpace"><a href="monitor<?php echo $x; ?>"><?php echo $l['Monitors']; ?></a></li>
					<?php
						if($MULTISERV) {
							echo '<li class="sideSpace"><a href="remote' . $x . '">' . $l['Remote'] . '</a></li>';
						}

						echo '<li class="sideSpace"><a href="settings' . $x . '">' . $l['Settings'] . '</a></li>';
					?>
					<li class="sideSpace"><a href="logout<?php echo $x; ?>"><?php echo $l['Logout']; ?></a></li>
				</ul>
		  	</div>
			
			<hr>
		  
		  	<div class="row-fluid marketing">
				<h2>Monitors</h2>
				<br />
		  		<?php
					# Install file exists
					if(file_exists('install.php')) {
						echo '<div class="alert alert-danger">';
						echo '<strong>Error!</strong> Install file is still uploaded! You may want to delete this file for security purposes!';
						echo '</div>';
					}

					# Stripe payment error
					if(isset($_GET['stripe-error']) && isset($_SESSION['Stripe-Error'])) {
						echo '<div class="alert alert-danger">';
						echo '<strong>Error!</strong> ' . $_SESSION['Stripe-Error'];
						echo '</div>';
					}
					
					# Invalid Domain
		  			if(isset($_GET['invalid'])) {
		  				echo '<div class="alert alert-warning">';
		  				echo '<strong>Warning!</strong> That does not seem to be a valid domain. Please check it and try again!';
		  				echo '</div>';
		  			}

		  			# To many
		  			if(isset($_GET['tomany'])) {
		  				echo '<div class="alert alert-warning">';
		  				echo '<strong>Warning!</strong> I think you are monitoring enough servers!';
		  				echo '</div>';
		  			}
					
					# Domain Exists
		  			if(isset($_GET['exists'])) {
		  				echo '<div class="alert alert-warning">';
		  				echo '<strong>Warning!</strong> The domain that you have entered seems to exist already in your monitoring list!';
		  				echo '</div>';
		  			}
					
					# Bad ID
					if(isset($_GET['bad-id'])) {
						echo '<div class="alert alert-warning">';
						echo '<strong>Warning!</strong> You do not seem to have a monitor to remove with the ID supplied, please try again!';
						echo '</div>';
					}
		  			
					# Is the page valid? If not - page 1
					if(!empty($_GET['page'])) {
					
						/* Is page numeric? */
						if (is_numeric($_GET['page'])) {
							$page = $_GET['page'];
						}else{
							$page = 1;
						}
						
						# If the page is not 1, work out the servers needed
						if ($page != 1)
						{
							$Other = ($page * $serPerPage - $serPerPage);
						}
						
						# Select all the servers for the page
						$stmt = $dbh->prepare("SELECT * FROM servers WHERE userid = :userid ORDER BY id ASC LIMIT $Other, $serPerPage");
						$stmt->bindParam(':userid', $_SESSION['UserID']);
						$stmt->execute();
						$serDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

						# Find the amount of servers they have
						$stmt = $dbh->prepare("SELECT * FROM servers WHERE userid = :userid");
						$stmt->bindParam(':userid', $_SESSION['UserID']);
						$stmt->execute();
						$servDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
						
						# Count how many servers they have
						$countSer = count($servDetails);
						
						# If there is less than can fit on a page, no pagination
						if($countSer < $serPerPage){
							$pagination = 0;
						}
						
						# If more than one server, check per page
						if($countSer > 0) {
							/* Get amount of pages needed & round up. */
							$pageAmount = ceil($countSer/$serPerPage);
						}
						
						# If it's more, or if it's less than 0 it is wrong
						if ($page > $pageAmount || $page < 0) {
							$page = '1';
						}
						
						# If the page is not 1, work out the servers needed
						if ($page != 1)
						{
							$Other = ($page * $serPerPage - $serPerPage);
						}
					}

					# If adding new server
					if(isset($_GET['add'])) {
						echo '<form class="form-horizontal" action="?page=' . $_GET['page'] . '&addserver" method="POST">';
						
						echo '<div class="input-group">
								<input type="text" id="serveradd" name="serveradd" placeholder="' . $l['ServerAddress'] . '" class="form-control">
								<span class="input-group-btn">
									<button class="btn btn-success" type="submit">' . $l['AddServer'] . '</button>
								</span>
							</div><br />';
						
						# Multi server support
						if($MULTISERV) {
							# Count through locations
							$i=0;
							echo '<select class="form-control" name="servers">';
							foreach ($aServers as $arrServ) {
								echo '<option value="' . $i . '">' . $aServers[$i]['Country'] . '</option>';
								$i++;
							}
							echo '</select><br />';
						}
						echo '</form>';
					}else{
						echo '<p style="float:left;"><a href="' . $siteLoc . 'monitor' . $x . '?page=' . $_GET['page'] . '&add">' . $l['AddMonitor'] . '</a>';

						# Add credits if Stripe module & multi-user activated
						if($MULTIUSER && $STRIPEINT) {
							include("lib/StripeButton.html");
							echo '<a href="#" style="margin-top:1px; margin-right:3px; margin-left:3px; float:right;" class="btn btn-default disabled btn-xs" role="button">Current Credits: ' . $myCredits . '</a>';
						}
						echo '</p><br />';
					}
				?>
				
				<?php
					# Show as a table
					if(!empty($_GET['view']) && $_GET['view'] == 1) {
				?>
				<table class="table table-striped table-bordered">
					<thead>
						<tr>
							<th><?php echo $l['ID']; ?></th>
							<th class="hidden-xs"><?php echo $l['IPAddress']; ?></th>
							<th><?php echo $l['WebAddress']; ?></th>
							<th><?php echo $l['Status']; ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
							if(!empty($serDetails)) {
								foreach ($serDetails as $serverRow) {
									if($serverRow['active']) {
										if(pingDomain($serverRow['address'], $serverRow['secure']) != -1) {
											$state = '<img class="icons" style="padding-bottom:5px;" src="img/tick.png" alt="Tick" /> ' . $l['Online'];
										}else{
											$state = '<img class="icons" style="padding-bottom:5px;" src="img/cross.png" alt="Tick" /> ' . $l['Offline'];
										}
									}else{
										$state = '<img class="icons" style="padding-bottom:5px;" src="img/cross.png" alt="Tick" /> ' . $l['Inactive'] . '<button class="btn btn-default btn-xs modalButtons pull-right" data-toggle="modal" data-target="#myModalSub">?</button>';
									}
									
									echo '<tr>';
									echo '<td><a target="_blank" href="api.php?id=' . $serverRow['id'] . '&hash=' . validateHash($serverRow['id']) . '&public">' . $serverRow['id'] . '</a><a href="?page=' . $_GET['page'] . '&remove=' . $serverRow['id'] . '"><img class="icons" align="right" style="padding-bottom:5px;" src="' . $siteLoc . 'img/bin.png" /></a>';

									# Only if the monitor is active show the stats
									if($serverRow['active']) {
										echo '<a href="statistics' . $x . '?id=' . $serverRow['id'] . '&5min"><img class="icons" align="right" style="padding-bottom:5px;" src="' . $siteLoc . 'img/flux.png" /></a></td>';
									}

									echo '<td class="hidden-xs">' . $serverRow['ipaddress'] . '</td>';
									
									if($MULTISERV) {
										if($serverRow['secure']) {
											echo '<td><span class="label label-primary">HTTPS</span> ' . $serverRow['address'] . ($aServers[$serverRow['location']]['Shorter']=='LC' ? '' : '<img style="margin-top:4px;" class="pull-right" src="img/' . strtolower($aServers[$serverRow['location']]['Shorter']) . '.png" />') . '</td>';
										}else{
											echo '<td>' . $serverRow['address'] . ($aServers[$serverRow['location']]['Shorter']=='LC' ? '' : '<img style="margin-top:4px;" class="pull-right" src="img/' . strtolower($aServers[$serverRow['location']]['Shorter']) . '.png" />') . '</td>';
										}
									}else{
										if($serverRow['secure']) {
											echo '<td><span class="label label-primary">HTTPS</span> ' . $serverRow['address'] . ' </td>';
										}else{
											echo '<td>' . $serverRow['address'] . '</td>';
										}
									}
									echo '<td>' . $state . '</td>';
									echo '</tr>';								
								}
							}else{
								# Ugly way of doing it, but you should not be here
								if(!empty($_GET['page']) && $_GET['page'] > 1) {
									$pageMinus = $_GET['page'] - 1;

									header("Location: monitor" . $x . "?page=" . $pageMinus);
								}
							}
						?>
					</tbody>
				</table>
				
				<?php
					# Show as boxes
					}else if(empty($_GET['view'])){
						if(!empty($serDetails)) {
							echo '<br /><div class="row">';
							foreach ($serDetails as $serverRow) {
								if($serverRow['active']) {
									if(pingDomain($serverRow['address'], $serverRow['secure']) != -1) {
										$state = $l['Online'];
										$pState = 'success';
									}else{
										$state = $l['Offline'];
										$pState = 'danger';
									}
								}else{
									$state = $l['Inactive'] . '<span style="margin-right:5px;"></span> <button class="btn btn-default btn-xs modalButtons pull-right" data-toggle="modal" data-target="#myModalSub">?</button>';
									$pState = 'default';
								}
								
								# Show what port to ping
								if($serverRow['secure']) {
									$mPort = '443';
								}else{
									$mPort = '80';
								}
								
								echo '<div class="col-md-4"><div class="panel panel-' . $pState . '">';
								echo '<div class="panel-heading">' . $serverRow['address'] . '<span style="float:right;">' . $state . '</span></div>';
								echo '<div class="panel-body">';
								echo '<p>Public IP: ' . $serverRow['ipaddress'] . '<span style="float: right;">Port: ' . $mPort . '</span></p>';

								echo '<div align="center">';
								echo '<a target="_blank" href="api.php?id=' . $serverRow['id'] . '&hash=' . validateHash($serverRow['id']) . '&public" class="btn btn-default btn-xs" role="button">Public Status Page</a>';
								
								# If it is inactive, disable the button
								if($pState == 'default') {
									echo '<a href="statistics' . $x . '?id=' . $serverRow['id'] . '&5min" style="margin-left:5px;" class="btn btn-default disabled btn-xs" role="button">View Statistics</a>';
								}else{
									echo '<a href="statistics' . $x . '?id=' . $serverRow['id'] . '&5min" style="margin-left:5px;" class="btn btn-default btn-xs" role="button">View Statistics</a>';
								}
								
								echo '</div>';
								echo '<br />';
								
								# Show where we are pinging from - not local
								if($MULTISERV && $aServers[$serverRow['location']]['Shorter'] != 'LC') {
									echo '<span class="label label-primary">' . $aServers[$serverRow['location']]['Country'] . '</span>';
								}
								
								echo '<a href="?page=' . $_GET['page'] . '&remove=' . $serverRow['id'] . '" style="float: right;" class="btn btn-danger btn-xs" role="button">Remove Monitor</a>';
								echo '</div>';
								echo '</div>';
								echo '</div>';
								
							}
							echo '</div>';
						}else{
							# Ugly way of doing it, but you should not be here
							if(!empty($_GET['page']) && $_GET['page'] > 1) {
								$pageMinus = $_GET['page'] - 1;

								header("Location: monitor" . $x . "?page=" . $pageMinus);
							}
						}
					}
				?>
				
				<?php
					# If no servers...
					if(empty($serDetails)) {
						echo '<p align="center">Currently no servers have been added! Try <a href="monitor' . $x . '?page=' . $_GET['page'] . '&add">adding one</a> now..</p>';
					}
				
					/* Set out pagination numbers */
					$pagePlus = $_GET['page'] + 1;
					$pageMinus = $_GET['page'] - 1;
					
					/* Pagination enabled? */
					if($pagination) {
						/* First page, just need next button */
						if($page == 1 && $countSer > $serPerPage){
							echo '<span style="float:left;">' . $l['Previous'] . '</span> <span style="float:right;"><a href="monitor' . $x . '?page=' . $pagePlus . '">' . $l['Next'] . '</a></span>';
						}
						/* Last page, just show previous button */
						elseif($page == $pageAmount && $countSer > $serPerPage) {
							echo '<span style="float:left;"><a href="monitor' . $x . '?page=' . $pageMinus . '">' . $l['Previous'] . '</a></span> <span style="float:right;">' . $l['Next'] . '</span>';
						}
						/* Exact amount, no pagination */
						elseif($page == 1 && $countSer == $serPerPage) {
							echo '<span style="float:left;"></span> <span style="float:right;"></span>';
						}else{
							echo '<span style="float:left;"><a href="monitor' . $x . '?page=' . $pageMinus . '">' . $l['Previous'] . '</a></span> <span style="float:right;"><a href="' . $siteLoc . 'monitor' . $x . '?page=' . $pagePlus . '">' . $l['Next'] . '</a></span>';
						}
					}
				?>
			</div>

			<hr>

			<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
			</div>
		</div>

		<!-- Modal box for the '?' -->
		<div class="modal fade" id="myModalSub" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="myModalLabel">What does 'Inactive' mean?</h4>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12">
								<p>Inactive will appear when you have just added a new server. To monitor servers using TinyMTR, every single one will need
								a small PHP file uploaded to the web root of it.</p>

								<p>Every 5 minutes TinyMTR's cron will check for this file and will set the the server active
								if it finds it, otherwise it will stay inactive and data will not be collected.</p><br />

								<p align="center"><a href="dl<?php echo $x; ?>">Download the file</a></p>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>