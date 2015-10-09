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


$q = "UPDATE `ecodes_premium_subs` SET `seats_total`=300 WHERE `sku` LIKE '%Corporate%'";


$q2 = "UPDATE  `customer_address_entity_varchar` SET `value` ='DC' WHERE `value`='WDC'";


mysql_query($q2,$conn);
die;
$q = "SELECT DISTINCT value
FROM
`customer_address_entity_varchar`
WHERE
`entity_type_id`=2
AND
`attribute_id`=28";





$res = mysql_query($q,$conn);
//print_r($res);
//$zips = loadZips();
//print_r($zips);
//die;
while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {

	$state = $row['value'];

	if(strlen($state) > 2)
	{
		echo $state."\n";
		$abb = convert_state($state,'abbrev');
		if($abb)
		{

			$q2 = "UPDATE  `customer_address_entity_varchar` SET `value` ='".$abb."' WHERE `value`='".$state."'";
			echo $q2;
			mysql_query($q2,$conn);
		}

	}


}


$res = mysql_query($q,$conn);
//print_r($res);
//$zips = loadZips();
//print_r($zips);
//die;
while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {

	$state = $row['value'];

	if(strlen($state) > 2)
	{

		$abb = str_replace(".","",$state,$count);
		if($count>0)
		{
			$q2 = "UPDATE  `customer_address_entity_varchar` SET `value` ='".$abb."' WHERE `value`='".$state."'";
			echo $q2;
			mysql_query($q2,$conn);
		}

	}


}




/*
 * Create region_id
*/
/*

$time = time();
$originaltime = $time;


$a = Mage::getModel('customer/address');

$addresses = Mage::getModel('customer/address')->getCollection()->addAttributeToFilter('region_id',0
		//array(
		//'null' => true,
				//)
) ->addAttributeToSort('entity_id',"DESC");

echo "Starting at ".date( DATE_RSS,$time)."\n";

echo "total is AFTER ".count($addresses)."\n";
$totalcount = count($addresses);
$count = 0;

foreach ($addresses as $address)
{
	$count++;

	$a->load($address->getEntityId());
	//print_r($a->getData());

echo $address->getRegion()."\n";
	$regionModel = Mage::getModel('directory/region')->loadByCode($address->getRegion(), "US");
	if($regionModel)
	{
		$regionId = $regionModel->getId();
		echo $address->getRegion()."\n";
		if($regionId != "")
		{
			echo $address->getRegion()."  ".$regionId."  Saving....\n";
			$a->setRegionId($regionId);
			$a->setRegion( $d[3]);
			$a->setCity($d[2]);
			$a->save();
		}
	}

	//$diff = time() - $time;
	//$time = time();
	//$total = $time-$originaltime;
	//echo ($address->getEntityId()." $count of $totalcount Took ".$diff." seconds.");
	//echo(" Total So far is ".$total." seconds.\n");
}




"Done! Took a total of ".$time-$originaltime." seconds.\n";


*/







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
