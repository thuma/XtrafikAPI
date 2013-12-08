<?php
header('Content-type: application/json; charset=utf-8');

$namelist = 'names.json';
$all = array();
if(is_file($namelist) == FALSE)
	{
	$bokstaver = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','Å','Ä','Ö');
	for($i = 0; $i < count($bokstaver); $i++)
		{
		$newdata = TRUE;
		$page = 0;
		while($newdata)
			{
			$newdata = FALSE;
			$doc = new DOMDocument();
			@$doc->loadHTML(file_get_contents('http://reseplaneraren.x-trafik.se/v2/indexes.aspx?optType=0&selKommun=0&sLetter='.urlencode(utf8_decode($bokstaver[$i])).'&iPage='.$page.'&Language=se&optFrTo=0&TNSource=XTRAFIKEN'));
			$list = $doc->getElementById('add-fetch')->getElementsByTagName('a');
			if($list->length == 0)
			{
				break;
			}
			foreach($list as $node)
				{
				$station['name'] = trim($node->nodeValue);
				$station['cleanname'] = preg_replace('/\s+/', ' ',$station['name']);
				$all[] = $station;
				$newdata = TRUE;
				}
			$page++;
			}
		}
	file_put_contents($namelist,json_encode($all));
	}

// Load all stations into object.
$all = json_decode(file_get_contents($namelist));

$idlist = 'ids.json';
if(is_file($idlist) == FALSE)
	{
	foreach($all as $key => $name)
		{
		$url = 'http://reseplaneraren.x-trafik.se/v2/rpajax.aspx?net=XTRAFIKEN&lang=se&letters='.urlencode(utf8_decode($name->cleanname));
		$data = utf8_encode(file_get_contents($url));
		$stationer = preg_split('/></',$data);
		foreach($stationer as $soksvar)
			{
			$station = preg_split('/###/', $soksvar);
			$stationinfo = preg_split('/\|/', $station[0]);
			if(trim(preg_replace('/\s+/', ' ',$stationinfo[0])) == $name->cleanname)
				{
				$all[$key]->id = $stationinfo[1];
				$all[$key]->type = $stationinfo[2];
				break;
				}
			}
		print_r($all[$key]);
		}
	file_put_contents($idlist,json_encode($all));
	}

$all = json_decode(file_get_contents($idlist));
print json_encode($all);

$idlist = 'coord.json';
foreach($all as $key => $station)
	{
	if(isset($station->position)==FALSE)
		{
		//open connection
		$ch = curl_init('http://reseplaneraren.x-trafik.se/v2/querypage_adv.aspx');

		$fields_string = 'inpPointFr_ajax='.rawurlencode(utf8_decode($station->cleanname)).'%7C'.$station->id.'%7C0'
.'&inpPointTo_ajax='
.'&inpPointInterm_ajax='
.'&selRegionFr=741'
.'&inpPointFr='
.'&optTypeFr=0'
.'&inpPointTo='
.'&optTypeTo=0'
.'&inpPointInterm='
.'&selDirection=0'
.'&inpTime=07%3A03'
.'&inpDate=2013-12-08'
.'&optReturn=0'
.'&selDirection2=0'
.'&inpTime2=11%3A03'
.'&inpDate2=2013-12-08'
.'&trafficmask=1'
.'&trafficmask=2'
.'&trafficmask=4'
.'&selChangeTime=0'
.'&selWalkSpeed=0'
.'&selPriority=0'
.'&cmdAction=pastefrommap'
.'&EU_Spirit=False'
.'&TNSource=XTRAFIKEN'
.'&SupportsScript=True'
.'&Language=se'
.'&VerNo=7.1.1.2.0.38p3'
.'&Source=querypage_adv'
.'&MapParams=0%7C0%7Cnull%7C'.rawurlencode(utf8_decode($station->cleanname));

		curl_setopt($ch,CURLOPT_POST, TRUE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		$result = curl_exec($ch);
		curl_close($ch);
		$htmldelar = preg_split('/var aCoordsFr/',$result);
		$htmldelar = preg_split("/'/",$htmldelar[1]);
		$xymix = preg_split("/&|=/",$htmldelar[1]);
		$rt90 = new stdClass();
		$rt90->x = $xymix[6];
		$rt90->y = $xymix[4];
		$coord = new stdClass();
		$coord->rt90 = $rt90;
		$all[$key]->position = $coord;
		print_r($all[$key]);
		file_put_contents($idlist,json_encode($all));
		}
	}

$all = json_decode(file_get_contents($idlist));
print json_encode($all);
?>