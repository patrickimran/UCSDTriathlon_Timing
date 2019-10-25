<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />    
        <meta http-equiv="X-UA-Compatible" content="IE=Edge"/>     
        <link href="/race/timing.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Cantarell|Quicksand">
		<title>UCSD Triathlon Timing Results</title>
		<!--jQuery-->
		<script type="text/javascript" src="/race/js/jquery.min.js"></script>
		</script>
		<!-- Table Filter: Picnet table filter at http://www.picnet.com.au/picnet-table-filter.html -->
		<script type="text/javascript" src="/race/js/picnet.table.filter.min.js"></script>
		<!-- Table sort: jQuery tablesorter at http://tablesorter.com -->
		<script type="text/javascript" src="/race/js/jquery.tablesorter.min.js"></script>
		<!-- Initialize tablesorter -->
		<script type="text/javascript">
		$(document).ready(function(){ 
			$("#data-table").tablesorter();
			var options = {
				clearFiltersControls: [$('#cleanfilters')],            
			};
			$('#data-table').tableFilter(options);
		});
		</script>
    </head>

<?php
set_time_limit(300); // Give the server time to crunch through everything
	$action= "";
	if ( isset($_GET['action']) ) $action = $_GET['action'];
	if ( isset($_POST['action']) ) $action = $_POST['action'];
?>

<body class="maintext">
<div id="all">
	<div id="content">
	<form class="timing" action="results.php" method="post" enctype="multipart/form-data" >
	<input type="hidden" name="action" value="load">
		<div> Race name: <input type="text" name="racename" value="" size="20" class="maintext"> </div>
		<div> Race date: <input type="text" name="racedate" value="" size="20" class="maintext"> </div>
		<div id="Uploadcontainer">
			<div class="uploadfile"> Final entrants file:<input type="file" name="tags" size="20" class="maintext"/></div>
		</div>
		<div> Number of events: 
			<input type="number" name="num_events" size="2"  min="1" value="3">
		</div>
	
		<div> Transition in: <input type="file" name="trans_in" value="" size="20" class="maintext"></div>
		<div> Transition out: <input type="file" name="trans_out" value="" size="20" class="maintext"></div>
		<div> Finish: <input type="file" name="finish" value="" size="20" class="maintext"></div>
	  
	  <div> Event start time (H:M:S): <input type="text" name="start" value="" size="20" class="maintext"></div>
	  <div> Box offsets (optional - +/-H:M:S) <br>
		  	Transition in correction: <input type="text" name="trans_in_offset" value="+0:00:00" size="20" class="maintext"><br>
		  	Transition out correction: <input type="text" name="trans_out_offset" value="+0:00:00" size="20" class="maintext"><br>
		  	Finish offset: <input type="text" name="finish_offset" value="+0:00:00" size="20" class="maintext">
	  </div>


	  <div> <input type="submit" value="Upload" class="maintext"></div>
	</form>


