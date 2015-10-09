<?php

//  Mage_Adminhtml_Block_Template Mage_Adminhtml_Block_Widget_Grid_Container    //  extends Mage_Adminhtml_Block_Template
class ICC_Ecodes_Block_Adminhtml_Customer_Edit_Tab_Premium extends Mage_Adminhtml_Block_Widget_Grid_Container implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

	public function __construct()
	{   //ICC_Ecodes_Adminhtml_Premium_GridController
		$this->_blockGroup = 'ecodes';
		$this->_controller = 'adminhtml_customer_edit_tab_premium';
		//$this->_controller = 'adminhtml_premium';
		$this->_headerText = $this->__('PremiumACCESS Subscriptions');
		parent::__construct();

		$this->removeButton('add');
		//$test = new Mage_Adminhtml_Block_Widget_Grid();
		//$test->
	}
	protected function _prepareLayout()
	{
		//die;
		//echo  $this->_blockGroup.'/' . $this->_controller . '_grid';
		//die;
		
		return parent::_prepareLayout();
                $this->setChild( 'grid',
				$this->getLayout()->createBlock( $this->_blockGroup.'/' . $this->_controller . '_grid',
						$this->_controller . '.grid')->setSaveParametersInSession(true) );
	}

	public function getCustomtabInfo()
	{
		$customer = Mage::registry('current_customer');
		$customtab = 'My Custom tab Action Contents Here';
		return $customtab;
	}

	/**
	 * Return Tab label
	 *
	 * @return string
	 */
	public function getTabLabel()
	{
		return $this->__('PremiumACCESS Subscriptions');
	}

	/**
	 * Return Tab title
	 *
	 * @return string
	 */
	public function getTabTitle()
	{
		return $this->__('Work with PremiumACCESS Subscriptions');
	}

	/**
	 * Can show tab in tabs
	 *
	 * @return boolean
	 */
	public function canShowTab()
	{
		$customer = Mage::registry('current_customer');
		return true;   //(bool)$customer->getId();
		return $customer->hasPremiumSubscription();
	}

	/**
	 * Tab is hidden
	 *
	 * @return boolean
	 */
	public function isHidden()
	{
		return false;
	}
        
        
        public function getHeaderHtml(){
            return '';
        }

	/**
	 * Defines after which tab, this tab should be rendered
	 *
	 * @return string
	 */
	public function getAfter()
	{
		return 'tags';
	}

}