<?php
class Bluecore_Integration_Block_Common_Abstract extends Mage_Core_Block_Template
{
    public function isEnabled()
    {
        return Mage::getStoreConfig('bluecore/account/enabled');
    }

    public function getEnvironment()
    {
        return Mage::getStoreConfig('bluecore/account/environment');
    }

    public function getBluecoreNamespace()
    {
        return strtolower(Mage::getStoreConfig('bluecore/account/namespace'));
    }

    public function getCurrentProduct()
    {
        return Mage::registry('current_product');
    }

    public function getCurrentCategory()
    {
        return Mage::registry('current_category');
    }

    public function encodeEventData($data)
    {
        // $encodeOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK;
        $encodeOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK;
        $data = json_encode($data, $encodeOptions);
        return $data;
    }
}
