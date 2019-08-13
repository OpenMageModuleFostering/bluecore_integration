<?php
class Bluecore_Integration_Block_Test extends Mage_Core_Block_Template
{


    public function __construct()
    {
        parent::__construct();
        // exit();

        $this->execute();
        $this->renderView();
    }

    public function execute()
    {
        Mage::getModel('core/session')->setAddToCartProduct(
            new Varien_Object(array(
                'sku' => "BIMMEL + DMX"
            ))
        );
    }

    public function getAddToCartProduct()
    {
        $addToCartProduct = Mage::getModel('core/session')->getAddToCartProduct();
        if ($addToCartProduct) {
            Mage::getModel('core/session')->unsAddToCartProduct();
            return $addToCartProduct;
        }
    }

    public function getRemoveFromCartProduct()
    {
        $removeFromCartProduct = Mage::getModel('core/session')->getRemoveFromCartProduct();
        if ($removeFromCartProduct) {
            Mage::getModel('core/session')->unsRemoveFromCartProduct();
            return $removeFromCartProduct;
        }
    }

}
