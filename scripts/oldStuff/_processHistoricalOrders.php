<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');

$customer = new Local_Customer();

$email = "rsuess@gorillagroup.com";

//$ob = $customer->loadCustomerByEmail($email);

//print_r($ob->getFirstItem()->debug());

//exit;

$process = new processHistoricalOrders();

$process -> run();

class processHistoricalOrders {

    private $data;
    private $orderArray;
    private $notfound = array();
    private $defaultemail = "historicalOrders@iccsafe.org";

    private $storeid;
    public $filename = "downloadable_ecodes.csv";

    function __construct() {
        $this -> gp = new Gorilla_Greatplains_Model_Soap();

        Mage::app();

    }

    function run() {

        // echo "start\n";
        $this -> loadFile();
        $this -> processLinesToOrderArray();

        foreach ($this->orderArray as $email => $customerOrders) {

            //echo($customerOrders['email']."\n");
            //continue;

            $customer = $this -> getCustomer($customerOrders);
            //$address = $customer -> checkAddress($customerOrders);

            $num = 0;
            foreach ($customerOrders['orders'] as $data) {
                //  echo "creating order\n";
                //if ($num < 2)
                //    print_r($data);
                //echo "---------------------\n";
                //sleep(1);

                $this -> createOrder($data, $customer);

                $num++;
            }
            unset($customer);

        }
    }

    function createOrder($data, $_customer) {
        echo "creating order\n";


try{
    

        $time = strtotime($data['order_datetime']);

        $transaction = Mage::getModel('core/resource_transaction');

        $storeId = $_customer -> getStoreId();
        $reservedOrderId = Mage::getSingleton('eav/config') -> getEntityType('order') -> fetchNewIncrementId($storeId);
        $order = Mage::getModel('sales/order') -> setIncrementId($reservedOrderId) -> setStoreId($storeId) -> setQuoteId(0);

        $order -> setCustomer_email($_customer -> getEmail()) -> setCustomerFirstname($_customer -> getFirstname()) -> setCustomerLastname($_customer -> getLastname()) -> setCustomerGroupId($_customer -> getGroupId()) -> setCustomer_is_guest(0) -> setCustomer($_customer);

        $_custom_address = array();

        
        $customer_address = $_customer->checkAddress($data);
        $regionModel = Mage::getModel('directory/region') -> loadByCode($data['bill_state'], $data['bill_country']);
        $regionId = $regionModel -> getId();

//echo "dfault billing address\n";
//print_r($customer_address);
//die;

try{
        
        $customAddress = Mage::getModel('sales/order_address');
        $customAddress -> setData($customer_address->getData()) 
        -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING) 
        -> setCustomerId($_customer -> getId()) 
        -> setIsDefaultBilling('1') 
        -> setIsDefaultShipping('1') 
        -> setSaveInAddressBook('1');

        $order -> setBillingAddress($customAddress);

        $customAddress = Mage::getModel('sales/order_address');
        $customAddress -> setData($customer_address->getData()) 
        -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) 
        -> setCustomerId($_customer -> getId()) 
        -> setIsDefaultBilling('1') 
        -> setIsDefaultShipping('1') 
        -> setSaveInAddressBook('1');
        $order -> setShippingAddress($customAddress);
        }catch(Exception $e)
        {
            echo "-------------\n";
        }    


        
        //you can set your payment method name here as per your need
        $orderPayment = Mage::getModel('sales/order_payment') -> setStoreId($storeId) -> setCustomerPaymentId(0) -> setMethod('checkmo');

        $order -> setPayment($orderPayment);

        $subTotal = 0;

        foreach ($data['products'] as $_product) {


            $product = Mage::getModel('catalog/product');

            $this -> log("Added Product ", $_product['eCodes_ID']);
            $product -> load($product -> getIdBySku($_product['eCodes_ID']));

            if (!$product) {
                echo "cannot find product " . $_product['eCodes_ID'] . "\n";
                // die ;
            }

            $qty = $_product['product_qty'];

            if ($_product['product_qty'] == 0 || $_product['product_qty'] == 'n/a') {
                $qty = 1;
            }

            $links = Mage::getModel('downloadable/product_type') -> getLinks($product);

            
            $price = $_product['line_item_total'] / $qty;

            $rowTotal = $_product['line_item_total'];

            $price = $_product['line_item_total'] / $qty;

            $options = array('links' => array($linkId));

            $orderItem = Mage::getModel('sales/order_item') -> setStoreId($storeId) -> setQuoteItemId(0) -> setQuoteParentItemId(NULL) -> setProductId($product -> getId()) -> setProductType($product -> getTypeId()) -> setQtyBackordered(NULL) -> setTotalQtyOrdered($qty) -> setQtyOrdered($qty) -> setName($product -> getName()) -> setSku($product -> getSku()) -> setPrice($price) -> setBasePrice($price) -> setOriginalPrice($price) -> setRowTotal($rowTotal) -> setBaseRowTotal($rowTotal) -> setProductOptions(array($options));


            $subTotal += $rowTotal;
            $order -> addItem($orderItem);

        }

