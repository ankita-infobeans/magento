<?php
/**
 * Adminhtml sales order creditmemo controller is overriden for volumelicense and premium access
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     InfoBeans
 */
include_once("Mage/Adminhtml/controllers/Sales/Order/CreditmemoController.php");
class ICC_Premiumaccess_Adminhtml_Sales_Order_CreditmemoController extends Mage_Adminhtml_Sales_Order_CreditmemoController
{ 
    /**
     * Get requested items qtys and return to stock flags
     * Due to checkboxes are used in refund controller, it returns which item id has how many number of quantities set for refund.
     */
    protected function _getItemData()
    {
        $data = $this->getRequest()->getParam('creditmemo');
        //echo "<pre>"; print_r($data); exit;
        if (!$data) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        }
        foreach ($data['items'] as $k=>$v){
            foreach($v as $it=>$qt){
              if(is_array($qt)){
                   $sum = array_sum($qt);
                   if($sum){
                     $data['items'][$k][$it] = array_sum($qt);
                   }else{
                       unset($data['items'][$k][$it]);
                       
                   }
              }
            }
        }
        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }
       // echo "<pre>"; print_r($qtys); exit;
        return $qtys;
    } 
    /**
     * Save creditmemo and related order, invoice in one transaction
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * We have added two custom refund events to save data for volumelicense and premium access after invoice is created. 
     */
    protected function _saveCreditmemo($creditmemo) {
 // echo "<pre>";  print_r($creditmemo->getData());  die;
        $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($creditmemo)
                ->addObject($creditmemo->getOrder());
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();
        Mage::dispatchEvent("sales_order_creditmemo_saved", array("controller_action" => $this));
        Mage::dispatchEvent("sales_order_creditmemo_saved_after", array("controller_action" => $this));
        return $this;
    }

    /**
     * Save creditmemo
     * We can save only new creditmemo. Existing creditmemos are not editable
     * If no item is set to be refund, then it thorows an error. 
     * Previously if no item selected it was refunding all items by clicking on refund button.
     */
    public function saveAction() {
        $data = $this->getRequest()->getPost('creditmemo');
   //     echo "<pre>";print_r($data);die;
        if ($data['items'] == NULL) {
            $this->_getSession()->addError($this->__('No item selected. Cannot save the credit memo.'));
            $this->_redirect('*/*/new', array('_current' => true));
        } else {
            if (!empty($data['comment_text'])) {
                Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
            }

            try {
                $creditmemo = $this->_initCreditmemo();
                  //print_r($creditmemo->getData()); die;
                if ($creditmemo) {
                    if (($creditmemo->getGrandTotal() <= 0) && (!$creditmemo->getAllowZeroGrandTotal())) {
                        Mage::throwException(
                                $this->__('Credit memo\'s total must be positive.')
                        );
                    }

                    $comment = '';
                    if (!empty($data['comment_text'])) {
                        $creditmemo->addComment(
                                $data['comment_text'], isset($data['comment_customer_notify']), isset($data['is_visible_on_front'])
                        );
                        if (isset($data['comment_customer_notify'])) {
                            $comment = $data['comment_text'];
                        }
                    }

                    if (isset($data['do_refund'])) {
                        $creditmemo->setRefundRequested(true);
                    }
                    if (isset($data['do_offline'])) {
                        $creditmemo->setOfflineRequested((bool) (int) $data['do_offline']);
                    }

                    $creditmemo->register();
                      
                    if (!empty($data['send_email'])) {
                        $creditmemo->setEmailSent(true);
                    }

                    $creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                    $this->_saveCreditmemo($creditmemo);
                    $creditmemo->sendEmail(!empty($data['send_email']), $comment);
                    $this->_getSession()->addSuccess($this->__('The credit memo has been created.'));
                    Mage::getSingleton('adminhtml/session')->getCommentText(true);
                    $this->_redirect('*/sales_order/view', array('order_id' => $creditmemo->getOrderId()));
                    return;
                } else {
                    $this->_forward('noRoute');
                    return;
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($this->__('Cannot save the credit memo.'));
            }
            $this->_redirect('*/*/new', array('_current' => true));
        }
    }
    
    
     /**
     * Initialize creditmemo model instance
     *
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    protected function _initCreditmemo($update = false)
    {
        $this->_title($this->__('Sales'))->_title($this->__('Credit Memos'));

        $creditmemo = false;
        $creditmemoId = $this->getRequest()->getParam('creditmemo_id');
        $orderId = $this->getRequest()->getParam('order_id');
        if ($creditmemoId) {
            $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
        } elseif ($orderId) {
            $data   = $this->getRequest()->getParam('creditmemo');
            $order  = Mage::getModel('sales/order')->load($orderId);
            $invoice = $this->_initInvoice($order);

            if (!$this->_canCreditmemo($order)) {
                return false;
            }

            $savedData = $this->_getItemData();

            $qtys = array();
            $backToStock = array();
            foreach ($savedData as $orderItemId =>$itemData) {
                if (isset($itemData['qty'])) {
                    $qtys[$orderItemId] = $itemData['qty'];
                }
                if (isset($itemData['back_to_stock'])) {
                    $backToStock[$orderItemId] = true;
                }
            }
            $data['qtys'] = $qtys;

            $service = Mage::getModel('sales/service_order', $order);
            if ($invoice) {
                $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
            } else {
                $creditmemo = $service->prepareCreditmemo($data);
            }

            /**
             * Process back to stock flags
             */
            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $orderItem = $creditmemoItem->getOrderItem();
                $parentId = $orderItem->getParentItemId();
                if (isset($backToStock[$orderItem->getId()])) {
                    $creditmemoItem->setBackToStock(true);
                } elseif ($orderItem->getParentItem() && isset($backToStock[$parentId]) && $backToStock[$parentId]) {
                    $creditmemoItem->setBackToStock(true);
                } elseif (empty($savedData)) {
                    $creditmemoItem->setBackToStock(Mage::helper('cataloginventory')->isAutoReturnEnabled());
                } else {
                    $creditmemoItem->setBackToStock(false);
                }
            }
        }

        $args = array('creditmemo' => $creditmemo, 'request' => $this->getRequest());
        Mage::dispatchEvent('adminhtml_sales_order_creditmemo_register_before', $args);

        Mage::register('current_creditmemo', $creditmemo);
        return $creditmemo;
    }


}

