<?php
ini_set("memory_limit", "2048M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');
$time = time();
$originaltime = $time;

//$addresses = Mage::getModel('customer/address')->getCollection();



/*
 *    <host><![CDATA[10.30.2.172]]></host>
<username><![CDATA[icc]]></username>
<password><![CDATA[phuWepOov5]]></password>
<dbname><![CDATA[iccshop]]></dbname>
<initStatements><![CDATA[SET NAMES utf8]]></initStatements>
<model><![CDATA[mysql4]]></model>
<type><![CDATA[pdo_mysql]]></type>
<pdoType><![CDATA[]]></pdoType>
<active>1</active>

*/



$zips = loadZips();

$dbUser = "icc";
$dbPass = "phuWepOov5";
$dbDb = "iccshop";
$dbHost = "10.30.2.172";

//$dbUser = "root";
//$dbPass = "xcyte79";
//$dbDb = "icctest2";
//$dbHost = "127.0.0.1";

$conn = mysql_connect($dbHost, $dbUser, $dbPass);

if (!$conn) {
	echo "Could not connect to server\n";
	trigger_error(mysql_error(), E_USER_ERROR);
	die;
} else {
	echo "Connection established\n";
}


if (!mysql_select_db($dbDb)) {
	echo "Unable to select mydbname: " . mysql_error();
	exit;
}

$q = "SELECT * FROM  `sales_flat_order_address` WHERE region_id IS NULL OR region IS NULL";



$res = mysql_query($q,$conn);
//print_r($res);
//$zips = loadZips();
//print_r($zips);
//die;



foreach($zips as $zip)
{
	//print_r($zip);

	$regionModel = Mage::getModel('directory/region')->loadByCode($zip[3], "US");
	$regionId = $regionModel->getId();
	//$q2 = "UPDATE `sales_flat_order_address` SET `region`='".$d['3']."' AND `region_id` ='".$regionId."'  WHERE `entity_id`='".$row['entity_id']."'";
	$q2 = "UPDATE `sales_flat_order_address` SET `region_id` ='".$regionId."' WHERE `postcode`='".$zip[0]."'";
	echo $q2."\n";

	$res2 = mysql_query($q2,$conn);
	if (!$res2) {
		die('Invalid query: ' . mysql_error());
	}
	
	$q2 = "UPDATE `sales_flat_order_address` SET `region` ='".$zip['3']."'  WHERE `entity_id`='".$zip[0]."'";
	echo $q2."\n";
	$res2 = mysql_query($q2,$conn);
	if (!$res2) {
		die('Invalid query: ' . mysql_error());
	}
	
}


die;


while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {

	$postal =  $row['postcode'];

	$d = getPostal($zips,$postal);
	//print_r($d);

	//continue;


	if($d)
	{
		$regionModel = Mage::getModel('directory/region')->loadByCode($d[3], "US");
		$regionId = $regionModel->getId();
		//$q2 = "UPDATE `sales_flat_order_address` SET `region`='".$d['3']."' AND `region_id` ='".$regionId."'  WHERE `entity_id`='".$row['entity_id']."'";
		$q2 = "UPDATE `sales_flat_order_address` SET `region_id` ='".$regionId."'  WHERE `entity_id`='".$row['entity_id']."'";
		echo $q2."\n";

		$res2 = mysql_query($q2,$conn);
		if (!$res2) {
			die('Invalid query: ' . mysql_error());
		}
		$q2 = "UPDATE `sales_flat_order_address` SET `region` ='".$d['3']."'  WHERE `entity_id`='".$row['entity_id']."'";
		echo $q2."\n";
		$res2 = mysql_query($q2,$conn);
		if (!$res2) {
			die('Invalid query: ' . mysql_error());
		}
	}

}




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


function convert_state($name, $to='name') {
	$states = array(
			array('name'=>'Alabama', 'abbrev'=>'AL'),
			array('name'=>'Alaska', 'abbrev'=>'AK'),
			array('name'=>'Arizona', 'abbrev'=>'AZ'),
			array('name'=>'Arkansas', 'abbrev'=>'AR'),
			array('name'=>'California', 'abbrev'=>'CA'),
			array('name'=>'Colorado', 'abbrev'=>'CO'),
			array('name'=>'Connecticut', 'abbrev'=>'CT'),
			array('name'=>'Delaware', 'abbrev'=>'DE'),
			array('name'=>'Florida', 'abbrev'=>'FL'),
			array('name'=>'Georgia', 'abbrev'=>'GA'),
			array('name'=>'Hawaii', 'abbrev'=>'HI'),
			array('name'=>'Idaho', 'abbrev'=>'ID'),
			array('name'=>'Illinois', 'abbrev'=>'IL'),
			array('name'=>'Indiana', 'abbrev'=>'IN'),
			array('name'=>'Iowa', 'abbrev'=>'IA'),
			array('name'=>'Kansas', 'abbrev'=>'KS'),
			array('name'=>'Kentucky', 'abbrev'=>'KY'),
			array('name'=>'Louisiana', 'abbrev'=>'LA'),
			array('name'=>'Maine', 'abbrev'=>'ME'),
			array('name'=>'Maryland', 'abbrev'=>'MD'),
			array('name'=>'Massachusetts', 'abbrev'=>'MA'),
			array('name'=>'Michigan', 'abbrev'=>'MI'),
			array('name'=>'Minnesota', 'abbrev'=>'MN'),
			array('name'=>'Mississippi', 'abbrev'=>'MS'),
			array('name'=>'Missouri', 'abbrev'=>'MO'),
			array('name'=>'Montana', 'abbrev'=>'MT'),
			array('name'=>'Nebraska', 'abbrev'=>'NE'),
			array('name'=>'Nevada', 'abbrev'=>'NV'),
			array('name'=>'New Hampshire', 'abbrev'=>'NH'),
			array('name'=>'New Jersey', 'abbrev'=>'NJ'),
			array('name'=>'New Mexico', 'abbrev'=>'NM'),
			array('name'=>'New York', 'abbrev'=>'NY'),
			array('name'=>'North Carolina', 'abbrev'=>'NC'),
			array('name'=>'North Dakota', 'abbrev'=>'ND'),
			array('name'=>'Ohio', 'abbrev'=>'OH'),
			array('name'=>'Oklahoma', 'abbrev'=>'OK'),
			array('name'=>'Oregon', 'abbrev'=>'OR'),
			array('name'=>'Pennsylvania', 'abbrev'=>'PA'),
			array('name'=>'Rhode Island', 'abbrev'=>'RI'),
			array('name'=>'South Carolina', 'abbrev'=>'SC'),
			array('name'=>'South Dakota', 'abbrev'=>'SD'),
			array('name'=>'Tennessee', 'abbrev'=>'TN'),
			array('name'=>'Texas', 'abbrev'=>'TX'),
			array('name'=>'Utah', 'abbrev'=>'UT'),
			array('name'=>'Vermont', 'abbrev'=>'VT'),
			array('name'=>'Virginia', 'abbrev'=>'VA'),
			array('name'=>'Washington', 'abbrev'=>'WA'),
			array('name'=>'West Virginia', 'abbrev'=>'WV'),
			array('name'=>'Wisconsin', 'abbrev'=>'WI'),
			array('name'=>'Wyoming', 'abbrev'=>'WY')
	);

	$return = false;
	foreach ($states as $state) {
		if ($to == 'name') {
			if (strtolower($state['abbrev']) == strtolower($name)){
				$return = $state['name'];
				break;
			}
		} else if ($to == 'abbrev') {
			if (strtolower($state['name']) == strtolower($name)){
				$return = strtoupper($state['abbrev']);
				break;
			}
		}
	}
	return $return;
}