<?php
if ( $action == "load" ) {
	// Parse user-set information
	$num_events = intval($_POST['num_events']);
	$start1 = explode(":", $_POST['start']);
	$start_time = ($start1[0]*3600)+($start1[1]*60)+($start1[2]);
	$racename = $_POST['racename'];
	$racedate = $_POST['racedate'];

	// Corrections
	$t_in_correction = parseClock($_POST['trans_in_offset']);
	$t_out_correction = parseClock($_POST['trans_out_offset']);
	$finish_correction = parseClock($_POST['finish_offset']);


	// Convert the race title into a new directory
	$racefolder = rawurlencode(utf8_encode($racename));
    $racefolder = str_replace("%20", "_", $racefolder);
	if (!is_dir("./".$racefolder)){
		mkdir("./".$racefolder,0755);
	}

	// Arrays for times
	$times1 = array(); // All transition (in) time data
	$times2 = array(); // All transition (out) time data
	$times3 = array(); // All finish data
	$errormsg = '';
	
	// 2) TRANSITION: load times1 & times2 (if needed)  - - - - - - - - - - 
	if ($num_events>1){
		// (Transition in)
		$handle = fopen($_FILES['trans_in']['tmp_name'], "r");
		if ($handle) {
			$cur = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				if ( $buffer != "" ) {
					$times1[$cur]['reader'] = substr($buffer, 2, 2);
					$times1[$cur]['tag'] = substr($buffer, 4, 12);
					$times1[$cur]['mata'] = substr($buffer, 16, 2);
					$times1[$cur]['matb'] = substr($buffer, 18, 2);
					$times1[$cur]['date'] = '20'.substr($buffer, 20, 2).'-'.substr($buffer,22, 2).'-'.substr($buffer,24, 2);
					$times1[$cur]['time'] = substr($buffer, 26, 2).':'.substr($buffer,28, 2).':'.substr($buffer,30, 2);
					$times1[$cur]['ntime'] = (substr($buffer, 26, 2)*3600)+(substr($buffer,28, 2)*60)+(substr($buffer,30, 2));
					// Adjust clock to cover offset
					$times1[$cur]['ntime'] = $times1[$cur]['ntime'] + $t_in_correction;
					$cur++;				
				}
			} 
			fclose($handle);
		}else{$errormsg=$errormsg."Transition in file not valid. ";}

		// (Transition out) - - - - - - - - - - -
		$handle = fopen($_FILES['trans_out']['tmp_name'], "r");
		if ($handle) {
			$cur = 0;
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				if ( $buffer != "" ) {
					$times2[$cur]['reader'] = substr($buffer, 2, 2);
					$times2[$cur]['tag'] = substr($buffer, 4, 12);
					$times2[$cur]['mata'] = substr($buffer, 16, 2);
					$times2[$cur]['matb'] = substr($buffer, 18, 2);
					$times2[$cur]['date'] = '20'.substr($buffer, 20, 2).'-'.substr($buffer,22, 2).'-'.substr($buffer,24, 2);
					$times2[$cur]['time'] = substr($buffer, 26, 2).':'.substr($buffer,28, 2).':'.substr($buffer,30, 2);
					$times2[$cur]['ntime'] = (substr($buffer, 26, 2)*3600)+(substr($buffer,28, 2)*60)+(substr($buffer,30, 2));
					// Adjust clock to cover offset
					$times2[$cur]['ntime'] = $times2[$cur]['ntime'] + $t_out_correction;
					$cur++;
					}
				} 
				fclose($handle);
		}else{$errormsg=$errormsg."Transition out file not valid. ";}
	}
	// 3) FINISH: Load finish times - - - - - - - - - - - - 
	$handle = fopen($_FILES['finish']['tmp_name'], "r");
	if ( $handle ) {
		$cur = 0;
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			if ( $buffer != "" ) {
				$times3[$cur]['reader'] = substr($buffer, 2, 2);
				$times3[$cur]['tag'] = substr($buffer, 4, 12);
				$times3[$cur]['mata'] = substr($buffer, 16, 2);
				$times3[$cur]['matb'] = substr($buffer, 18, 2);
				$times3[$cur]['date'] = '20'.substr($buffer, 20, 2).'-'.substr($buffer,22, 2).'-'.substr($buffer,24, 2);
				$times3[$cur]['time'] = substr($buffer, 26, 2).':'.substr($buffer,28, 2).':'.substr($buffer,30, 2);
				$times3[$cur]['ntime'] = (substr($buffer, 26, 2)*3600)+(substr($buffer,28, 2)*60)+(substr($buffer,30, 2));
				$times3[$cur]['ntime'] = $times3[$cur]['ntime'] + $finish_correction;
				$cur++;
			}
		} 
		fclose($handle);
	}else{$errormsg=$errormsg."Finish file not valid. ";}


	// - - - - - - - 0) INITIALIZE: create arrays and parse input values - - - - - - - - - - 
	$data = array(); //Temporary storage place for reading csv rows
	$racers = array(); // Holding place for all data
	$handle = fopen($_FILES['tags']['tmp_name'], "r");
	if ( $handle ) {
		//Get headers, find locs of data needed for output
		$headers = fgetcsv($handle, 10000, ",");
		$key_raceno = array_search ('Bib #', $headers);
		$key_RFID = array_search ('RFID', $headers);
		$key_firstname = array_search ('First Name', $headers);
		$key_lastname = array_search ('Last Name', $headers);
		$key_gender = array_search ('Gender', $headers);
		$key_division = array_search ('Division', $headers);
		$key_offset = array_search ('Offset (min)', $headers);
		$key_HS = array_search ('High School', $headers);
		$key_univ = array_search ('University', $headers);
		$key_AG = array_search ('Age Group', $headers);
		$key_adj = array_search ('Time Adjustment', $headers);

		echo '<h2>'.$key_divison.'</h2>';

		$cur = 0;
		while (($data = fgetcsv($handle, 10000, ",")) != FALSE) {			
			// Racer information
			$racers['raceno'][$cur] = $data[$key_raceno];
			$racers['RFID'][$cur] = $data[$key_RFID];
			$racers['name'][$cur] = $data[$key_firstname].' '.$data[$key_lastname];
			$racers['gender'][$cur] = $data[$key_gender];
			$racers['division'][$cur] = $data[$key_division];
			$racers['append'][$cur] = '';
			// Choose racer's info based on division
			if (strcmp($racers['division'][$cur],'Collegiate')==0){
				$racers['info'][$cur] = $data[$key_univ];
			}elseif(strcmp($racers['division'][$cur],'High School')==0){
				$racers['info'][$cur] = $data[$key_HS];
			}elseif(strcmp($racers['division'][$cur],'Elite')==0){
				$racers['info'][$cur] = "Elite";
			}else{
				$racers['info'][$cur] = $data[$key_AG];
			}

			// Modify relay as necessary
			if (strcasecmp($racers['division'][$cur],'Relay')==0){
				$key_team = array_search('Relay Name', $headers);
				$racers['name'][$cur] = $data[$key_team];
				$racers['gender'][$cur] = '-';
			}

			// Calculate times
			$offset = parseClock($data[$key_offset]);
			$start_time2 = $start_time+$offset;
			$mintime = $start_time2;
			
			$output = "<script>console.log( 'Debug Objects: " . $mintime . "' );</script>";
    		echo $output;

			for($i=1;$i<(2*$num_events - 1);$i+=2){
				// Transition-in times
				$ind =  findTag($racers['RFID'][$cur],$times1,$mintime);		
				$time1 = $times1[$ind]['ntime'];
				$racers['t_'.$i][$cur] = subtractTimes($time1, $mintime);
				if ($racers['t_'.$i][$cur] < 5){$racers['t_'.$i][$cur] = 9999999999;}
				if ($time1>0){$mintime = $time1;}

				// Transition-out times
				$ind =  findTag($racers['RFID'][$cur],$times2,$mintime);		
				$time2 = $times2[$ind]['ntime'];
				$racers['t_'.($i+1)][$cur] = subtractTimes($time2, $mintime);
				if ($racers['t_'.($i+1)][$cur] < 5){$racers['t_'.($i+1)][$cur] = 9999999999;}

				if ($time2>0){$mintime = $time2;}
			}
			// Parse corrections
			$correction = $data[$key_adj];
			// "No Splits" corrections
			if (strcasecmp(substr($correction,0,2),"ST")==0){
				$correction_times = substr($correction,strpos($correction,"(")+1,(strpos($correction,")")-strpos($correction,"(")-1));
				$times = explode(';',$correction_times);
				for($g=0; $g<count($times); ++$g){
					if (strpos($times[$g] , ':' )===FALSE){
						$racers['t_'.($g+1)][$cur] = 9999999999;
					}else{$racers['t_'.($g+1)][$cur] = parseClock($times[$g]);}
				}
			}

			// ['No chip' correction - use estimated final race time]
			if ((strcasecmp(substr($correction,0,2),"NC")==0) || (strcasecmp(substr($correction,0,2),"FT")==0)){
				$correction_time = substr($correction,strpos($correction,"(")+1,(strpos($correction,")")-strpos($correction,"(")-1));
				$time3 = parseClock($correction_time)+$start_time2;
			}elseif ((strcasecmp(substr($correction,0,2),"NF")==0)|| (strcasecmp(substr($correction,0,2),"TD")==0)){ // In case of manually-added finish time (time of day)
					$correction_time = substr($correction,strpos($correction,"(")+1,(strpos($correction,")")-strpos($correction,"(")-1));
					$time3= parseClock($correction_time);		
			}else{
				// Finish Time: 1st time in finish
				$ind = findTag($racers['RFID'][$cur],$times3,$mintime);		
				$time3 = $times3[$ind]['ntime'];
			}
			$racers['t_end'][$cur] = subtractTimes($time3, $start_time2);

			// Final split (if applicable)
			if ($num_events>1){
				$racers['t_'.$i][$cur] = subtractTimes($time3, $mintime);
			}
			

			// Other corrections: DQ, time changes
			if (strpos($correction,"DQ")!==false){
				$racers['t_end'][$cur] = 9999999999;
				$racers['append'][$cur] = "-DQ";
			}
			// Check racer for time change- TC (in correction)- modify finish time.
			if (strcasecmp(substr($correction,0,2),"TC")==0){
				// Plus sign- add time
				$sign = substr($correction,strpos($correction,"(")+1,1);
				$time_adjust = substr($correction,strpos($correction,$sign)+1,strpos($correction,")")-strpos($correction,$sign)-1);
				if (strcmp($sign,"+")==0){
					$racers['t_end'][$cur] = $racers['t_end'][$cur]+$time_adjust;
				}else{
					$racers['t_end'][$cur] = $racers['t_end'][$cur]-$time_adjust;
				}
				$racers['append'][$cur] = " [".$sign.$time_adjust." sec]";
			}
			++$cur;
		} 
		fclose($handle);
	}

	// Sort finishers
	asort($racers['t_end'],SORT_NUMERIC);
	$finish_order = array_keys($racers['t_end']);


	// Pair up gender and division in preparation for outputting
	$genderlist = array_unique($racers['gender']);
	if (($key = array_search('-', $genderlist)) !== false) {unset($genderlist[$key]);}

	$divisionlist = array_unique($racers['division']);
	if (($key = array_search('Relay', $divisionlist)) !== false) {unset($divisionlist[$key]);}

	// Write to file, starting with all (by gender)
    $raceinfo['name'] = $racename;
    $raceinfo['date'] = $racedate;
    $raceinfo['num_events'] = $num_events;


	// Arrays/flags
	$pagelist = array();
	$relay_flag = 0;
    $all_subsets = array();

    foreach ($genderlist as $gender){
	    // Walk down finishers and make sure that gender matches
        $racer_subset = array();
	    foreach($finish_order as $num){
	    	// Relays are special case; handle separately
	    	if (strcasecmp($racers['division'][$num],'Relay')!=0){ //Only add non-relays
		    	if (strcmp($racers['gender'][$num],$gender)==0){
					foreach($racers as $key => $val){
						$racer_subset[$key][] = $racers[$key][$num];
					}
		    	}
	    	}else{$relay_flag=1;}    	
	    }
		if(!empty($racer_subset)){
			$pagelist[] = 'All-'.substr($gender,0,1);
			$all_subsets[] = $racer_subset;
		}	
	    
	    foreach($divisionlist as $division){	    
		    // Walk down finishers and make sure that gender matches
		    $racer_subset = array();
		    foreach($finish_order as $num){
		    	if ((strcmp($racers['gender'][$num],$gender)==0)&&(strcmp($racers['division'][$num],$division)==0)){
					foreach($racers as $key => $val){
						$racer_subset[$key][] = $racers[$key][$num];
					}
		    	}
		    }
	    	if(!empty($racer_subset)){
	    		$pagelist[] = $division.'-'.substr($gender,0,1);
	    		$all_subsets[] = $racer_subset;
	    	}
	    }
	}
	// Relays were found, make that page too.
    if($relay_flag){
    	$racer_subset = array();
 		foreach($finish_order as $num){
	    	if (strcasecmp($racers['division'][$num],'Relay')==0){
				foreach($racers as $key => $val){
				$racer_subset[$key][] = $racers[$key][$num];
				}
			}
		}
		$pagelist[] = 'Relay';
    	$all_subsets[] = $racer_subset;
	}
	// Go through and make all pages
	for($i=0;$i<count($pagelist);++$i){
		makePage($racefolder,$raceinfo,$pagelist[$i],$pagelist,$all_subsets[$i]);
	    echo 'Made: <a href="/race/'.$racefolder.'/'.$pagelist[$i].'.html" target="_blank">',$pagelist[$i].'</a><br>';

	}


}


