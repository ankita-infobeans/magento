<?php 
class Aoe_Scheduler_Model_Emailmode
{
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>'Production'),
            array('value'=>2, 'label'=>'Development')                    
        );
    }
}
