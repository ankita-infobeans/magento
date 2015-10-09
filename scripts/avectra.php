<?php

require_once '../app/Mage.php';

umask(0);

Mage::app('default');

$email = "klintz@douglas.co.us";

$a = new ICC_Avectra_Model_Account();

$customer = $a->getByEmail($email);

$ak = $customer->getAvectraKey();
#print_r($a->hasBillMemberStatus($ak));

#die;


$account = new ICC_Avectra_Model_Account();

$orgavkeys = $account->getAffiliatedOrgAvKeys($ak);

print_r ($customer->getAvectraKey());
die;

$ui = $account->getAvComm()->getUserInfo($customer->getAvectraKey());


print_r($ui);

die;



$av_key = $account->getAvectraKey();
           

print_r($customer->getData());

$avc = $account->getAvCustomer($av_key);

print_r($avc);
$ui = $a->getAvCustomer($av_key);

print_r($ui);