// FUNCTIONS
function findTag($tag, $array, $minTime) {// Find first tag occurence (filter by minimum time) in timing box data
	for ($i=0; $i<count($array); $i++ ) {
		if ( $array[$i]['tag'] == $tag ) {
			if($array[$i]['ntime'] >= $minTime) {
				return($i);			
			}
		}
	}
	return -1;
}

function convertTime($time1) {
	if($time1 == 9999999999){
		$time2='NT';	
	} else {	
		$hour = floor($time1/3600);
		$min = floor(($time1-($hour*3600))/60);
		$sec = $time1 - ($hour*3600) - ($min*60);
		$time2 = str_pad($hour, 2, "0", STR_PAD_LEFT).':'.str_pad($min, 2, "0", STR_PAD_LEFT).':'.str_pad($sec, 2, "0", STR_PAD_LEFT);
	}
	return($time2);
}

function subtractTimes($time2,$time1){

	if($time1 <= 0){
		return 9999999999;
	} elseif ($time2 <= 0) {
		return 9999999999;
	}else{
		$output_time = $time2-$time1;
		return($output_time);
	}
}

function parseClock($time_in){
	// Convert a +/- H:M:S into seconds
	$time_out = explode(':',$time_in);
	$multiply = 1;
	if(substr_compare($time_out[0],'-',0,1)==0){
		$multiply = -1;
	}
	$time_out[0] =  preg_replace("/[^0-9,.]/", "", $time_out[0] );
	$time_out = $multiply*((3600*$time_out[0])+(60*$time_out[1])+($time_out[2]));
	return($time_out);
}


