<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		index.php
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
				<img style="margin:0 auto; display:block;" src="<?php echo $siteLoc; ?>img/logo.png" /><br />

				<ul class="nav nav-pills nav-justified">
					<li class="active sideSpace"><a href="<?php echo $siteLoc; ?>index<?php echo $x; ?>"><?php echo $l['Home']; ?></a></li>
					<li class="sideSpace"><a href="<?php echo $siteLoc; ?>about<?php echo $x; ?>"><?php echo $l['About']; ?></a></li>
					<li class="sideSpace"><a href="<?php echo $siteLoc; ?>login<?php echo $x; ?>"><?php echo $l['Login']; ?></a></li>
					<?php
						if($MULTIUSER) {
							echo '<li class="sideSpace"><a href="' . $siteLoc . 'register' . $x . '">' . $l['Register'] . '</a></li>';
						}
					?>
				</ul>
		  	</div>

		  	<hr>
			
			<?php
				if($sqlError) {
					echo '<div class="alert alert-danger">';
					echo '<strong>Error!</strong> There seems to be an error with your SQL details, please double check them inside your config.php page, and then reload the page!';
					echo '</div>';
				}else if(file_exists('install.php')) {
					echo '<div class="alert alert-info">';
					echo '<strong>Info!</strong> Install file is still uploaded! Do you still need to <a href="install.php">install</a> TinyMTR?';
					echo '</div>';
				}
			?>
			
			<div class="jumbotron medium">
				<h1>Server monitoring in one interface!</h1>
				<p class="lead">Have multiple servers and no easy way to monitor all of them? Use TinyMTR to monitor them all in a single easy-to-use interface. Comes with Email &amp; SMS alerts as standard.</p>
				<?php
					if($MULTIUSER) {
						echo '<a class="btn btn-large btn-success" href="' . $siteLoc . 'register' . $x . '">' . $l['RegNow'] . '</a>';
					}else{
						echo '<a class="btn btn-large btn-success" href="' . $siteLoc . 'login' . $x . '">' . $l['LoginNow'] . '</a>';
					}
				?>
				
			</div>

		  	<hr>

		  	<div class="row-fluid marketing">
				<div class="span6">
					<h4><?php echo $l['lUptime']; ?></h4>
					<p>TinyMTR records the uptime of your servers, and shows you exactly when any of them happen to go down.</p>

					<h4><?php echo $l['lLoad']; ?></h4>
					<p>We monitor your server load on each of your servers, and can alert you if any of them go above a threshold.</p>

					<h4><?php echo $l['lMemUse']; ?></h4>
					<p>We record memory usage for you, so you are able to see at what point of the day that your servers started slowing.</p>
				</div>

				<div class="span6">
					<h4><?php echo $l['Alerts']; ?></h4>
					<p>We are able to alert you in multiple ways when servers go down, either through Email or sent to your phone through SMS. </p>

					<h4><?php echo $l['Automatic']; ?></h4>
					<p>With TinyMTR, all you have to do is sign up and add your servers, then sit back and let us do the rest of the work for you.</p>

					<h4><?php echo $l['Interface']; ?></h4>
					<p>At TinyMTR we have created a clean interface for you to keep an eye on all your servers, without all the mess around.</p>
				</div>
		  	</div>

		  	<hr>

		  	<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
		  	</div>
		</div>
	</body>
</html>