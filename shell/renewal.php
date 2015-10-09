<?php
require_once 'abstract.php';

class Mage_Shell_Renewal extends Mage_Shell_Abstract
{
    public function run()
    {
        Mage::getModel('ecodes/premiumsubs')->sendRenewalEmails();
    }
}

$shell = new Mage_Shell_Renewal();
$shell->run();
?>
