<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		iAlex (http://codecanyon.net/iAlex)
		overview.php
	*/
	
	include ("config.php");

	if(!isset($_SESSION['Logged_In']) || !isset($_SESSION['User'])) {
		header("Location: " . $siteLoc . "login" . $x);
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
					<li class="active sideSpace"><a href="<?php echo $siteLoc; ?>overview<?php echo $x; ?>"><?php echo $l['Overview']; ?></a></li>
					<li class="sideSpace"><a href="<?php echo $siteLoc; ?>monitor<?php echo $x; ?>"><?php echo $l['Monitors']; ?></a></li>
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

			<?php
				/* Count the servers */
				$stmt = $dbh->prepare("SELECT * FROM servers WHERE userid = :userid");
				$stmt->bindParam(':userid', $_SESSION['UserID']);
				$stmt->execute();
				$serDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
				/* See how many there are */
				$countSer = count($serDetails);

				/* Count the records */
				$stmt = $dbh->prepare("SELECT COUNT(*) FROM records WHERE userid = :userid");
				$stmt->bindParam(':userid', $_SESSION['UserID']);
				$stmt->execute();
				$recDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

				/* Strip the beginning as the function adds this */
				$siteAdd = removePrefix($siteLoc);
				$siteAdd = rtrim($siteAdd, "/");

				/* Get the local server info */
				$getInfo = getServerInfo($siteAdd, $externalFile);
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
				
				/* Work out disk space */
				$dSpaceKB = (disk_total_space("/")/1024);
				$dSpaceMB = $dSpaceKB/1024;
				$dSpaceGB = round($dSpaceMB/1024, 2);
				$dFSpaceKB = (disk_free_space("/")/1024);
				$dFSpaceMB = $dFSpaceKB/1024;
				$dFSpaceGB = round($dFSpaceMB/1024, 1);
				$dsUsed = round($dSpaceGB - $dFSpaceGB, 1);
				$getSpPercent = round(($dsUsed / $dSpaceGB) * 100);
				
				//echo 'Disk Space: ' . $dSpaceGB . 'GB<br />';
				//echo 'Free Disk Space: ' . $dFSpaceGB . 'GB<br />';
				//echo 'Used Space: ' . $dsUsed . 'GB';
				
				$upTime = sysUptime();
			?>

			<h3 align="center"><?php echo $l['SystemOverview']; ?></h3>
			<br />
			<div class="row">
				<div class="col-md-4" align="center">
					<div class="circleHold">
						<h4><?php echo $l['Servers']; ?></h4>
						<?php echo $countSer; ?>
					</div>
				</div>

				<div class="col-md-4" align="center">
					<div class="circleHold">
						<h4><?php echo $l['Uptime']; ?></h4>
						<?php echo $upTime; ?>
					</div>
				</div>

				<div class="col-md-4" align="center">
					<div class="circleHold">
						<h4><?php echo $l['Records']; ?></h4>
						<?php echo number_format($recDetails[0]['COUNT(*)']); ?>
					</div>
				</div>
			</div>
			<br />
			
				<h2 align="center">TinyMTR Monitoring System</h2>
				<h4 align="center">v1.2.0</h4>
			
			<br />
			<div class="row">
				<div class="col-md-4" align="center">
					<div class="circleHold">
						<h4><?php echo $l['Disk']; ?></h4>
						<div class="progress" style="width:75%; margin: 0 auto;" align="center">
							<span style="font-size:10px;"><?php echo $dFSpaceGB; ?>GB</span>
							<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="' . $getMemPercent . '" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $getSpPercent; ?>%">
								<span style="font-size:10px;"><?php echo $dsUsed; ?>GB</span><span class="sr-only"><?php echo $getSpPercent; ?></span>
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-md-4" align="center">
					<div class="circleHold">
						<h4><?php echo $l['Load']; ?></h4>
						<span class="label label-success"><?php echo $getInfo['load1']; ?></span> -
						<span class="label label-info"><?php echo $getInfo['load5']; ?></span> -
						<span class="label label-warning"><?php echo $getInfo['load15']; ?></span>
					</div>
				</div>
				
				<div class="col-md-4" align="center">
					<div class="circleHold">
						<h4><?php echo $l['Ram']; ?></h4>
						<div class="progress" style="width:75%; margin: 0 auto;" align="center">
							<span style="font-size:10px;"><?php echo $memLeft; ?>MB</span>
							<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $getMemPercent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $getMemPercent; ?>%">
								<span style="font-size:10px;"><?php echo $getInfo['memoryused']; ?>MB</span><span class="sr-only"><?php echo $getMemPercent; ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<hr>

		  	<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
		  	</div>
		</div>
	</body>
</html>