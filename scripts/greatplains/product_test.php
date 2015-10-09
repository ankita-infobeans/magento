<?php
    require_once '../../app/Mage.php';
    umask(0);
    Mage::app('default');

    $sku = '3410S12';
    

    $gpmp = new Gorilla_Greatplains_Model_Product();
//    $gpmp->UpdateProductData();

    $gp = new Gorilla_Greatplains_Model_Soap();
    $data = $gp->getProductBySku($sku);

    print '<pre>';
    print_r($data);
    print '</pre>';

    $products = Mage::getModel('catalog/product')->getCollection()
        ->addFieldToFilter('type_id', array('in' => array('simple', 'virtual', 'downloadable')))
        ->addAttributeToFilter(array(
            array(                                          // last update time is more than 24 hours before we
                'attribute' => 'last_gp_update',            // started running this script
                'to'        => date ("Y-m-d H:i:s", time() ),
                'datetime'  => true
            ),                                              // OR
            array(                                          // last update isn't set for this product
                'attribute' => 'last_gp_update',
                'null'      => true
            ),
            array(                                          // OR
                'attribute' => 'last_gp_update',            // last update is set, but empty
                'eq'        => ''
            )
        ),
        null,
        'left')                                             // make sure to left join.
        ->addAttributeToSelect("gp_sku")
        ->setPage(1, 5000);

    $products = $products->addAttributeToFilter('gp_sku', $sku)
                            ->addAttributeToSelect('price')
                            ->addAttributeToSelect('visibility')
                            ->addTierPriceData()
                            ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);
    
    foreach ($products as $product) 
    {
        print 'start inventory<br/>';
        print $product->getSku();

        
        $stockData = array();

        $stockData['qty'] = $data->Inventory;
        //$stockData['qty'] = 274;
        //$stockData['is_in_stock'] = 1;
        
        // Set is_in_stock properly based on qty and (if necessary) backorderable
        // Note: if qty <= 0 and it is backorderable, we leave is_in_stock as is.
      if ($data->Inventory > 0) 
        {
            $stockData['is_in_stock'] = 1;
        } 
        else 
        {
            $isBackorderable = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getBackorders();
            if ($isBackorderable == "0") 
            {
                $stockData['is_in_stock'] = 0;
            }
        }

        //$product->setStockItem($stockData);
        //$product->fromArray(array('stock_item' => $stockData));
        
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $product->setStockItem($stockItem);
        $product->setStockData($stockData);
        
        try 
        {
            print 'start saving<br/>';
            $product->save();
            print 'end saving<br/>';
        } 
        catch (Exception $e) 
        {
            print 'ERROR: '.$e->getMessage();
        }
        
        print '<pre>';
        //print_r($product->getStockItem());
        print '</pre>';
        print 'end inventory<br/>';
    }
?>

