<?php require_once 'abstract.php';

class ICC_Shell_Gpsoaptest extends Mage_Shell_Abstract
{

    const RANGE = 1000;
    /**
     * Run script
     *
     */
    public function run()
    {
        // Init GP Soap client
        $api = new Gorilla_Greatplains_Model_Soap();

        // Initialize GP update model as used in hourly cron
        $model = Mage::getSingleton('greatplains/product');
        $model->setGroupIds();
        $model->startTime = date ("Y-m-d H:i:s", time() + ( 60 * 60) ); // Override starttime filter so we get all applicable skus
        $model->processEnabledOnly = true;

        $skus = array();

        $update = (int)$this->getArg('update');
        $min = $this->getArg('step');
        $min = !empty($min) ? (int)$min : 0;
        echo "\nSKU=".$this->getArg('sku');
        if($sku = $this->getArg('sku')) {
            $skus[] = $sku;

        }
        elseif ($attrSet = $this->getArg('attrset')) {

            // Get Skus for attr set
            switch(strtolower($attrSet)) {
                case 'downloadable':
                    $arrAttrSet = array(
                        'attribute_set_id' => 15,
                        'attribute_set_name' => 'Downloadable'
                    );
                    break;
                case 'event':
                    $arrAttrSet = array(
                        'attribute_set_id' => 11,
                        'attribute_set_name' => 'Event'
                    );
                    break;

            }

            if(isset($arrAttrSet) && is_array($arrAttrSet)) {
                $skus = $model->getProductSkusByAttributeSet($arrAttrSet);
            }

        }

        echo "Processing " . count($skus) . PHP_EOL;
        Print_r($skus);
        if(count($skus)) {
           $max = ($min + 1)*self::RANGE;
            if($max > count($skus))
                $max = count($skus);

            //foreach($skus as $sku) {
            for($i = $min*self::RANGE ; $i < $max; $i++){
                echo "Getting Product by SKU: " . PHP_EOL;

                //$data = $api->getProductBySku($sku);
		$data = $api->getProductBySku($skus[$i]);		

                //$data->mySku = $sku;
		$data->mySku = $skus[$i];

                // Dump API response
                print_r($data);

                // update data in Magento.
                if($update) {


                    //echo "\nUpdating SKU $sku\n";
			echo "\nUpdating SKU $sku\n";
                    //$model->_updateProductData($data, $sku);
			$model->_updateProductData($data, $skus[$i]);
                    echo "\nDone.\n";
                }

            }
        }

    }


}

$shell = new ICC_Shell_Gpsoaptest();
$shell->run();
