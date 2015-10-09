<?php

class ICC_Ecodes_Block_Adminhtml_Managepremium extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	/**
	 * Initialize grid container settings
	 *
	 * The child grid block class will be:
	 *
	 * $this->_blockGroup . '/' . $this->_controller . '_grid'
	 * i.e. training_animal/adminhtml_animal_grid
	 */


	public function __construct()
	{
		$sub_id = $this->getRequest()->getParam('id');
		$sub = Mage::getModel('ecodes/premiumsubs')->load($sub_id);
		$customer = Mage::getModel('customer/customer')->load($sub->getCustomerId());

		$this->_blockGroup = 'ecodes';
		$this->_controller = 'adminhtml_managepremium';
		$this->_headerText = $this->__('List Users for ' . $customer->getName() . '\'s ' . $sub->getProductName() . ' Premium Subscription.');
		parent::__construct();

		$this->updateButton('add', 'onclick',  'setLocation(\'' . $this->getUrl('*/managepremium/new', array('subscription_id'=>$sub->getId() )) . '\')' );

		$this->addButton('back', array(
				'label' => 'Return to Edit Customer',
				'onclick' => 'setLocation(\'' . $this->getUrl('*/customer/edit', array('id'=>$sub->getCustomerId() )) . '\')',
		));
	}
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('process_id');
		$this->getMassactionBlock()->setFormFieldName('process');

		$modeOptions = Mage::getModel('index/process')->getModesOptions();

		$this->getMassactionBlock()->addItem('change_mode', array(
				'label'         => Mage::helper('index')->__('Change Index Mode'),
				'url'           => $this->getUrl('*/*/massChangeMode'),
				'additional'    => array(
						'mode'      => array(
								'name'      => 'index_mode',
								'type'      => 'select',
								'class'     => 'required-entry',
								'label'     => Mage::helper('index')->__('Index mode'),
								'values'    => $modeOptions
						)
				)
		));

		$this->getMassactionBlock()->addItem('reindex', array(
				'label'    => Mage::helper('index')->__('Reindex Data'),
				'url'      => $this->getUrl('*/*/massReindex'),
				'selected' => true,
		));

		return $this;
	}
}