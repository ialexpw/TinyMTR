<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		iAlex (http://codecanyon.net/iAlex)
		graphing.php
	*/
	
	include ("config.php");
	
	if(!isset($_SESSION['Logged_In']) || !isset($_SESSION['User'])) {
		header("Location: " . $siteLoc . "login" . $x);
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
			$stmt = $dbh->prepare("SELECT * FROM (SELECT @row := @row +1 AS rownum, id, readtime, load_1, load_5, load_15, memory, disk, status, atimestamp FROM (SELECT @row :=0) r, records WHERE serid = :serid AND userid = :userid ORDER BY atimestamp DESC) ranked WHERE rownum %3 =1 LIMIT 48");
			$stmt->bindParam(':serid', $_GET['id']);
			$stmt->bindParam(':userid', $_SESSION['UserID']);
			$stmt->execute();
		}
		elseif(isset($_GET['30min'])) {
			/* Select the records for the server with the ID (30min) */
			$stmt = $dbh->prepare("SELECT * FROM (SELECT @row := @row +1 AS rownum, id, readtime, load_1, load_5, load_15, memory, disk, status, atimestamp FROM (SELECT @row :=0) r, records WHERE serid = :serid AND userid = :userid ORDER BY atimestamp DESC) ranked WHERE rownum %6 =1 LIMIT 48");
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
		  .graph-container {
		  	border-radius: 3px;
			padding: 30px;
			border: 1px solid #ccc;
			box-shadow: 1px 1px 2px #eee;
			margin: 10px 0;
			position: relative;
		  }
		  .graph-container::after {
		  	border-radius: 3px;
			content: "TinyMTR";
			display: block;
			position: absolute;
			left: -1px;
			top: -1px;
			border: 1px solid #ccc;
			background-color: #eeeeee;
			padding: 3px 8px;
			font-size: 12px;
		  }
		  .graph-container .caption {
			text-align: center;
		  }
		  .graph-container .graph {
			height: 250px;
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
			<h4 align="center"><?php echo $l['AdStatsFor']; ?> <?php echo $srvDetails[0]['address']; ?> (<?php echo $srvDetails[0]['ipaddress']; ?>)</h4>
			<br />

			<p align="center">
				<a href="<?php echo $siteLoc; ?>graphing<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;5min"><?php echo $l['Every5']; ?></a> | 
				<a href="<?php echo $siteLoc; ?>graphing<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;15min"><?php echo $l['Every15']; ?></a> | 
				<a href="<?php echo $siteLoc; ?>graphing<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;30min"><?php echo $l['Every30']; ?></a>
			</p>

			<br />
			<a href="<?php echo $siteLoc; ?>statistics<?php echo $x; ?>?id=<?php echo $_GET['id']; ?>&amp;5min" class="btn btn-default btn-xs pull-right"><?php echo $l['BackStat']; ?></a>
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
			<div class="row">
				<div class="col-md-12">
					<div class="graph-container">
						<div class="caption"><?php echo $l['MemGraph']; ?></div>
						<div id="memMTR" style="height: 250px;"></div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="graph-container">
						<div class="caption"><?php echo $l['LoadGraph']; ?></div>
						<div id="loadMTR" style="height: 250px;"></div>
					</div>
				</div>

				<div class="col-md-6">
					<div class="graph-container">
						<div class="caption"><?php echo $l['DiskUsedPer']; ?></div>
						<div id="diskMTR" style="height: 250px;"></div>
					</div>
				</div>
			</div>

			<script>
				new Morris.Line({
					// ID of the element in which to draw the chart.
					element: 'memMTR',
					hideHover: true,
					lineWidth: 1,
					pointSize: 1,
					// Chart data records -- each entry in this array corresponds to a point on
					// the chart.
					data: [
						<?php
							foreach($recDetails as $dS) {
								$tStamp = date('Y-m-d', $dS['atimestamp']);
								echo "{ time: '$tStamp " . $dS['readtime'] . "', value: " . $dS['memory'] . " },";
							}
						?>
					],
					// The name of the data record attribute that contains x-values.
					xkey: 'time',
					// A list of names of data record attributes that contain y-values.
					ykeys: ['value'],
					// Labels for the ykeys -- will be displayed when you hover over the
					// chart.
					labels: ['Memory']
				});

				new Morris.Line({
					// ID of the element in which to draw the chart.
					element: 'loadMTR',
					hideHover: true,
					lineWidth: 1,
					pointSize: 1,
					// Chart data records -- each entry in this array corresponds to a point on
					// the chart.
					data: [
						<?php
							foreach($recDetails as $dS) {
								$tStamp = date('Y-m-d', $dS['atimestamp']);
								echo "{ time: '$tStamp " . $dS['readtime'] . "', load1: " . $dS['load_1'] . ", load5: " . $dS['load_5'] . ", load15: " . $dS['load_15'] . " },";
							}
						?>
					],
					// The name of the data record attribute that contains x-values.
					xkey: 'time',
					// A list of names of data record attributes that contain y-values.
					ykeys: ['load1', 'load5', 'load15'],
					// Labels for the ykeys -- will be displayed when you hover over the
					// chart.
					labels: ['Load 1min', 'Load 5min', 'Load 15min']
				});

				new Morris.Line({
					// ID of the element in which to draw the chart.
					element: 'diskMTR',
					hideHover: true,
					lineWidth: 1,
					pointSize: 1,
					// Chart data records -- each entry in this array corresponds to a point on
					// the chart.
					data: [
						<?php
							foreach($recDetails as $dS) {
								$tStamp = date('Y-m-d', $dS['atimestamp']);
								echo "{ time: '$tStamp " . $dS['readtime'] . "', value: " . $dS['disk'] . " },";
							}
						?>
					],
					// The name of the data record attribute that contains x-values.
					xkey: 'time',
					// A list of names of data record attributes that contain y-values.
					ykeys: ['value'],
					// Labels for the ykeys -- will be displayed when you hover over the
					// chart.
					labels: ['Disk']
				});
			</script>

			<br />
			<hr>

			<div class="footer">
				<p class="small sideSpace">&copy; Powered by <a href="https://picotory.com">TinyMTR</a> <?php echo date("Y"); ?></p>
			</div>
		</div>
	</body>
</html>