        $order -> setSubtotal($subTotal) -> setBaseSubtotal($subTotal) -> 
        setGrandTotal($subTotal) -> setBaseGrandTotal($subTotal);

$order->save();
        $transaction -> addObject($order);

        $transaction -> addCommitCallback(array($order, 'place'));

        $transaction -> addCommitCallback(array($order, 'save'));


        
        $order -> load();
        // echo $order -> getIncrementId();



        $items = $order -> getAllItems();

       // $this -> log("Processing products after save");
       // echo "Processing products after save\n";
       // echo "14\n";
        
        
        
        foreach ($data['products' ] as $_product) {
      
echo "matching ".$_product['eCodes_ID']." to ".$_product['download_serial_number']."\n";
            
            foreach ($items as $item) {

echo "checking\n";
                   if($this->alreadyHasSerial($item,$_product['download_serial_number']))
                   {
                       continue;
                   }
                if ($_product['eCodes_ID'] == $item -> getSku()) {

                    
                    $product = Mage::getModel('catalog/product');
                    $product -> load($product -> getIdBySku($_product['eCodes_ID']));


                    $qty = $_product['product_qty'];
                    if ($_product['product_qty'] == 0 || $_product['product_qty'] == 'n/a') {
                        $qty = 1;
                    }

                

                   $this -> processDownloadable($_product, $item -> getId(), $product, $time, $item);
    

                    /*
                     * Create Link
                     */

                    $links = $product -> getTypeInstance(true) -> getLinks($product);

                    $linkIds = array();
                    foreach ($links as $link) {
                        $linkIds[] = $link -> getLinkId();
                    }
        // echo "21\n";           
        // print_r($item->getData());
                    $linkPurchased = Mage::getModel('downloadable/link_purchased');

                    Mage::helper('core') -> copyFieldset('downloadable_sales_copy_order', 'to_downloadable', $item -> getOrder(), $linkPurchased);

                    Mage::helper('core') -> copyFieldset('downloadable_sales_copy_order_item', 'to_downloadable', $item, $linkPurchased);
                 
     $linkSectionTitle = ($product -> getLinksTitle() ? $product -> getLinksTitle() : Mage::getStoreConfig(Mage_Downloadable_Model_Link::XML_PATH_LINKS_TITLE));
                

                    $linkPurchased -> setLinkSectionTitle($linkSectionTitle) -> save();
                    
              foreach ($linkIds as $linkId) {
                        if (isset($links[$linkId])) {

                            $linkPurchasedItem = Mage::getModel('downloadable/link_purchased_item') -> setPurchasedId($linkPurchased -> getId()) -> setOrderItemId($item -> getId());
                            Mage::helper('core') -> copyFieldset('downloadable_sales_copy_link', 'to_purchased', $links[$linkId], $linkPurchasedItem);
                            $linkHash = strtr(base64_encode(microtime() . $linkPurchased -> getId() . $item -> getId() . $product -> getId()), '+/=', '-_,');
                            $numberOfDownloads = 6;
                            $linkPurchasedItem -> setLinkHash($linkHash) -> setNumberOfDownloadsBought($numberOfDownloads) -> setNumberOfDownloadsUsed($numberOfDownloads-$data['download_remaining_downloads']) -> setStatus(Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_AVAILABLE) -> setCreatedAt($item -> getCreatedAt()) -> setUpdatedAt($item -> getUpdatedAt()) -> save();
                        
                        }
                    }

   
  

                }

            }
        }

        $order -> setOldOrderIdA($data['old_order_id_a']);
        $order -> setOldOrderIdA($data['old_order_id_a']);
        if ($data['old_order_id_b'] != "NULL")
            $order -> setOldOrderIdB($data['old_order_id_b']);

        $date = Mage::getModel('core/date') -> timestamp($time);
        $order -> setCreatedAt($date);

