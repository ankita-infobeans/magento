<?php

class Gorilla_Queue_Block_Adminhtml_Queue_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Prepare the inn form wrapper
     * @return \Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $queue = Mage::registry('current_queue');

        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/save', array(
                'id' => $this->getRequest()->getParam('id')
            )),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ));

        $fieldset = $form->addFieldset('queue_form', array('legend' => $this->__('Manual Edit of Queue Item')));

        $fieldset->addField('queue_id', 'hidden', array('name' => 'queue_id'));

        $fieldset->addField('status', 'select', array(
            'name'    => 'status',
            'label'   => 'State of Queue Item',
            'options' => Mage::getModel('gorilla_queue/queue')->getStatusesOptions()
        ));

        $fieldset->addField('action', 'select', array(
            'name'    => 'action',
            'label'   => $this->__('Action'),
            'options' => array(
                0 => $this->__('Please Select'),
                1 => $this->__('Process'),
                2 => $this->__('Reset'),
                3 => $this->__('Remove'),
            ),
        ));

        $fieldset->addField('number_attempts', 'text', array(
            'name'  => 'number_attempts',
            'label' => 'Number of attemps made',
        ));

        $fieldset->addField('created_at', 'note', array(
            'label' => $this->__('Created At'),
            'title' => $this->__('Created At'),
            'text'  => $queue->getCreatedAt()
        ));

        $fieldset->addField('last_attempt', 'note', array(
            'label' => $this->__('Last Attempted'),
            'title' => $this->__('Last Attempted'),
            'text'  => $queue->getLastAttempt()
        ));

        $fieldset->addField('short_description', 'textarea', array(
            'name'  => 'short_description',
            'label' => 'Description',
        ));

        $fieldset->addField('queue_item_data', 'textarea', array(
            'name'  => 'queue_item_data',
            'label' => 'Queue Item Data',
            'note'  => '<strong>WARNING: editing this information can cause system instability.</strong>'
        ));
        $fieldset->addField('error_message', 'textarea', array(
            'name'  => 'error_message',
            'label' => 'Error Message',
        ));

        Mage::dispatchEvent("adminhtml_gorilla_queue_prepare_form", array("block" => $this, "form" => $form));
        if ($queue->getQueueId()) {
            $form->setValues($queue->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
