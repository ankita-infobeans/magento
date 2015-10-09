<?php


require_once '../app/Mage.php';

umask ( 0 );

Mage::app ( 'default' );

$av = new ICC_Avectra_Model_Account();

/*
 * 850739
 *
 * 820110
 */

//
// 8063540
/// 5225406 org


$user = $av->getUserByRecNo(8063540)->WEBWebUserGetByRecno_CustomResult;

print_r($user);

//echo "\n\n------------createNewUser---------------\n\n";


echo "\n\n---------updateUser------------------\n\n";
$aff = $av->updateUser($user->CurrentKey);

print_r($aff->getData());

die;

$aff = $av->getUserOrgCustomerNo($user->CurrentKey);

print_r($aff);
die;
echo "\n\n---------getAffiliatedOrgAvKeys------------------\n\n";


print_r($aff);
die;



echo "\n\n---------getMageCustomer------------------\n\n";

$cust = $av->getMageCustomer();
print_r($cust->getData());


echo "\n\n---------getUserAffiliation------------------\n\n";
$aff = $av->getUserAffiliation($user->CurrentKey);

print_r($aff);

echo "\n\n---------getUserAffiliatedOrganizations------------------\n\n";

//$cust = $av->getUserAffiliatedOrganizations($user->CurrentKey);
//print_r($cust);



echo "\n\n---------getUserOrgCustomerNo------".$user->CurrentKey."------------\n\n";
$aff = $av->getUserOrgCustomerNo($user->CurrentKey);


echo $aff;

die;

