<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		settings.php
	*/
	
	include ("config.php");
	
	# Not logged in
	if(!isset($_SESSION['Logged_In']) || !isset($_SESSION['User'])) {
		header("Location: " . $siteLoc . "login" . $x);
	}

	$userLevel = $_SESSION['UserLevel'];

	# Updating details
	if(isset($_GET['update']) && !empty($_POST)) {
		# Are we changing email?
		if(!empty($_POST['inputEmail'])) {
			# New email
			$nEmail = $_POST['inputEmail'];
			
			# Update the email
			$stmt = $dbh->prepare("UPDATE users SET email = :email WHERE id = :userid");
			$stmt->bindParam(':email', $nEmail);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
		}
		
		# Changing password?
		if(!empty($_POST['currentPassword']) && !empty($_POST['newPassword'])) {
			# Store them temporarily
			$oldPass = $_POST['currentPassword'];
			$updtPass = $_POST['newPassword'];
			
			# Get the users current details
			$stmt = $dbh->prepare("SELECT * FROM users WHERE id = :userid");
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();

			# Get the details so we can see the hashed password
			$userDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			# Hash it so we can compare
			$hashPass = passKey($_SESSION['User'], $oldPass, CYCLE_ONE, CYCLE_TWO);
			
			# See if we have a match
			if($userDetails[0]['password'] == $hashPass) {
				# The password matches - first hash it
				$hashPass = passKey($_SESSION['User'], $updtPass, CYCLE_ONE, CYCLE_TWO);
				
				# Then update it
				$stmt = $dbh->prepare("UPDATE users SET password = :password WHERE id = :userid");
				$stmt->bindParam(':password', $hashPass);
				$stmt->bindParam(':userid', $_SESSION['UserID']);
				$stmt->execute();
			}else{
				# Password does not match
			}
		}
		
		# Redirect back to avoid resubmission
		header("Location: settings" . $x);
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
					<li class="sideSpace"><a href="monitor<?php echo $x; ?>"><?php echo $l['Monitors']; ?></a></li>
					<?php
						if($MULTISERV) {
							echo '<li class="sideSpace"><a href="remote' . $x . '">' . $l['Remote'] . '</a></li>';
						}
					?>
					<li class="active sideSpace"><a href="settings<?php echo $x; ?>"><?php echo $l['Settings']; ?></a></li>
					<li class="sideSpace"><a href="logout<?php echo $x; ?>"><?php echo $l['Logout']; ?></a></li>
				</ul>
		  	</div>

		  	<hr>
			
			<div class="row-fluid marketing">
				<h2>Settings</h2>
				<br />
				<?php
					if($_SESSION['UserLevel'] >= 5) {
						# Show the plugin boxes
						echo '<p>Below are the available plugins for this version of TinyMTR</p>';
						
						echo '<div class="row">';
						echo '<div class="col-md-3">';
						echo '<div class="panel panel-default">';
						echo '<div class="panel-heading">Multi User Plugin</div>';
						echo '<div class="panel-body">';
						echo '<p>Allows TinyMTR to be used by the public, allowing sign-ups.</p>';
						
						# Is the plugin enabled?
						if(file_exists('Plugins/MultiUser.php')) {
							echo '<div align="center"><span class="label label-success">ENABLED</span></div>';
						}else{
							echo '<div align="center"><span class="label label-danger">DISABLED</span></div>';
						}
						
						echo '</div>';
						echo '</div>';
						echo '</div>';
						
						// --------------------------------------------------------------------------------------------
						
						echo '<div class="col-md-3">';
						echo '<div class="panel panel-default">';
						echo '<div class="panel-heading">Multi Server Plugin</div>';
						echo '<div class="panel-body">';
						echo '<p>Allows you to ping from external servers in other locations.</p>';
						
						# Is the plugin enabled?
						if(file_exists('Plugins/MultiServer.php')) {
							echo '<div align="center"><span class="label label-success">ENABLED</span></div>';
						}else{
							echo '<div align="center"><span class="label label-danger">DISABLED</span></div>';
						}
						
						echo '</div>';
						echo '</div>';
						echo '</div>';
						
						// --------------------------------------------------------------------------------------------
						
						echo '<div class="col-md-3">';
						echo '<div class="panel panel-default">';
						echo '<div class="panel-heading">Stripe Integration Plugin</div>';
						echo '<div class="panel-body">';
						echo '<p>Allows integration of Stripe to buy credits to use on text messages.</p>';
						
						// Plugin is currently not available
						echo '<div align="center"><span class="label label-warning">UNAVAILABLE</span></div>';
						
						echo '</div>';
						echo '</div>';
						echo '</div>';
						
						// --------------------------------------------------------------------------------------------
						
						echo '<div class="col-md-3">';
						echo '<div class="panel panel-default">';
						echo '<div class="panel-heading">Port Monitor Plugin</div>';
						echo '<div class="panel-body">';
						echo '<p>Allows ports other than 80 and 243 to be monitored inside TinyMTR.</p>';
						
						// Plugin is currently not available
						echo '<div align="center"><span class="label label-warning">UNAVAILABLE</span></div>';
						
						echo '</div>';
						echo '</div>';
						echo '</div>';
						echo '</div>';
						
						// --------------------------------------------------------------------------------------------
						
						echo '<hr>';
						
						$stmt = $dbh->prepare("SELECT * FROM users WHERE id = :usrid");
						$stmt->bindParam(':usrid', $_SESSION['UserID']);
						$stmt->execute();

						$userDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
						
						echo '<h4>' . $l['Contact'] . '</h4><br />';
						
						echo '<form role="form" action="?update" method="POST">';
						echo '<div class="form-group">';
						echo '<label for="inputEmail">' . $l['EmailAddr'] . ' (leave blank for no change)</label>';
						echo '<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="' . $userDetails[0]['email'] . '">';
						echo '</div>';
						
						echo '<hr>';
						
						echo '<h4>' . $l['PassUpdate'] . '</h4><br />';
						
						echo '<div class="form-group">';
						echo '<label for="currentPassword">' . $l['CurrPass'] . '</label>';
						echo '<input type="password" class="form-control" id="currentPassword" name="currentPassword" placeholder="' . $l['CurrPass'] . '..">';
						echo '</div>';
						
						echo '<div class="form-group">';
						echo '<label for="newPassword">' . $l['NewPass'] . '</label>';
						echo '<input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="' . $l['NewPass'] . '..">';
						echo '</div>';
						
						echo '<button type="submit" class="btn btn-default">' . $l['UpteDet'] . '</button>';
						echo '</form>';
						
						echo '<hr>';
						
						$stmt = $dbh->prepare("SELECT * FROM users");
						$stmt->execute();

						$userList = $stmt->fetchAll(PDO::FETCH_ASSOC);
						
						echo '<h4>User Management</h4><br />';

						echo '<p>Below are the users that have signed up to this instance of TinyMTR</p>';

						echo '<div class="panel panel-default">';
						echo '<table class="table table-bordered"><tr>';
						echo '<th>Username</th>';
						echo '<th>Email</th>';
						
						# If Stripe plugin enabled
						if($STRIPEINT) {
							echo '<th>SMS Credits</th>';
						}
						echo '</tr>';

						# Go through each user
						foreach($userList as $user) {
							echo '<tr>';
							echo '<td>' . $user['username'] . '</td>';
							echo '<td>' . $user['email'] . '</td>';

							# If Stripe plugin enabled
							if($STRIPEINT) {
								echo '<td>' . $user['credits'] . '</td>';
							}
							
							echo '</tr>';
						}
						
						echo '</table></div>';

					}else{
						# We are a normal user
						$stmt = $dbh->prepare("SELECT * FROM users WHERE id = :usrid");
						$stmt->bindParam(':usrid', $_SESSION['UserID']);
						$stmt->execute();

						$userDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
						
						echo '<h4>' . $l['Contact'] . '</h4><br />';
						
						echo '<form role="form" action="?update" method="POST">';
						echo '<div class="form-group">';
						echo '<label for="inputEmail">Email address (leave blank for no change)</label>';
						echo '<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="' . $userDetails[0]['email'] . '">';
						echo '</div>';
						
						echo '<hr>';
						
						echo '<h4>' . $l['PassUpdate'] . '</h4><br />';
						
						echo '<div class="form-group">';
						echo '<label for="currentPassword">' . $l['CurrPass'] . '</label>';
						echo '<input type="password" class="form-control" id="currentPassword" name="currentPassword" placeholder="' . $l['CurrPass'] . '..">';
						echo '</div>';
						
						echo '<div class="form-group">';
						echo '<label for="newPassword">' . $l['NewPass'] . '</label>';
						echo '<input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="' . $l['NewPass'] . '..">';
						echo '</div>';
						
						echo '<button type="submit" class="btn btn-default">' . $l['UpteDet'] . '</button>';
						echo '</form>';
					}
				?>
			</div>
			
			<hr>

			<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
		  	</div>
		</div>
	</body>
</html>