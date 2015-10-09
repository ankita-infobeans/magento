<?php
$data = file("errors.txt");


foreach($data as $line)
{
	$a = explode(":",$line);
	echo $a[4];
	
}