<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		https://picotory.com
		login.php
	*/
	
	include ("config.php");

	if(isset($_GET['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
		$user = $_POST['username'];
		$pass = $_POST['password'];

		$hashPass = passKey($user, $pass, CYCLE_ONE, CYCLE_TWO);

		/* Try and find the user */
		$stmt = $dbh->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
		$stmt->bindParam(':username', $user);
		$stmt->bindParam(':password', $hashPass);
		$stmt->execute();

		$userDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$count = count($userDetails);
		
		/* Account exists */
		if($count) {
			$_SESSION['Logged_In'] = 1;
			$_SESSION['User'] = $user;
			$_SESSION['UserID'] = $userDetails[0]['id'];
			$_SESSION['UserLevel'] = $userDetails[0]['level'];

			header("Location: " . $siteLoc . "overview" . $x);
		}else{
			header("Location: " . $siteLoc . "login" . $x . '?error');
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
		</style>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
	</head>
	
	<body>
		<div class="container-narrow">
			<div class="masthead">
				<img style="margin:0 auto; display:block;" src="<?php echo $siteLoc; ?>img/logo.png" /><br />
				<!--<h2 align="center" class="muted sideSpace"><?php //echo $siteName; ?></h2><br />-->

				<ul class="nav nav-pills nav-justified">
					<li class="sideSpace"><a href="<?php echo $siteLoc; ?>index<?php echo $x; ?>"><?php echo $l['Home']; ?></a></li>
					<li class="sideSpace"><a href="<?php echo $siteLoc; ?>about<?php echo $x; ?>"><?php echo $l['About']; ?></a></li>
					<li class="active sideSpace"><a href="<?php echo $siteLoc; ?>login<?php echo $x; ?>"><?php echo $l['Login']; ?></a></li>
					<?php
						if($MULTIUSER) {
							echo '<li class="sideSpace"><a href="' . $siteLoc . 'register' . $x . '">' . $l['Register'] . '</a></li>';
						}
					?>
				</ul>
		  	</div>

			<hr>
			
			<?php
				/* User does not exist */
				if(isset($_GET['error'])) {
					echo '<div class="alert alert-warning">';
					echo '<strong>Warning!</strong> You seem to have typed in the wrong username or password!';
					echo '</div>';
				}
			?>

			<div class="row-fluid marketing">
				<form class="form-horizontal" action='?login' method="POST">
					<fieldset>
						<div id="legend">
							<legend class=""><?php echo $l['Login']; ?></legend>
						</div>    
						<div class="control-group">
							<div class="row">
								<div class="col-md-2"></div>
								<div class="col-md-8">
									<!-- Username -->
									<label class="control-label" for="username"><?php echo $l['Username']; ?></label>
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
									<!-- Password-->
									<label class="control-label" for="password"><?php echo $l['Password']; ?></label>
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
										<button class="btn btn-success" type="submit"><?php echo $l['LoginTinyMTR']; ?></button>
									</div>
								</div>
								<div class="col-md-2"></div>
							</div>
						</div>
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