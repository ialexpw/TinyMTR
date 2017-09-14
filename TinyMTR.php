<?php
	/*
		TinyMTR Web Monitor
		Version 1.2.1
		iAlex (http://codecanyon.net/iAlex)
		TinyMTR.php
	*/

	#################################
	// For TinyMTR External Server //
	#################################

	/* Define the class */
	$TinyMTR = new TinyMTR();

	/* Get the information on System Memory */
	$jsonInfo = json_encode($TinyMTR->getSystemMemInfo());
	$jsonInfo = stripslashes($jsonInfo);

	/* Get the information on System Load */
	$jsonLoad = json_encode($TinyMTR->getSystemLoad());
	$jsonLoad = stripslashes($jsonLoad);

	/* Get the information on System Disk */
	$jsonDisk = json_encode($TinyMTR->getSystemDisk());
	$jsonDisk = stripslashes($jsonDisk);

	/* Get the information on System Uptime */
	$jsonUptime = json_encode($TinyMTR->getSystemUptime());
	$jsonUptime = stripslashes($jsonUptime);

	###########
	// CHECK //
	###########

	/* Respond to checks of this file */
	if(isset($_GET['verify'])) {
		exit('active');
	}

	###########
	// CHECK //
	###########

	############
	// MEMORY //
	############

	/* Decode the JSON and get the server memory usage */
	$jsonDecMem = json_decode($jsonInfo, true);
	$jsonDecMemTotal = explode(' ', $jsonDecMem['MemTotal']);
	$jsonDecMemFree = explode(' ', $jsonDecMem['MemFree']);
	$jsonDecMemBuff = explode(' ', $jsonDecMem['Buffers']);
	$jsonDecMemCache = explode(' ', $jsonDecMem['Cached']);
	$jsonCurrentMem = $jsonDecMemTotal[0] - $jsonDecMemFree[0] - $jsonDecMemBuff[0] - $jsonDecMemCache[0];
	$jsonCurrentMem = round($jsonCurrentMem/1024);
	$jsonDecMemTotal = round($jsonDecMemTotal[0]/1024);
	//$Memory = array('memory' => $jsonCurrentMem);
	$Memory = array('memoryused' => $jsonCurrentMem, 'memorytotal' => $jsonDecMemTotal);
	$Memory = json_encode($Memory);

	echo $Memory;

	############
	// MEMORY //
	############

	echo '::';

	##########
	// LOAD //
	##########

	/* Decode the JSON and get the server load */
	$jsonDecLoad = json_decode($jsonLoad, true);
	$jsonDecLoad = explode(' ', $jsonDecLoad['load']);
	$Load = array('load1' => $jsonDecLoad[0], 'load5' => $jsonDecLoad[1], 'load15' => $jsonDecLoad[2]);
	$Load = json_encode($Load);

	echo $Load;

	##########
	// LOAD //
	##########

	echo '::';

	##########
	// DISK //
	##########

	/* Decode the JSON and get the server disk */
	$jsonDecDisk = json_decode($jsonDisk, true);
	$Disk = json_encode($jsonDecDisk);

	echo $Disk;

	##########
	// DISK //
	##########

	echo '::';

	############
	// UPTIME //
	############

	$jsonDecUptime = json_decode($jsonUptime, true);
	$Uptime = json_encode($jsonDecUptime);

	echo $Uptime;

	############
	// UPTIME //
	############

	class TinyMTR {
		function getSystemMemInfo() {       
			$memData = explode("\n", file_get_contents("/proc/meminfo"));
			$memInfo = array();
			foreach ($memData as $line) {
				list($key, $val) = explode(":", $line);
				$memInfo[$key] = trim($val);
			}
			return $memInfo;
		}

		function getSystemLoad() {
	        if(file_exists('/proc/loadavg')) {
	            $load = file_get_contents('/proc/loadavg');
	            $load = explode(' ', $load, 1);
	            $load = $load[0];
	        }
	        elseif(function_exists('shell_exec')) {
	            $load = explode(' ', `uptime`);
	            $load = $load[count($load)-1];
	        }else {
	            return false;
	        }

	        if(function_exists('shell_exec')) {
	        	$cpu_count = shell_exec('cat /proc/cpuinfo | grep processor | wc -l');
	        }

			return array('load' => $load, 'procs' => $cpu_count);
		}

		function getSystemDisk() {
			$dSpaceKB = (disk_total_space("/")/1024);
			$dSpaceMB = $dSpaceKB/1024;
			$dSpaceGB = round($dSpaceMB/1024, 2);
			$dFSpaceKB = (disk_free_space("/")/1024);
			$dFSpaceMB = round($dFSpaceKB/1024);
			$dFSpaceGB = round($dFSpaceMB/1024, 1);
			$dsUsed = round($dSpaceGB - $dFSpaceGB, 1);
			$getSpPercent = round(($dsUsed / $dSpaceGB) * 100);

			return array('diskMB' => "$dSpaceMB", 'diskGB' => "$dSpaceGB", 'diskMBfree' => "$dFSpaceMB", 'diskGBfree' => "$dFSpaceGB", 'diskused' => "$dsUsed", 'diskpercent' => "$getSpPercent");
		}

		function getSystemUptime() {
			$up = file_get_contents('/proc/uptime');
			$data = explode(" ", $up); # from split()

			$days = floor($data[0]/60/60/24);
			$hours = $data[0]/60/60%24;
			$minutes = $data[0]/60%60;
			$seconds = $data[0]%60;

			return array('days' => "$days", 'hours' => "$hours", 'minutes' => "$minutes", 'seconds' => "$seconds");
		}
	}
?>