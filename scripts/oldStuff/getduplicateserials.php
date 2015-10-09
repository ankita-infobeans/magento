<?php


$serials = array();

if (($handle = fopen('downloadable_ecodes.csv', "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

		$s = $data[20];
		$serials[$s][] = $data;		
	}
	fclose($handle);
}





$fp = fopen('output.csv', 'w');

foreach($serials as $serial)

{
	if(count($serial)>1)
	{
		echo count($serial);
		echo "\n";
	//	continue;
		foreach($serial as $line)
		{
			print_r( $line);
			fputcsv($fp, $line);
			
		}
		fputcsv($fp, array());
		fputcsv($fp, array());
		echo "\n\n\n\n\n\n";
	}
}