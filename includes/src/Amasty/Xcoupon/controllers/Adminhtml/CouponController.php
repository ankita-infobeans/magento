<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */
class Amasty_Xcoupon_Adminhtml_CouponController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction() 
	{
	    $html = $this->getLayout()->createBlock('amxcoupon/adminhtml_coupons')->toHtml();
        $this->getResponse()->setBody($html);
	}

    public function exportCsvAction()
    {
        $content    = $this->getLayout()->createBlock('amxcoupon/adminhtml_coupons')
            ->getCsvFile();
        $this->_prepareDownloadResponse('coupons.csv', $content);  
    }
   
    public function exportXmlAction()
    {
        $content    = $this->getLayout()->createBlock('amxcoupon/adminhtml_coupons')
            ->getExcelFile();
        $this->_prepareDownloadResponse('coupons.xml', $content);  
    }
    
    public function exportCouponCsvAction() {
        $content = $this->getLayout()->createBlock('amxcoupon/adminhtml_couponsSystem')
            ->getCsvFile();

	$this->_prepareDownloadResponse('couponsystem.csv', $content);  
    }
    
    public function exportCouponXmlAction() {
        $content = $this->getLayout()->createBlock('amxcoupon/adminhtml_couponsSystem')
            ->getExcelFile();
        $this->_prepareDownloadResponse('couponsystem.xml', $content);  
    }
/*

 public function exportCsvAction()
        {
            $fileName   = 'subscription.csv';
            $content    = $this->getLayout()->createBlock('subscription/adminhtml_subscription_grid')
                ->getCsv();

            $this->_sendUploadResponse($fileName, $content);
        }

        public function exportXmlAction()
        {
            $fileName   = 'subscription.xml';
            $content    = $this->getLayout()->createBlock('subscription/adminhtml_subscription_grid')
                ->getXml();

            $this->_sendUploadResponse($fileName, $content);
        }
*/
        protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
        {
            $response = $this->getResponse();
            $response->setHeader('HTTP/1.1 200 OK','');
            $response->setHeader('Pragma', 'no-cache');
            $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
            $response->setHeader('Content-Length', strlen($content));
            $response->setHeader('Content-Type', 'text/csv');
	    $response->setHeader('Content-Transfer-Encoding', 'UTF-8');
            $response->setBody($content);
            $response->sendResponse();
            die;
        }

    
    public function editAction() 
    {
		$id     = (int) $this->getRequest()->getParam('id');
		$model  = Mage::getModel('salesrule/coupon')->load($id);

		if (!$model->getId()) {
    		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amxcoupon')->__('Coupon #%d does not exist', $id));
		    $this->_redirect('adminhtml/promo_quote/index');
			return;
		}
		
		$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}
		
		Mage::register('amxcoupon_coupon', $model);

		$this->loadLayout();
		
		$this->_setActiveMenu('promo/quote');
        $this->_addContent($this->getLayout()->createBlock('amxcoupon/adminhtml_coupon_edit'));
        
		$this->renderLayout();
	}  

	public function saveAction() 
	{
	    $id     = $this->getRequest()->getParam('id');
	    $model  = Mage::getModel('salesrule/coupon')->load($id);
	               
	    $data = $this->getRequest()->getPost();
		if (!$data) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('amxcoupon')->__('Unable to find a coupon to save'));
            $this->_redirect('adminhtml/promo_quote/index');
            return;
		}
		
		try {
		    $ruleId = $model->getRuleId();
			
		    $model->setData($data)->setId($id);
			$model->save();
			
			Mage::getSingleton('adminhtml/session')->setFormData(false);
			
			$msg = Mage::helper('amxcoupon')->__('Coupon has been successfully saved');
            Mage::getSingleton('adminhtml/session')->addSuccess($msg);

            $this->_redirect('adminhtml/promo_quote/edit', array('id'=> $ruleId, 'tab'=>'coupons'));
			
        } 
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            $this->_redirect('*/*/edit', array('id' => $id));
        }	
	}

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amxcoupon')->__('Unable to find a coupon to delete'));
            $this->_redirect('adminhtml/promo_quote/index');
            return;
        }
        
        try {
            $coupon = Mage::getModel('salesrule/coupon')->load($id);
            $code = $coupon->getCode();
            $ruleId = $coupon->getRuleId();
            
            $coupon->delete();
            
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('amxcoupon')->__('Coupon "%s" has been deleted', $code));
            $this->_redirect('adminhtml/promo_quote/edit', array('id'=>$ruleId, 'tab'=>'coupons'));
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/promo_quote/index');
        }
    } 	
	
}
