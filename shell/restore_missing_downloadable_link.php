<?php


require_once 'abstract.php';

class ICC_Shell_RestoreDownloadableLink extends Mage_Shell_Abstract
{


    /**
     * Run script
     *
     */
    public function run()
    {
        if($this->getArg('item') && ctype_digit($this->getArg('item'))) {
            echo "Loading sales order item " . $this->getArg('item') . '...' . PHP_EOL;
            $item = Mage::getModel('sales/order_item')->load($this->getArg('item'));
            if($item->getId() == $this->getArg('item')) {

                // Check if link already exists.  If yes, just return
                if(Mage::getModel('downloadable/link_purchased')->load($item->getId(), 'order_item_id')->getId()) {
                    echo "Link already exists for this item." . PHP_EOL;
                    return;
                }

                // Save downloadable link for order item
                $param = new Varien_Object( array(
                    'event' => new Varien_Object(array(
                            'item' => $item
                            )
                        )
                    ));

                if($param->getEvent()->getItem()->getId()) {
                    echo "Saving link..." . PHP_EOL;

                    Mage::getModel('downloadable/observer')
                        ->saveDownloadableOrderItem($param);

                    echo "Done. " . PHP_EOL . PHP_EOL;
                    echo "If this operation was unsuccessful, please check the order item id product_options. If the serialized links array is empty, then first fix it and try again. ". PHP_EOL;
                    echo "For example, if the product option has ...:5:\"links\";a:0:{}..., update with the link id such as ...:5:\"links\";a:1:{i:0;s:4:\"7647\";}..." . PHP_EOL;
                    echo "Then re-run this script. " . PHP_EOL;
                }
            }
            else {
                echo "Sales order item not found";
            }
        }
        else {
            echo $this->usageHelp();
        }


    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f restore_downloadable_link.php -- [options]

  item <order_item_id>        Restore missing link for given order item id
  help                        This help

USAGE;
    }


}

$shell = new ICC_Shell_RestoreDownloadableLink();
$shell->run();
