<?php
/**
 * @category    Amasty
 * @package     Amasty_Coupon
 */

require_once ('Mage/Adminhtml/controllers/Promo/QuoteController.php');
class Amasty_Coupons_Adminhtml_Promo_QuoteController extends Mage_Adminhtml_Promo_QuoteController
{
    
    /**
     * Promo quote save action
     *
     */
    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            try {
                /** @var $model Mage_SalesRule_Model_Rule */
                $model = Mage::getModel('salesrule/rule');
                Mage::dispatchEvent(
                    'adminhtml_controller_salesrule_prepare_save',
                    array('request' => $this->getRequest()));
                $data = $this->getRequest()->getPost();
                $data = $this->_filterDates($data, array('from_date', 'to_date'));
                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        Mage::throwException(Mage::helper('salesrule')->__('Wrong rule specified.'));
                    }
                }

                $session = Mage::getSingleton('adminhtml/session');

                $validateResult = $model->validateData(new Varien_Object($data));
                if ($validateResult !== true) {
                    foreach($validateResult as $errorMessage) {
                        $session->addError($errorMessage);
                    }
                    $session->setPageData($data);
                    $this->_redirect('*/*/edit', array('id'=>$model->getId()));
                    return;
                }

                if (isset($data['simple_action']) && $data['simple_action'] == 'by_percent'
                && isset($data['discount_amount'])) {
                    $data['discount_amount'] = min(100,$data['discount_amount']);
                }
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                if (isset($data['rule']['actions'])) {
                    $data['actions'] = $data['rule']['actions'];
                }
                unset($data['rule']);
                $model->loadPost($data);

                $usedForCouponSystem = (int)!empty($data['used_for_coupon_system']);
                $model->setUsedForCouponSystem($usedForCouponSystem);
                /* Add error message when add multiple sku for coupon system */
                $sku = '';
                $allSku = array();;
                $allData = $data['conditions'];

                foreach ($allData as $skudata) {
                    if ($skudata['attribute'] == 'sku') {
                        $sku = $skudata['value'];
                        $skuArray = explode(',', $sku);
                        $allSku[] = $skuArray;
                    }
                }
                if (count($allSku) == 0) { 
                    $allData = $data['actions'];
                    foreach ($allData as $skudata) {
                       if ($skudata['attribute'] == 'sku') {
                            $sku = $skudata['value'];
                            $skuArray = explode(',', $sku);
                            $allSku[] = $skuArray;
                        }
                    }
                }
                $count = count($allSku, COUNT_RECURSIVE);
               
                
                $useAutoGeneration = (int)!empty($data['use_auto_generation']);
                $model->setUseAutoGeneration($useAutoGeneration);

                $session->setPageData($model->getData());

                if ($count > 2 && ($usedForCouponSystem == 1)) {
                    $this->_getSession()->addError(
                        Mage::helper('catalogrule')->__('An error occurred while saving the rule data. Please use only one sku for coupon system.'));
                    $id = (int)$this->getRequest()->getParam('rule_id');
                    if (!empty($id)) {
                        $this->_redirect('*/*/edit', array('id' => $id));
                    } else {
                        $this->_redirect('*/*/new');
                    }
                    return;
                }
                $model->save();
                $session->addSuccess(Mage::helper('salesrule')->__('The rule has been saved.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('rule_id');
                if (!empty($id)) {
                    $this->_redirect('*/*/edit', array('id' => $id));
                } else {
                    $this->_redirect('*/*/new');
                }
                return;

            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('catalogrule')->__('An error occurred while saving the rule data. Please review the log and try again.'));
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('rule_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }
}
