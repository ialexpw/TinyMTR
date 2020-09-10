<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		about.php
	*/
	
	include ("config.php");
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
		<link href="<?php echo $siteLoc; ?>css/main.css" rel="stylesheet" media="screen">
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
					<li class="sideSpace"><a href="index<?php echo $x; ?>"><?php echo $l['Home']; ?></a></li>
					<li class="active sideSpace"><a href="about<?php echo $x; ?>"><?php echo $l['About']; ?></a></li>
					<li class="sideSpace"><a href="login<?php echo $x; ?>"><?php echo $l['Login']; ?></a></li>
					<?php
						if($MULTIUSER) {
							echo '<li class="sideSpace"><a href="register' . $x . '">' . $l['Register'] . '</a></li>';
						}
					?>
				</ul>
		  	</div>

			<hr>

			<h4>About TinyMTR</h4>
			<br />
			<p>TinyMTR is an all-in-one server monitoring tool meant for Shared, VPS or Dedicated servers, although it was created to monitor multiple VPS nodes inside one interface,
			with very little set-up time.</p>
			
			<p>With an easy-to-use automatic installer, all you need to edit is one file with your website and database details, and the installer will do the rest. It creates all
			the database tables you will need, as well as the user that you will login to TinyMTR with.</p>
			
			<p>After setting up your cron job to run every 5 minutes, TinyMTR will take care of all your logging for you, and will start reporting the statistics after just the 
			first 5 minutes. Showing you the ping result times and the status of the website itself. As well as the memory and load status of the website if you wish to include it.</p>
			
			<p>If you wish to try out TinyMTR, give the demo a go at the <a href="http://tinymtr.co">TinyMTR</a> homepage. Or buy it <a href="http://codecanyon.net/item/tinymtr-simple-server-monitoring/6448123">here!</a></p>

			<hr>

		  	<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
		  	</div>
		</div>
	</body>
</html>