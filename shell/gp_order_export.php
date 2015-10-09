<?php require_once 'abstract.php';

class ICC_Shell_Gporder extends Mage_Shell_Abstract
{


    /**
     * Run script
     *
     */
    public function run()
    {
    echo "begin ...";
      $obj = Mage::getModel('Gorilla_Greatplains_Model_Observer');
      $obj->processOrder();
      echo " end";
    }


}

$shell = new ICC_Shell_Gporder();
$shell->run();
