<?php
$file = fopen("/usr/local/nagios/var/status.dat", "r") or exit("Unable to open file!");
$hosts    = array();
$services = array();
$states   = array(0=>"ok",1=>"warning",2=>"critical",3=>"unknown");
echo "<table width=90% border=0 class=boldtable align=center class=head>";

echo '<tr class="head">
        <th nowrap></th>
        <th nowrap>Last Checked</th>
        <th style="width:160px;">Host</th>
        <th style="width:260px;">Service</td>
        <th width=100%>Status Info</th>
</tr>';


function duration($start,$end=null) {
 $end = is_null($end) ? time() : $end;

 $seconds = $end - $start;
 
 $days = floor($seconds/60/60/24);
 $hours = $seconds/60/60%24;
 $mins = $seconds/60%60;
 $secs = $seconds%60;
 
 $duration='';
 if($days>0) $duration .= "$days"."d ";
 if($hours>0) $duration .= "$hours"."h ";
 if($mins>0) $duration .= "$mins"."m ";
 if($secs>0) $duration .= "$secs"."s ";
 
 $duration = trim($duration);
 if($duration==null) $duration = '0 seconds';
 
 return $duration;
}



function getval($line){ return trim(substr($line, strpos($line, '=') + 1, strlen($line))); }
function readobj() {
    global $file;
    $obj = array();
    while (!feof($file)) {
	$line = fgets($file);
	if (strpos($line, '}')       			!== false) 	break;
	if (strpos($line, 'host_name' ) 		!== false)	{$obj['host']	  =getval($line);}
	if (strpos($line, 'service_description')	!== false) 	{$obj['service']  =getval($line);}
	if (strpos($line, 'current_state') 		!== false)      {$obj['state']	  =getval($line);}
	if ((strpos($line, 'plugin_output') 		!== false)&&
	    (strpos($line, 'long_plugin_output') 	== false))      {$obj['plugin']   =getval($line);}
	if (strpos($line, 'last_check') 	   	!== false) 	{$obj['lastcheck']=getval($line);}
	if (strpos($line, 'been_acknowledged' )       	!== false)   	{$obj['ack']      =getval($line);}
	if (strpos($line, 'scheduled_downtime_depth') 	!== false) 	{$obj['downtime'] =getval($line);}
	if (strpos($line, 'last_state_change')     	!== false)      {$obj['lasthsch'] =getval($line);}
	if (strpos($line, 'current_attempt')     	!== false)      {$obj['current_attempt'] =getval($line);}
	if (strpos($line, 'max_attempts')     		!== false)      {$obj['max_attempts'] =getval($line);}
    }
    return $obj;
}

while (!feof($file)) {
    $line = fgets($file);
    if (strpos($line, 'hoststatus') 	!== false) array_push($hosts,   readobj());
    if (strpos($line, 'servicestatus') 	!== false) array_push($services,readobj());
}

$i=1;

foreach ($hosts as $host) {
    if (($host['ack'])||($host['downtime'])) continue;
    $print=$host['state'];
    $hservices = array();
    foreach ($services as $service) {
      if ($service['host'] == $host['host'])
	if (($service['state'])&&(!$service['ack'])&&(!$service['downtime'])&&($service['current_attempt']==$service['max_attempts'])) {
	    $print = 1;
	    array_push($hservices,$service);
	}
    }

    if ($print) {
	foreach ($hservices as $hservice) {

	    echo "<tr class='".$states[$hservice['state']]."' nowrap><td>".$i."</td><td nowrap>".date('d-m-Y H:i:s',$host['lastcheck'])."<br>(".duration($hservice['lasthsch']).")</td><td nowrap>".$host['host']."</td><td nowrap>".$hservice['service']."</td><td>".$hservice['plugin']."</td></tr>";
	    $i++;
	}
    }

}
fclose($file);
echo "</table>";
?>