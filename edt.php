<?php
	// modification du header HTTP
	header("Accept-Charset: UTF-8");
	header("Content-Type: text/calendar; charset=UTF-8");
	

	// Récupération des données XML via cURL
	$xml_url	= "http://www.enseirb-matmeca.fr/edt/g31804.xml";
	$ch = curl_init($xml_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$xml_data = curl_exec($ch);
	curl_close($ch);

	// conversion des données XML en élément SimpleXML
	$xml_data = new SimpleXMLElement($xml_data);
	
	
	// entête du ICS
	$vcalendar  = "BEGIN:VCALENDAR\n";
	//$vcalendar .= "PRODID:-//Pouet//NONSGML v1.0//EN\n";
	//$vcalendar .= "VERSION:2.0";
	$vcalendar .= "X-WR-CALNAME:SEE3 ENSEIRB\n";
	$vcalendar .= "X-WR-TIMEZONE:Europe/Paris\n";
	
	// parcours des évents
	foreach($xml_data->event as $event) {
		$vevent = "";
	
		//debug
		//echo "<pre>";
		//var_dump($event);
		//echo "</pre><hr />";
		
		// parcours de chaque semaine pour retrouver à quelle semaine appartient l'évenement		
		$week_start_date = null;
		foreach($xml_data->span as $week) {
			$week_tab = json_decode(json_encode($week),true);	
			if(strpos($event->rawweeks, "Y")+1 == $week_tab["@attributes"]["rawix"])
			{
				$week_start_date = $week_tab["@attributes"]["date"];
				break;
				
			}
		}
		if($week_start_date == null) continue;
		
		$year			= preg_replace("#^([0-9]{2})/([0-9]{2})/([0-9]{4})$#", "$3", $week_start_date);
		$month			= preg_replace("#^([0-9]{2})/([0-9]{2})/([0-9]{4})$#", "$2", $week_start_date);
		$day			= preg_replace("#^([0-9]{2})/([0-9]{2})/([0-9]{4})$#", "$1", $week_start_date);
		$time 			= mktime(0, 0, 0, $month, $day, $year) + 24*3600*$event->day;;
		$date 			= date("Ymd",$time);
 		$hstart			= str_replace(":", "", $event->starttime);
		$hend			= str_replace(":", "", $event->endtime);
		$name			= getAllItems($event->resources->module);
		$description	= getAllItems($event->resources->staff);
		$location 		= getAllItems($event->resources->room);
		
		$vevent .= "BEGIN:VEVENT\n";
		$vevent .= "LOCATION:"			.$location.			"\n";
		$vevent .= "SUMMARY:"			.$name.				"\n";
		$vevent .= "DESCRIPTION:"		.$description.		"\n";
		$vevent .= "DTSTART:"			.$date."T".$hstart.	"00\n";
		$vevent .= "DTEND:"				.$date."T".$hend.	"00\n";
		$vevent .= "END:VEVENT\n";
		
		//debug
		//echo preg_replace('/\\n/', '<br />', $vevent) . '<hr />';
		
		$vcalendar .= $vevent;
	}
	
	$vcalendar .= "END:VCALENDAR\n";
	$vcalendar = utf8_decode($vcalendar);
	$vcalendar = utf8_encode($vcalendar);
	echo($vcalendar);
	
	
	
	
	
	function getAllItems($items)
	{		
		$return 		= "";
		
		$items = (is_object($items)) 		? object2array($items)	: $items;
		$items = (isset($items['item']))	? $items['item'] 		: $items;
			
		if(is_string($items)) return $items;
		elseif(count($items) == 1) return $items['a'];
		else
		{
			$i = 0;
			foreach($items as $item) 
			{	
		
				if($i > 0) $return .= " / ";
				//$return .= $item['a']; // delete cette ligne si tout fonctionne...
				$return .= (is_string($item)) ? $item : $item['a'];
				$i++;
			}
		}
		
		return $return;
	}

	function dbg($data)
	{
		echo "<pre>";
		var_dump($data);
		echo "</pre>";
	}
	
	
	function object2array($object)
	{
	    return json_decode(json_encode($object), TRUE); 
	}
	
?>