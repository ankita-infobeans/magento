<?php

class ICC_Couponsystem_Model_Coupon extends Mage_Core_Model_Abstract
{
    
    public function checkExpiryDate()
    {
        Mage::log('checkExpiry',null,'checkExpiry.log');
        $todayDate = date("Y-m-d");
        $collection = Mage::getModel('salesrule/rule')->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('to_date', array('lt' => $todayDate))
	    ->load();
        $collection->getSelect();
        foreach($collection as $model) {
	     $model->setIsActive(0);
             $model->save();
             Mage::log("RuleID: ".$model->getRuleId()."'s status has been changed <br />", null, 'checkExpiry.log');
        }	
    }
    
    public function notificationEmail()
    {
        $todayDate = date("Y-m-d");
        $today = time();
        $threeMonthsLater = strtotime("+3 months", $today);
        $threeMonthsLaterDate = date("Y-m-d", $threeMonthsLater);
        $ruleCollection = Mage::getModel('salesrule/rule')->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('to_date', array(
                'from' => $todayDate,
                'to' => $threeMonthsLaterDate,
                'date' => true));
         $ruleCollection->getSelect()->columns(
                array(
                    'coupon_count' => new Zend_Db_Expr("(SELECT count(coupon_id) FROM salesrule_coupon WHERE rule_id = main_table.rule_id and (usage_limit IS NULL OR usage_limit > times_used) )"
                )));
        $ruleName = array();
        $i = 1; 
        $rule_name = '<table style="border:1px solid black"><tbody><tr><td><b>Rule Name</b></td><td><b>Available Coupon Count</b></td> </tr>';
        if (count($ruleCollection) > 0) :
           foreach($ruleCollection as $collection):
               $ruleName[] = $collection->getName();
               $rule_name .= '<tr><td>'.$collection->getName().'</td><td style="text-align:center">'.$collection->getCouponCount().'</td></tr>';
               $i++;
            endforeach;
        else:
            $rule_name .= '<tr><td>-</td><td style="text-align:center">-</td></tr>';
        endif;
        $rule_name .= '</tbody></table>';
        $template_id = 'shopping_cart_rule_notify';
        //$rule_name  = implode(',', $ruleName);
        $email_template  = Mage::getModel('core/email_template')->loadDefault($template_id);
        $email_template_variables = array(
            'rule_name' => $rule_name,
            'date' => date("F j, Y, g:i a"),
        );
        $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
        $reciever_email = Mage::getStoreConfig('icc_couponsystem/shoppingcartexpirenotification/shopping_cart_expire_notification');
        $email_template->setSenderName($sender_name);
        $email_template->setSenderEmail($sender_email);
        
        $var = $email_template->send($reciever_email,'Admin', $email_template_variables);
    }
}
	 
