<?php
ini_set("memory_limit", "2048M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');
$time = time();
$originaltime = $time;

//$addresses = Mage::getModel('customer/address')->getCollection();
$a = Mage::getModel('customer/address');

//echo "total is ".count($addresses)."\n";
$zips = loadZips();

$addresses = Mage::getModel('customer/address')
->getCollection()
->addAttributeToFilter('region_id',array(
		'eq' => 0,
	)
)
->addAttributeToFilter('country_id',array(
		'eq' => 'US',
	)
)
->addAttributeToSort('entity_id');//,"DESC");


echo "Starting at ".date( DATE_RSS,$time)."\n";

echo "total is AFTER ".count($addresses)."\n";
$totalcount = count($addresses);
$count = 0;

foreach ($addresses as $address)
{
	$count++;

	$a->load($address->getEntityId());
	//$a->load('71127');
	//print_r($a->getData());
	//die;
	$postal = $a->getPostcode();
	//echo $postal."\n";
	//print_r($zips);
	$d = getPostal($zips,$postal);
	//print_r($d);
	if($d)
	{

		$regionModel = Mage::getModel('directory/region')->loadByCode($d[3], "US");
		$regionId = $regionModel->getId();

		$a->setRegionId($regionId);
		$a->setRegion( $d[3]);
		$a->setCity($d[2]);
		$a->save();
	}


	$diff = time() - $time;
	$time = time();
	$total = $time-$originaltime;
	echo ($address->getEntityId()." $count of $totalcount Took ".$diff." seconds.");
	echo(" Total So far is ".$total." seconds.\n");
}
"Done! Took a total of ".$time-$originaltime." seconds.\n";

//$zip = "60169";

//print_r(get_zip_info($zip));

function getPostal($zips,$zip)
{
	foreach($zips as $z)
	{
		if($z[0]==$zip)
		{
			return $z;
		}
	}
	return false;
}
function loadZips()
{
	//echo "Loading file ";
	$tmpdata = array();
	if (($handle = fopen('csvs/zipcodes.csv', "r")) !== FALSE) {


		$tempdata = array();

		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$tmpdata[$data[0]] = $data;

		}
		fclose($handle);

	}
	array_shift($tmpdata);
	return $tmpdata;

}

function get_zip_info($zip) {
	//Function to retrieve the contents of a webpage and put it into $pgdata
	$pgdata =""; //initialize $pgdata
	// Open the url based on the user input and put the data into $fd:
	$fd = fopen("http://zipinfo.com/cgi-local/zipsrch.exe?zip=$zip","r");
	while(!feof($fd)) {//while loop to keep reading data into $pgdata till its all gone
		$pgdata .= fread($fd, 1024); //read 1024 bytes at a time
	}
	fclose($fd); //close the connection
	if (preg_match("/is not currently assigned/", $pgdata)) {
		$city = "N/A";
		$state = "N/A";
	} else {
		$citystart = strpos($pgdata, "Code</th></tr><tr><td align=center>");
		$citystart = $citystart + 35;
		$pgdata    = substr($pgdata, $citystart);
		$cityend   = strpos($pgdata, "</font></td><td align=center>");
		$city      = substr($pgdata, 0, $cityend);

		$statestart = strpos($pgdata, "</font></td><td align=center>");
		$statestart = $statestart + 29;
		$pgdata     = substr($pgdata, $statestart);
		$stateend   = strpos($pgdata, "</font></td><td align=center>");
		$state      = substr($pgdata, 0, $stateend);
	}
	$zipinfo['zip']   = $zip;
	$zipinfo['city']  = $city;
	$zipinfo['state'] = $state;
	return $zipinfo;
}