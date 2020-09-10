<?php
	/*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		heartbeat.php
	*/
	
	include ("config.php");

	# Not logged in
	if(!isset($_SESSION['Logged_In']) || !isset($_SESSION['User'])) {
		header("Location: " . $siteLoc . "login" . $x);
	}

	# If not using the plugin, no point in being here
	if(!$MULTISERV) {
		exit('Multi server plugin is not installed.');
	}
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th><?php echo $l['Identity']; ?></th>
			<th><?php echo $l['Location']; ?></th>
			<th><?php echo $l['Status']; ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$i=0;
			foreach ($aServers as $arrServ) {
				$cnFlag = strtolower($arrServ['Shorter']);

				# Skip the local server
				if($i>0) {
					$rDet = getServerInfo($arrServ['Address'], 'TinyMTR.php');

					if(checkMTR($arrServ['Address'], 'TinyMTR.php', 0, 1)) {
						$state = $l['lUptime'] . ': ' . $rDet['days'] . ' day' . ($rDet['days'] != 1 ? 's' : '') . ', ' . $rDet['hours'] . ' hour' . ($rDet['hours'] != 1 ? 's' : '') . ' and ' . $rDet['minutes'] . ' minute' . ($rDet['minutes'] != 1 ? 's' : '') . '.';
					}else{
						$state = 'Unable to contact remote server.';
					}
					
					echo '<tr>';
					echo '<td>' . ($i+2500) . '</td>';
					echo '<td>' . $arrServ['Country'] . '<img style="margin-top:4px;" class="pull-right" src="img/flags/' . $cnFlag . '.png" /></td>';
					echo '<td>' . $state . '</td>';
					echo '</tr>';
				}
				
				$i++;
			}
		?>
	</tbody>
</table>

<p>This data will automatically reload every 60 seconds.</p>