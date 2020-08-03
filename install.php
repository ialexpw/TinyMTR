<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		install.php
	*/

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	include ("config.php");

	/* If we have everything we need.. */
	if(isset($_GET['do-install']) && !empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['email'])) {
		$user = $_POST['username'];
		$pass = $_POST['password'];
		$email = $_POST['email'];

		$hashPass = password_hash($pass, PASSWORD_DEFAULT);

		/* Install the tables */
		$sql = "CREATE TABLE IF NOT EXISTS cron (
			id int(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			readtime text,
			atimestamp int(18),
			timetook int(20)	
		)ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

		$sq = $dbh->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS records (
			id int(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			userid int(12),
			serid int(12),
			status int(10),
			atimestamp int(20),
			readtime text,
			load_1 float default '0',
			load_5 float default '0',
			load_15 float default '0',
			memory int(10) default '0',
			disk float default '0'
		)ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

		$sq = $dbh->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS servers (
			id int(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			userid int(12),
			ipaddress tinytext,
			address tinytext,
			location int(5) default '0',
			secure int(1) default '0',
			active int(1) default '0',
			alerts varchar(3) default '0:0'
		)ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

		$sq = $dbh->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS users (
			id int(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			username varchar(128),
			password varchar(128),
			email varchar(128) default '0',
			timestamp int(22) not null,
			credits int(10) default '0',
			level int(5) default '0',
			alerts varchar(3) default '0:0'
		)ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

		$sq = $dbh->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS webhooks (
			id int(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			serid varchar(12),
			url varchar(128),
			state int(1),
			sendon int(1)
		)ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";

		$sq = $dbh->query($sql);

		# Try and find the user
		$stmt = $dbh->prepare("SELECT * FROM users WHERE username = :username");
		$stmt->bindParam(':username', $user);
		$stmt->execute();

		$userDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$count = count($userDetails);
		
		if(!$count) {
			$data = array( 'username' => $user, 'password' => $hashPass, 'email' => $email, 'timestamp' => time(), 'level' => 5 );
			$stmt = $dbh->prepare("INSERT INTO users (username, password, email, timestamp, level) VALUES (:username, :password, :email, :timestamp, :level)");
			$stmt->execute($data);

			header("Location: " . $siteLoc . "login" . $x);
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

		  	<div class="row-fluid marketing">

			  	<form class="form-horizontal" action='?do-install' method="POST">
		            <fieldset>
		            	<div id="legend">
		                	<legend class="">Install TinyMTR</legend>
		              	</div>    
		              	<div class="control-group">
							<div class="row">
								<div class="col-md-2"></div>
								<div class="col-md-8">
		                			<!-- Username -->
		                			<label class="control-label" for="username">Username</label>
		                			<div class="controls sideSpace">
		                  				<input type="text" id="username" name="username" placeholder="" class="form-control">
		                			</div>
								</div>
								<div class="col-md-2"></div>
							</div>
		              	</div>

		              	<div class="control-group">
							<div class="row">
								<div class="col-md-2"></div>
								<div class="col-md-8">
		                			<!-- Email -->
		                			<label class="control-label" for="email">Email</label>
		                			<div class="controls sideSpace">
		                  				<input type="text" id="email" name="email" value="" class="form-control">
		                			</div>
								</div>
								<div class="col-md-2"></div>
							</div>
		              	</div>

		              	<div class="control-group">
							<div class="row">
								<div class="col-md-2"></div>
								<div class="col-md-8">
		                			<!-- Password -->
		                			<label class="control-label" for="password">Password</label>
		                			<div class="controls sideSpace">
		                  				<input type="password" id="password" name="password" placeholder="" class="form-control">
		                			</div>
								</div>
								<div class="col-md-2"></div>
							</div>
		              	</div>

						<br />
						<div class="control-group">
							<div class="row">
								<div class="col-md-2"></div>
								<div class="col-md-8">
									<!-- Button -->
									<div class="controls sideSpace">
										<button class="btn btn-success" type="submit">Install TinyMTR</button>
									</div>
								</div>
								<div class="col-md-2"></div>
							</div>
						</div>
						
						<hr>
						
						<p>Make sure you have configured the config.php file before running this web installer, the MySQL details must be correct to proceed successfully.</p>
		            </fieldset>
	        	</form>
	        </div>
	        
	        <hr>

		  	<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
		  	</div>
	    </div>
	</body>
</html>