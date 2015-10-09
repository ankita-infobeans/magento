<?php
class ICC_PremiumACCESSBundle_Model_Observer
{
    /**
     * Converts attribute set name of current product to nice name ([a-z0-9_]+).
     * Adds layout handle PRODUCT_ATTRIBUTE_SET_<attribute_set_nicename> after
     * PRODUCT_TYPE_<product_type_id> handle
     *
     * Event: controller_action_layout_load_before
     *
     * @param Varien_Event_Observer $observer
     */
    public function addAttributeSetHandle(Varien_Event_Observer $observer)
    {
        $product = Mage::registry('current_product');
 
        /**
         * Return if it is not product page
         */
        if (!($product instanceof Mage_Catalog_Model_Product)) {
            return;
        }
 
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId());
        /**
         * Convert attribute set name to alphanumeric + underscore string
         */
        $niceName = str_replace('-', '_', $product->formatUrlKey($attributeSet->getAttributeSetName()));
            //Mage::log($niceName,null,'aniltest.log');
            if($niceName=='premiumaccess_bundle'){
                Mage::register('premiumaccess_bundle',true);
               
            }
        /* @var $update Mage_Core_Model_Layout_Update */
        $update = $observer->getEvent()->getLayout()->getUpdate();
        $handles = $update->getHandles(); // Store all handles in a variable
        $update->resetHandles(); // Remove all handles
 
        /**
         * Rearrange layout handles to ensure PRODUCT_<product_id>
         * handle is added last
         */
        foreach ($handles as $handle) {
            $update->addHandle($handle);
            if ($handle == 'PRODUCT_TYPE_' . $product->getTypeId()) {
                $update->addHandle('PRODUCT_ATTRIBUTE_SET_' . $niceName);
            }
        }
    }
    
    public function addCustomOption(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId());
        $niceName = str_replace('-', '_', $product->formatUrlKey($attributeSet->getAttributeSetName()));
        if($niceName=='premiumaccess_bundle' && $product->getTypeId()=='bundle'){
            $bundleOptions = $product->getBundleSelectionsData();
            $abc = [];
            foreach( $bundleOptions as $key=>$selection):
                foreach ($selection as $keys=>$options){
                $sku = Mage::getModel('catalog/product')->load($options['product_id'])->getSku();
                $bundleOptions[$key][$keys]['product_id']=substr(trim($sku), -1);
                if($options['delete']==''){
                    $abc[$key][$keys]=$bundleOptions[$key][$keys]['product_id'];
                }
                }
            endforeach;
            //print_r(count($abc));
            if(count($abc)>1):
            $result = call_user_func_array('array_intersect',$abc);  
            try{
                if(empty($result)):
                    Mage::throwException('There should atleast one common year in all groups.');
                endif;
            } catch (Exception $ex) {
                Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
                Mage::app()->getResponse()->setRedirect('*/*/')->sendResponse();
                exit;
            }
            endif;
            //print_r($result); die;
//            try{
//                $oldOptions = $product->getOptions();
//                
//                if(count($oldOptions)>0):
//                    foreach ($oldOptions as $key => $option){
//                    print_r($option->getData()); die;
//                         $values = $option->getValues();
//                         
//                         foreach($values as $value) {
//                             $v->setTitle('nikhil');
//                             $v->save();
//                         }
//                    }
//               endif;
//                $optionInstance = $product->getOptionInstance();//->unsetOptions();
//                //print_r($optionInstance->getData());
//                if(!empty($result)) {// die("innnn");
//                    if(!$product->getId()):
//                        $optionInstance = $product->getOptionInstance()->unsetOptions();
//                        $product->setHasOptions(true);
//                        $optionInstance->addOption($oldOptions);
//                        //$optionInstance->addOption($oldOptions);
//                        $product->setCanSaveCustomOptions(true);
//                        $optionInstance->setProduct($product);
//                    endif;
//                }
//                else{
//                    //die('out');
//                    Mage::throwException();
//                }
//                
//            }
//            catch(Exception $e){
//                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
//                Mage::app()->getResponse()->setRedirect('*/*/')->sendResponse();
//                exit;
//            }
            
           
        }
       // echo $product->getTypeId();
       //die;
    }
   
    
protected function makeCustomOptions($result){
    $customOption=array();
    foreach($result as $data){
        //echo $data['sku']; die;
        switch($data['sku']){
            case 'A':
                array_push($customOption,array(
                    'title'=> "6 Month",
                    'price'=> 0,
                    'price_type'=> "fixed",
                    'sku'=> "A"));
                    break;
            case 'B':
                array_push($customOption,array(
                    'title'=> "1 Year",
                    'price'=> 0,
                    'price_type'=> "fixed",
                    'sku'=> "B"));
                break;
            case 'C':
                array_push($customOption,array(
                    'title'=> "3 Year",
                    'price'=> 0,
                    'price_type'=> "fixed",
                    'sku'=> "C"));
                break;
            case 'D':
                array_push($customOption,array(
                    'title'=> "5 Year",
                    'price'=> 0,
                    'price_type'=> "fixed",
                    'sku'=> "D"));
                break;
        }
    }
    
   // print_r($customOption);
    //die;
        return $option = array(
            'title' => 'Duration',
            'type' => 'drop_down',//That can be checkbox and more
            'is_require' => 0,//1 if required
            'values'=>$customOption
            /*'values'=>array(//These are the options
                array(

                    'title'             => "30 Days",
                    'price'             => -5,
                    'price_type'        => "fixed",
                    'sku'               => "A"
                ),
                array(

                    'title'             => "365 Days",
                    'price'             => -3,
                    'price_type'        => "fixed",
                    'sku'               => "B"
                ),
                array(

                    'title'             => "730 Days",
                    'price'             => -2,
                    'price_type'        => "fixed",
                    'sku'               => "C"
                )
            )*/

        );
    }
    
     
    
    
}