<?php
class Bluecore_Integration_Model_System_Environment
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'0', 'label'=>Mage::helper('bluecore')->__('Development')),
            // Staging = 1 (Unused)
            array('value'=>'2', 'label'=>Mage::helper('bluecore')->__('Production')),
        );
    }
}
