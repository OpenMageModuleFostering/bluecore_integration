<?php
class Bluecore_Integration_Block_Wishlist extends Bluecore_Integration_Block_Common_Abstract
{
    public function getWishlistProduct()
    {
        $wishlistProduct = Mage::getModel('core/session')->getWishlistProduct();
        if ($wishlistProduct) {
            Mage::getModel('core/session')->unsWishlistProduct();
            return $wishlistProduct;
        }
    }

    public function eventData($wishlistProduct)
    {
        $data = array(
            "id" => $wishlistProduct->getSku()
        );

        return $this->encodeEventData($data);
    }
}
