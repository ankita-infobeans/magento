<?php

class ICC_Ecodes_Block_Adminhtml_Downloadable_Assign_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Add fieldset
     *
     * @return \Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $helper = Mage::helper('ecodes');
        $serialIds = $this->getRequest()->getParam('serial_ids');
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/assignPost'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array('legend' => $helper->__('Assign eCodes to a Order below.'))
        );

        foreach($serialIds as $iterator => $serialId)
        {
            $fieldset->addField("serial_ids[$iterator]", 'hidden', array(
                'name'  => "serial_ids[$iterator]",
                'value' => $serialId
            ));
        }

        $fieldset->addField('serials', 'note', array(
            'label' => $this->__('Serial Number'),
            'title' => $this->__('Serial Number'),
            'text'  => implode(',', $serialIds)
        ));

        $fieldset->addField('order_increment_id', 'text', array(
            'name'     => 'order_increment_id',
            'title'    => $helper->__('Order Increment Id'),
            'label'    => $helper->__('Order Increment Id'),
            'required' => true,
        ));

        $fieldset->addField('assign_to_queue', 'checkbox', array(
            'name'  => 'assign_remaining_to_queue',
            'label' => $this->__('Assign remaining to Queue'),
            'note' => $this->__(
                'This will set a Queue item to automatically try to assign new, unused eCode serials if some of these are used upon submission of this tool.'
            )
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}