function makePage($racefolder,$raceinfo,$pagename,$linklist,$racers){
	$filename = $racefolder.'/'.$pagename.'.html';
	$outputfile = fopen($filename, 'w');		
		// First read header information into file
		$headername = "timing_header.html";
		$headerfile = fopen($headername, "r");
		$header = fread($headerfile, filesize($headername));
		fwrite($outputfile , $header);
		fclose($headerfile);
		
		// Sort racers
		$rank = array();
		for($i=1;$i<(2*$raceinfo['num_events']);++$i){
			$rank_col = $racers['t_'.$i];
			for($j=0;$j<count($rank_col);++$j){
				if ($racers['t_end'][$j]==9999999999){
					$rank_col[$j] = 9999999999;
				}
			}
			sort($rank_col,SORT_NUMERIC);
			for($j=0;$j<count($racers['t_'.$i]);++$j){
				if ($racers['t_end'][$j]==9999999999){
					$rank[$i][] = count($racers['t_'.$i]);
				}else{
					$rank[$i][] = array_search($racers['t_'.$i][$j],$rank_col)+1;
				}
			}
		}

		// RACE INFO
		$content = '';
		$content = $content.'<div class="race-intro"> ucsd triathlon race results:</div>'."\n";
		$content = $content.'<div class="race-title">'.$raceinfo['name'].'-'.$pagename.'</div>'."\n";
		$content = $content.'<div class="race-date">'.$raceinfo['date'].'</div>'."\n";
		// LINKS to other timing pages
		$content = $content.'<div class="group">';
		foreach($linklist as $link){
			// Make valid URL
			$content = $content.'<a class="racelink" href="http://ucsdtriathlon.org/race/'.$racefolder.'/'.$link.'.html">'.$link.'</a>'."\n";			
		}
		$content = $content.'</div>';
		
		// MAIN TABLE
		$content = $content.'<a id="cleanfilters" href="#">reset filters</a>'."\n";
		$content = $content.'Total Entrants: '.count($racers['name']).'<br>';
		$content = $content.'<span class="accent-text">Please direct (polite) comments or concerns to timing AT ucsdtriathlon.org - include your name, race number, and est. time, if missing</span><br>';
		$content = $content.'<table id=\'data-table\'>';	
		$content = $content.'<thead><tr>';
		$content = $content.'<th filter="false">OVR</th>';
		$content = $content.'<th>Name</th>';
		$content = $content.'<th>Bib #</th>';
		$content = $content.'<th filter-type="ddl">Cat./School</th>';
		
		for($i=1;$i<=$raceinfo['num_events'];++$i){
			$content = $content.'<th filter="false"> Event '.$i.' </th>';
			$content = $content.'<th filter="false"> # </th>';
			if ($i<$raceinfo['num_events']){
				$content = $content.'<th filter="false"> T'.$i.' </th>';
				$content = $content.'<th filter="false"> # </th>';
			}
		}
		$content = $content.'<th filter="false">Finish</th>'."\n";
		$content = $content.'</tr></thead><tbody>'."\n";
		for($i = 0; $i<count($racers['name']); ++$i){
			$content = $content.'<tr>';		
			// Overall rank
			$content = $content.'<td  class="divider rank"><b>'.($i+1).'</b></td>';

			// Registration Info
			$content = $content.'<td>'.$racers['name'][$i].'</td>';
			$content = $content.'<td class="narrow">'.$racers['raceno'][$i].'</td>';
			$content = $content.'<td class="divider">'.$racers['info'][$i].'</td>';
			// Times and ranks
			for($j=1;$j<(2*$raceinfo['num_events'] - 1);$j+=2){
				$content = $content.'<td>'.convertTime($racers['t_'.$j][$i]).'</td>';
				$content = $content.'<td class="divider rank">'.$rank[$j][$i].'</td>';
				$content = $content.'<td>'.convertTime($racers['t_'.($j+1)][$i]).'</td>';
				$content = $content.'<td class="divider rank">'.$rank[($j+1)][$i].'</td>';
			}
			if ($raceinfo['num_events']>1){
				$content = $content.'<td>'.convertTime($racers['t_'.$j][$i]).'</td>';
				$content = $content.'<td class="divider rank">'.$rank[$j][$i].'</td>';
			}
			$content = $content.'<td class="divider"><b>'.convertTime($racers['t_end'][$i]).$racers['append'][$i].'</b></td>';
			$content = $content.'</tr>'."\n";	
		}
		$content = $content.'</tbody></table>';
		fwrite($outputfile,$content);
		fwrite($outputfile , '</div></div></body></html>');
	fclose($outputfile);
}






?>

</div><!-- #content -->
</div> <!-- #all -->
</body>
</html>
