<!DOCTYPE html>
<html>
	<head>
		<title>TinyMTR : Painless Server Monitoring</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="author" content="iAlex@CodeCanyon">
		<meta name="description" content="TinyMTR is a simple server monitoring tool">
		<link rel="shortcut icon" href="img/favicon.ico" />

		<link href='//brick.a.ssl.fastly.net/Open+Sans:300i,400i,600i,700i,400,300,600,700' rel='stylesheet' type='text/css'>
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet">
		<link href="<?php echo $siteLoc; ?>css/main.css" rel="stylesheet" media="screen">
		<link href="<?php echo $siteLoc; ?>css/bootstrap-lightbox.min.css" rel="stylesheet" media="screen">
		<style type="text/css">
		  body {
			padding-top: 20px;
			padding-bottom: 40px;
		  }

		  /* Custom container */
		  .container-narrow {
			margin: 0 auto;
			max-width: 700px;
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
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
	</head>
	
	<body>
		<div class="container-narrow">
			<div style="border-radius:4px;" class="jumbotron">
	  			<h1>Status Page</h1>
	  			<p>Public status page for <?php echo $serCount[0]['address']; ?></p>
			</div>

			<?php
				if($MULTISERV && $serCount[0]['location'] > 0) {
			?>
				<p style="font-size:18px;" align="center">Being Monitored from <?php echo $aServers[$serCount[0]['location']]['Country']; ?></p><br />
			<?php
				}
			?>
			

			<p style="font-size:16px;">Statistics across the last 31 days</p>

			<div class="row" style="margin-left:0px; margin-right:0px; padding:6px; border:#ccc 1px solid; border-radius: 4px;">
