<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		remote.php
	*/
	
	include ("config.php");

	# Not logged in
	if(!isset($_SESSION['Logged_In']) || !isset($_SESSION['User'])) {
		header("Location: login" . $x);
	}

	# If not using the plugin, no point in being here
	if(!$MULTISERV) {
		exit('Multi server plugin is not installed.');
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
							echo '<li class="active sideSpace"><a href="remote' . $x . '">' . $l['Remote'] . '</a></li>';
						}

						echo '<li class="sideSpace"><a href="settings' . $x . '">' . $l['Settings'] . '</a></li>';
					?>
					<li class="sideSpace"><a href="logout<?php echo $x; ?>"><?php echo $l['Logout']; ?></a></li>
				</ul>
		  	</div>

		  	<hr>

			<div class="row-fluid marketing">
				<h2>Remote Servers</h2>
				<br />
				<p>Below are the remote servers that you can use to ping your servers and their current uptime.</p>
				<div id="loadRemote" style="margin-top:20px;" align="center">Loading remote servers and checking their status...</div>
				<div id="updateDiv"></div>
			</div>

			<hr>

			<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
			</div>
		</div>
		
		<script>
			// Initially load the table
			$(document).ready(function () {
				$('#updateDiv').load('heartbeat<?php echo $x; ?>').stop().fadeIn();
			});

			// Hide the loading text
			setInterval(function(){
				$('#loadRemote').hide();
			}, 4500);

			// Reload the table every 60 seconds
			setInterval(function(){
				$('#updateDiv').load('heartbeat<?php echo $x; ?>').stop().fadeIn();
			}, 60000);
		</script>
	</body>
</html>