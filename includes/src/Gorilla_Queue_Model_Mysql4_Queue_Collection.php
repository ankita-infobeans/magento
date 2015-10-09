<?php

class Gorilla_Queue_Model_Mysql4_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('gorilla_queue/queue');
    }
}