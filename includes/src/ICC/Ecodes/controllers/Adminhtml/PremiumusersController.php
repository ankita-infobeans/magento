<?php
class ICC_Ecodes_Adminhtml_PremiumusersController extends Mage_Adminhtml_Controller_action {
	public function indexAction() {
		$this->loadLayout();
		$this->_addContent($this->getLayout()->createBlock('ecodes/adminhtml_premiumusers_gridContainer'));
		$this->renderLayout();
	}

    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
        $this->getLayout()->createBlock('ecodes/adminhtml_premiumusers_grid')->toHtml()
        );
    }

}