        $this -> log("Saving order");
        
      
  try{
      $order->setStatus('complete');
            $order->addStatusToHistory($order->getStatus(), 'Historicical order completed', false);
         $order -> save(); 
  }catch(Exception $e)
  {
      echo "Error : " .$e->getMessage();
    print_r($data);
    die;
    }

    }catch(Exception $e)
    {
    echo "Final Error ".$e->getMessage()."\n";
  print_r($data);
      die;
        }
        echo "\ndone\n";
        return $this;

    }

    function log($message) {
        // echo $message."\n";
        Mage::Log(date("m-d-y G:i:s :: ", time()) . " : " . $message, $level, 'historical_import.log');
    }

    function processDownloadable($historicalproduct, $id, $magentoproduct, $timestamp, $orderItem) {
        $this -> log("processing key");
        $write = Mage::getSingleton('core/resource') -> getConnection('core_write');
        $query = "INSERT INTO `iccsafe`.`ecodes_downloadable` (`id`, `serial`, `order_item_id`, `enabled`, `updated_at`, `created_at`, `gp_sku`, `document_id`, `product_title`)
VALUES (NULL, '" . $historicalproduct['download_serial_number'] . "', '" . $id . "', '1', CURRENT_TIMESTAMP, '" . date('Y-m-d H:i:s', $timestamp) . "', '" . $magentoproduct -> getGpSku() . "', '" . $magentoproduct -> getSku() . "', '" . mysql_real_escape_string($magentoproduct -> getName()) . "');";
        $write -> query($query);
        $this -> log("Done Processing Key");

    }

    function getCustomer($custdata) {
        // print_r($custdata);

        if ($custdata['email'] == "") {
            $email = $defaultemail;
        } else {
            $email = $custdata['email'];
        }
        echo "\n\n".$email . "\n";
        $customer = new Local_Customer();

        $customer = $customer -> loadCustomerByEmail($email);

        if (!$customer) {
            echo "Creating new customer\n\n\n";
            $customer = new Local_Customer();
            $customer = $customer -> createNewCustomer($custdata);
        }
        echo "got customer\n\n\n";

        // $customer -> checkAddress($custdata);

        return $customer;
    }
    
    
    function alreadyHasSerial($item,$serial)
    {
                    
                    
                    
                    
                        $connection = Mage::getSingleton('core/resource')
    ->getConnection('core_read');
    $select = $connection->select()
    ->from('ecodes_downloadable', array('*')) // select * from tablename or use array('id','name') selected values
    ->where('order_item_id=?',$item->getId())       ;        // where id =1

    $rowsArray = $connection->fetchAll($select); // return all rows
if($rowsArray)
{
    return true;
}

                    
                    
          $connection = Mage::getSingleton('core/resource')
    ->getConnection('core_read');
    $select = $connection->select()
    ->from('ecodes_downloadable', array('*')) // select * from tablename or use array('id','name') selected values
    ->where('serial=?',$serial)       ;        // where id =1

    $rowsArray = $connection->fetchAll($select); // return all rows
if($rowsArray)
{
    return true;
}


                              
                    
                
       
    }

    function isGuest() {
        return false;
    }

    function processBillingAddress($address, $customer) {
        $billingAddress = Mage::getModel('sales/order_address') -> setStoreId($this -> storeId) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING) -> setCustomerId($customer -> getId())
        // ->                                                                                      setCustomerAddressId($address)
        //->setCustomer_address_id($address->getEntityId())
        -> setPrefix($address -> getPrefix()) -> setFirstname($address -> getFirstname()) -> setMiddlename($address -> getMiddlename()) -> setLastname($address -> getLastname()) -> setSuffix($address -> getSuffix()) -> setCompany($address -> getCompany()) -> setStreet($address -> getStreet()) -> setCity($address -> getCity()) -> setCountry_id($address -> getCountryId()) -> setRegion($address -> getRegion()) -> setRegion_id($address -> getRegionId()) -> setPostcode($address -> getPostcode()) -> setTelephone($address -> getTelephone()) -> setFax($address -> getFax());
        return $billingAddress;

    }

    function processShippingAddress($address, $customer) {
        $billingAddress = Mage::getModel('sales/order_address') -> setStoreId($this -> storeId) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) -> setCustomerId($customer -> getId())
        // ->                                                                                      setCustomerAddressId($address)
        //->setCustomer_address_id($address->getEntityId())
        -> setPrefix($address -> getPrefix()) -> setFirstname($address -> getFirstname()) -> setMiddlename($address -> getMiddlename()) -> setLastname($address -> getLastname()) -> setSuffix($address -> getSuffix()) -> setCompany($address -> getCompany()) -> setStreet($address -> getStreet()) -> setCity($address -> getCity()) -> setCountry_id($address -> getCountryId()) -> setRegion($address -> getRegion()) -> setRegion_id($address -> getRegionId()) -> setPostcode($address -> getPostcode()) -> setTelephone($address -> getTelephone()) -> setFax($address -> getFax());
        return $billingAddress;

    }

    function processLinesToOrderArray() {
        $notfound = array();
        $orderArray = array();
        $product = array();
        //Mage::getModel('catalog/product');
        foreach ($this->data as $line) {

            $email = $line[0];
            if ($email == "")
                $email = $this -> defaultemail;

            $first_name = $line[1];
            $last_name = $line[2];
            $coupon_number = $line[3];
            $old_order_id_a = $line[4];
            $old_order_id_b = $line[5];
            $order_datetime = $line[6];
            $status = $line[7];
            $member_nu = $line[8];
            $bill_street = $line[9];
            $bill_city = $line[10];
            $bill_state = $line[11];
            $bill_zip = $line[12];
            $bill_country = $line[13];
            $bill_phone = $line[14];
            $product_name = $line[15];
            $eCodes_ID = $line[16];
            $product_sku = $line[17];
            $line_item_total = $line[18];
            $product_qty = $line[19];
            $download_serial_number = $line[20];
            $download_remaining_downloads = $line[21];
            $subscription_start_date = $line[22];
            $subscription_end_date = $line[23];
            $subscription_num_users = $line[24];
            $subscription_master_user_name = $line[25];
            $subscription_master_password = $line[26];
            $download_subscription = $line[27];

            $product['product_name'] = $product_name;
            $product['eCodes_ID'] = $eCodes_ID;
            $product['product_sku'] = $product_sku;
            $product['line_item_total'] = $line_item_total;
            $product['product_qty'] = $product_qty;
            $product['download_serial_number'] = $download_serial_number;
            $product['download_remaining_downloads'] = $download_remaining_downloads;
            $product['subscription_start_date'] = $subscription_start_date;
            $product['subscription_end_date'] = $subscription_end_date;
            $product['subscription_num_users'] = $subscription_num_users;
            $product['subscription_master_user_name'] = $subscription_master_user_name;
            $product['subscription_master_password'] = $subscription_master_password;
            $product['download_subscription'] = $download_subscription;

            $orderArray[$email]['first_name'] = $first_name;
            $orderArray[$email]['last_name'] = $last_name;
            $orderArray[$email]['email'] = $email;
            $orderArray[$email]['member_nu'] = $member_nu;
            $orderArray[$email]['bill_street'] = $bill_street;
            $orderArray[$email]['bill_city'] = $bill_city;
            $orderArray[$email]['bill_state'] = $bill_state;
            $orderArray[$email]['bill_zip'] = $bill_zip;
            $orderArray[$email]['bill_country'] = $bill_country;
            $orderArray[$email]['bill_phone'] = $bill_phone;

            $orderArray[$email]['orders'][$old_order_id_a]['first_name'] = $first_name;
            $orderArray[$email]['orders'][$old_order_id_a]['last_name'] = $last_name;

            $orderArray[$email]['orders'][$old_order_id_a]['member_nu'] = $member_nu;
            $orderArray[$email]['orders'][$old_order_id_a]['status'] = $status;
            $orderArray[$email]['orders'][$old_order_id_a]['old_order_id_a'] = $old_order_id_a;
            $orderArray[$email]['orders'][$old_order_id_a]['old_order_id_b'] = $old_order_id_b;
            $orderArray[$email]['orders'][$old_order_id_a]['order_datetime'] = $order_datetime;
            $orderArray[$email]['orders'][$old_order_id_a]['status'] = $status;
            $orderArray[$email]['orders'][$old_order_id_a]['member_nu'] = $member_nu;
            $orderArray[$email]['orders'][$old_order_id_a]['bill_street'] = $bill_street;
            $orderArray[$email]['orders'][$old_order_id_a]['bill_city'] = $bill_city;
            $orderArray[$email]['orders'][$old_order_id_a]['bill_state'] = $bill_state;
            $orderArray[$email]['orders'][$old_order_id_a]['bill_zip'] = $bill_zip;
            $orderArray[$email]['orders'][$old_order_id_a]['bill_country'] = $bill_country;
            $orderArray[$email]['orders'][$old_order_id_a]['bill_phone'] = $bill_phone;
            $orderArray[$email]['orders'][$old_order_id_a]['products'][] = $product;
        }

        $this -> orderArray = $orderArray;

        //print_r ($notfound);
    }

    function loadFile() {
        //echo "Loading file ";
        if (($handle = fopen($this -> filename, "r")) !== FALSE) {

            $this -> data = array();

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $this -> data[] = $data;
            }
            fclose($handle);

        }
        array_shift($this -> data);

    }

}

























