<?php

/**
 *
 * @category   ICC
 * @package    ICC_Queue
 * @subpackage Model
 * @author     Aleksandr Lykhouzov<alykhouzov@gorillagroup.com>
 */
class ICC_Queue_Model_Observer
{

    public function addFields($observer)
    {
        $block = $observer->getBlock();
        if (!$block instanceof Gorilla_Queue_Block_Adminhtml_Queue_Edit_Form) {
            return;
        }
        /* @var $block Gorilla_Queue_Block_Adminhtml_Queue_Edit_Form */

        $fieldset = $observer->getForm()
                ->getElement('queue_form');
        if (!$fieldset) {
            return;
        }

        $fieldset->addField('soap_request', 'textarea', array(
            'name'     => 'soap_request',
            'label'    => 'Raw Request',
//            'readonly' => true
        ));
        $fieldset->addField('soap_response', 'textarea', array(
            'name'     => 'soap_response',
            'label'    => 'Raw Response',
//            'readonly' => true
        ));
    }

}
