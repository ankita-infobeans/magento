<?php

$a = array(
		'1034786-34001592',
		'1001287-22235227',
		'1008697-73515160',
		'1001421-85879887',
		'1001476-45970221',
		'1009972-35031310',
		'1013676-67510569',
		'1001456-5502049',
		'1009696-57478904',
		'1001557-16977585',
		'1017742-13201015',

);

$filtered = "historicalOrdersStatusFile.csv";


$input = "downloadable_ecodes.csv";
$outputfile = "new_fixed.csv";

$filterarray = array();

$ia = file($filtered);
foreach($ia as $i)
{
	
	if(strpos($i,'downloadable'))
	{
		$a = explode(',',$i);
		$olda = $a[6];
		$olda = explode(" / ",$olda);
		$olda = str_replace('"',"",$olda[0]);
		//echo $olda."\n";
		$filterarray[] = $olda;
	}
}


//die;


//print_r($filterarray);




$data = file($input);
//print_r($data);

$fh = fopen($outputfile, 'a') or die("can't open file");


foreach($data as $line)
{
	$found =false;
	foreach($filterarray as $needle)
	{
		if(strpos($line,$needle))
		{
			
			$found = true;
		}
	}
	if(!$found)
	{
		echo "DIDNT found $needle in line\n";
		echo $line."\n";
		fwrite($fh, $line);
	}
}