<?php

$filename = "ecodes_data_history_2.csv";
$what = array ();

echo "Loading file<br> ";
if (($handle = fopen ( $filename, "r" )) !== FALSE) {
	
	$data = array ();
	
	while ( ($d = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {
		$data [] = $d;
	}
	fclose ( $handle );

}
$firstline = array_shift ( $data );

$duplicates = array ();

$linenumber = 2;

foreach ( $data as $line ) {
	array_unshift($line,$linenumber);
	
	//$line ['linenumber'] = $linenumber;
	$linenumber ++;
	
	//$duplicates [$line [4]] [] = $line;
	//print_r($line);
	if($line [21] != "NULL")
	$duplicates [$line [21]] [] = $line;

}

$fp = fopen('duplicatefile.csv', 'w');

array_unshift($firstline,"linenumber");

fputcsv($fp, $firstline);



echo implode(",",$firstline)."<br>\n";

foreach ( $duplicates as $key => $dup ) {
	if (count ( $dup ) > 1) {
		//echo "<pre>";
		
		//print_r ( $dup );
		//echo "</pre>";
		echo "--------------------------------------------------------------------------------------------<br>";
		
		foreach($dup as $single)
		{
			echo "single - '$single'<br>";
			fputcsv($fp, $single);

			//echo "<pre>";
			//print_r($single);
			//echo "</pre>";
			//echo implode(",",$single)."<br>\n";
		}
		$dummy = array();
		fputcsv($fp,$dummy);
		fputcsv($fp,$dummy);
		fputcsv($fp,$dummy);
		
		
		echo "--------------------------------------------------------------------------------------------<br>";
		echo "--------------------------------------------------------------------------------------------<br>";
		echo "--------------------------------------------------------------------------------------------<br>";
		echo "--------------------------------------------------------------------------------------------<br>";
		echo "--------------------------------------------------------------------------------------------<br>";
		echo "--------------------------------------------------------------------------------------------<br>";
		
	}
	
	

}
fclose($fp);



exit;

echo "<br>total " . count ( $what ) . "<br>";
foreach ( $what as $k=>$who ) {
	if(count($who) ==1)
	{
	echo "total for $k ".count($who)."<br>\n";
	echo "<pre>";
	
	print_r ( $who );
	echo "</pre>";
	
	}
	
}
function processLines($dup) {
	global $what;
	foreach ( $dup as $single ) {
		$what [$single [4]] [$single [16]] [] = $single;
	}
}