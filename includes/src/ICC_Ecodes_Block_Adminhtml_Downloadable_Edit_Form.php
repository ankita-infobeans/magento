<?php

class ICC_Ecodes_Block_Adminhtml_Downloadable_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare the inn form wrapper
     * @return \Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $downloadable = Mage::registry('current_downloadable');

        $form = new Varien_Data_Form( array(
            'id' =>'edit_form',
            'action'=> $this->getUrl('*/*/save',
                array(
                    'id' => $this->getRequest()->getParam('id')
                )),
            'method' => 'post',
            'enctype' => 'multipart/form-data',
        ));

        $fieldset = $form->addFieldset(
            'downloadable_form',
            array(
                'legend' => $this->__(sprintf('Manual Edit of eCode Serial Item %s', $downloadable->getId()))
            )
        );

        $fieldset->addField('id', 'hidden', array('name' => 'id'));

        $fieldset->addField('serial', 'note', array(
            'label' => $this->__('Serial Number'),
            'title' => $this->__('Serial Number'),
            'text'  => $downloadable->getSerial()
        ));

        $fieldset->addField('order_item_id', 'note', array(
            'label' => $this->__('Order Item Id'),
            'title' => $this->__('Order Item ID'),
            'text'  => $downloadable->getOrderItemId() ? $downloadable->getOrderItemId() : $this->__('Unassigned')
        ));

        $fieldset->addField('gp_sku', 'note', array(
            'label' => $this->__('GP SKU'),
            'title' => $this->__('GP SKU'),
            'text'  => $downloadable->getGpSku() //Mage::getModel('catalog/product')->load($downloadable->getProductId())->getSku()
        ));

       $fieldset->addField('product_title', 'text', array(
            'name' => 'product_title',
            'label' => $this->__('Product Title' ),
        ));

       $fieldset->addField('document_id', 'text', array(
            'name' => 'document_id',
            'label' => $this->__('Document ID' ),
        ));


        $fieldset->addField('enabled', 'select', array(
            'name' => 'enabled',
            'label' => $this->__('Enabled'),
            'options' => array(
                2 => $this->__('Please Select'),
                1 => $this->__('Enabled'),
                0 => $this->__('Disabled'),
            ),
        ));

        $fieldset->addField('created_at', 'note', array(
            'label' => $this->__('Created At'),
            'title' => $this->__('Created At'),
            'text'  => $downloadable->getCreatedAt()
        ));

        $fieldset->addField('updated_at', 'note', array(
            'label' => $this->__('Updated At'),
            'title' => $this->__('Updated At'),
            'text'  => $downloadable->getUpdatedAt()
        ));

        if ($downloadable->getId())
        {
            $form->setValues($downloadable->getData());
        }

        $form->setUseContainer(true);
      	$this->setForm($form);

        return parent::_prepareForm();
    }
}