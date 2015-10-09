<?php


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


$res = mysql_query($q,$conn);
print_r($res);
