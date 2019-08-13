<?php
class Bluecore_Integration_Block_Head extends Bluecore_Integration_Block_Common_Abstract
{
    public function getIdentifyData()
    {
        $identifyData = Mage::getModel('core/session')->getIdentifyData();
        if ($identifyData) {
            Mage::getModel('core/session')->unsIdentifyData();
            return $identifyData;
        }
    }
}
