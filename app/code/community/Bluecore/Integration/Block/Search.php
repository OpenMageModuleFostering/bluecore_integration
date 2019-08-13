<?php
class Bluecore_Integration_Block_Search extends Bluecore_Integration_Block_Common_Abstract
{
    private function getSearchText()
    {
        return Mage::helper('catalogsearch')->getQueryText();
    }

    public function eventData()
    {
        $data = array(
            "searchTerm" => $this->getSearchText()
        );

        return $this->encodeEventData($data);
    }
}
