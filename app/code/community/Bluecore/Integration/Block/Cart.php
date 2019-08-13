<?php
class Bluecore_Integration_Block_Cart extends Bluecore_Integration_Block_Common_Abstract
{

    // view cart
    private function getCart()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function getCartProductIds()
    {
        $products = array();
        $cart = $this->getCart();

        if ($cart) {
            // getAllVisibleItems filters out nonvisible configurable products
            foreach ($cart->getAllVisibleItems() as $cartItem) {
                $product = $cartItem->getProduct();

                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    // add included products for bundled product types
                    if ($product->hasCustomOptions()) {
                        $customOption = $product->getCustomOption('bundle_option_ids');
                        $optionIds = unserialize($customOption->getValue());
                        $options = $product->getTypeInstance(true)->getOptionsByIds($optionIds, $product);
                        $customOption = $product->getCustomOption('bundle_selection_ids');
                        $selectionIds = unserialize($customOption->getValue());
                        $selections = $product->getTypeInstance(true)->getSelectionsByIds($selectionIds, $product);
                        foreach ($selections->getItems() as $selection) {
                            if ($selection->isSalable()) {
                                $optionId = 'selection_qty_' . $selection->getSelectionId();
                                $selectionQty = $product->getCustomOption($optionId);
                                if ($selectionQty) {
                                    $products[] = array(
                                        id => $selection->getSku()
                                    );
                                }
                            }
                        }
                    }
                } else {
                    // all other product types
                    $products[] = array(
                        id => $product->getSku()
                    );
                }
            }
        }

        return $products;
    }

    public function getAddToCartProducts()
    {
        $addToCartProducts = Mage::getModel('core/session')->getAddToCartProducts();
        if ($addToCartProducts) {
            Mage::getModel('core/session')->unsAddToCartProducts();
            return $addToCartProducts;
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

    public function getAddToCartEventData($productId)
    {
        $data = array(
            "id" => $productId
        );

        return $this->encodeEventData($data);
    }

    public function getRemoveFromCartEventData($product)
    {
        $data = array(
            "id" => $product->getSku()
        );

        return $this->encodeEventData($data);
    }

    public function getViewCartEventData($productIds)
    {
        $data = array(
            "productIds" => $productIds
        );

        return $this->encodeEventData($data);
    }
}
