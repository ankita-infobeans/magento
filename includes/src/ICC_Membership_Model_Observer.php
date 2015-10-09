<?php
class ICC_Membership_Model_Observer
{

    //core_config_data customer/avectra/login_hook

    protected function _becomeMember()
    {
        Mage::log('becomeMember', null, 'yakoff.log');
        $member_group_id = 2; //membership

        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        $email = $customer->getEmail(); //email
        $group = $customer->getGroupId();

        if ($customer->getGroupId != 2) {

            // logged in?
            if($session->isLoggedIn()) {
                $m = Mage::getSingleton('membership/group'); //model
                $collection = Mage::getModel('membership/group')
                    ->getCollection()
                    ->addFieldToFilter('email', array('eq' => $email));
                // exist in DB?
                $exist = $collection->count();
                if ($exist == 0) {
                    $m->setEmail($email);
                    $m->setOriginalGroupId($group);
                    $m->save();
                }

                $customer->setGroupId($member_group_id)->save();
                Mage::log('groupId = 2', null, 'yakoff.log');
            } else {
                //nothing for a guest
            }

        } // customer Group != 2*/
    }


    public function beforeCartSave($event)
    {
        //Standard on the Design and Construction of Log Structures: ICC 400 - 2007

        $types = array(); //types of product in the cart

        $items = $event->getCart()->getQuote()->getAllItems();   //get all items of quote

        $session = Mage::getSingleton('customer/session');
        $current_group = $session->getCustomer()->getGroupId(); //get customer group ID
        $email = $session->getCustomer()->getEmail(); 			//get customer e-mail

        foreach ($items as $number => $item) {
            $sku = $item->getSku();
            $product_full = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            if (is_object($product_full)) {
                $types[] = $product_full->getItemType();
            }
        } //foreach

        if ($current_group != 2) {
            if (in_array('534', $types)) {
                $this->_becomeMember(); //save original groupID, change group 1->2
            }
        } elseif($current_group == 2) {
            if (!in_array(534, $types)) {
                $collection = Mage::getModel('membership/group')
                    ->getCollection()
                    ->addFieldToFilter('email', array('eq' => $email));
                if ($collection->count()) {
                    foreach ($collection as $n=>$item) {
                        $original = $item->getOriginalGroupId();
                        $session->getCustomer()->setGroupId($original)->save();
                    }
                }
            }
        }
    }


    /*After place order - we should delete a recored for a customer from the membership_group*/
    public function afterPlaceOrder($event)
    {
        $order = $event->getOrder();
        $items = $order->getAllItems();
        foreach ($items as $number => $item) {
            $sku = $item->getSku();
            $product_full = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            if (is_object($product_full)) {
                $types[] = $product_full->getItemType();
            }
        } //foreach

        if (in_array('534', $types)) {
            $session = Mage::getSingleton('customer/session');
            $customer = $session->getCustomer();
            $email = $customer->getEmail();

            $item = Mage::getModel('membership/group')
                ->getCollection()
                ->addFieldToFilter('email', array('eq' => $email))
                ->getFirstItem();

            $id = $item->getId();
            //Mage::log($item->getId(), null, 'yakoff.log');
            if ($id) {
                $m = Mage::getSingleton('membership/group');
                $m->setId($id);
                $m->delete();
            }
        }
    }

    public function afterPredispatch($event)
    {
        if (!Mage::isInstalled()) {
            return ;
        }

        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
        $types = array(); //types of product in the cart


        $session = Mage::getSingleton('customer/session');
        $current_group = $session->getCustomer()->getGroupId(); //get customer group ID
        $email = $session->getCustomer()->getEmail(); 			//get customer e-mail
        if (!empty($email)) {
            foreach ($items as $number => $item) {
                $sku = $item->getSku();
                $product_full = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                if (is_object($product_full)) {
                    $types[] = $product_full->getItemType();
                }
            } //foreach

            if ($current_group != 2) {
                if (in_array('534', $types)) {
                    $this->_becomeMember(); //save original groupID, change group 1->2
                }
            } elseif($current_group == 2) {
                if (!in_array(534, $types)) {
                    $collection = Mage::getModel('membership/group')
                        ->getCollection()
                        ->addFieldToFilter('email', array('eq' => $email));
                    if ($collection->count()) {
                        foreach ($collection as $n=>$item) {
                            $original = $item->getOriginalGroupId();
                            $session->getCustomer()->setGroupId($original)->save();
                        }
                    }
                }
            }

        } // checking of email
    }

    public function afterLogin()
    {
        $this->afterPredispatch(null);
    }
	
	public function groupChangedByAvectra($observer)
    {
		$event = $observer->getEvent();
        //$session = Mage::getSingleton('customer/session');
		$mageCustomer=$event->getMageCustomer();
		//Mage::log('groupChangedByAvectra $mageCustomer > '.print_r($mageCustomer,true), null, 'yakoff-mageCustomer.log');
		//Mage::log('groupChangedByAvectra $mageCustomer > '.print_r($event,true), null, 'yakoff-event.log');
        //$customer = $session->getCustomer();
        $email = $mageCustomer->getEmail();

        $item = Mage::getModel('membership/group')
            ->getCollection()
            ->addFieldToFilter('email', array('eq' => $email))
            ->getFirstItem();

        $id = $item->getId();
        Mage::log("Update from avectra. Delete item #".$item->getId(), null, 'yakoff.log');
        if ($id) {
            $m = Mage::getSingleton('membership/group');
            $m->setId($id);
            $m->delete();
        }
    }

	public function groupChangedByAdmin($event)
    {
		$customer = $event->getCustomer();
	
		Mage::log('Update from admin. GroupId: '.$customer->getGroupId() , null, 'yakoff.log');
		//Mage::log('Update from admin. Customer: '.print_r($customer,true), null, 'yakoff.log');
        $email = $customer->getEmail();
		$group = $customer->getGroupId();

        $item = Mage::getModel('membership/group')
            ->getCollection()
            ->addFieldToFilter('email', array('eq' => $email))
            ->getFirstItem();

        $id = $item->getId();
        Mage::log("Update from admin. Delete item #".$item->getId(), null, 'yakoff.log');
        if ($id && $item->getOriginalGroupId()!=$group) {   // Do not do any changes if group was not changed for edited customer
            $m = Mage::getSingleton('membership/group');
            $m->setId($id);
            $m->delete();
        }
    }
}
