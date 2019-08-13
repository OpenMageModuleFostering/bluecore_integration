<?php

class Bluecore_Integration_Model_Observer
{
    public function setAddToCartProductId($observer)
    {
        $product = $observer->getProduct();

        if (!$product->getSku()) {
            return;
        }

        $productIdsAdded = array();

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $groupedProductIds = $observer->getRequest()->getParam('super_group');

            function filterZeroQty($val)
            {
                return $val > 0;
            }
            // Filter to added products only
            $groupedProductIds = array_keys(array_filter($groupedProductIds, "filterZeroQty"));

            // Fetch products by internal ID to retrieve SKU IDs
            $groupedProducts = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToFilter('entity_id', array('in' => $groupedProductIds));
            foreach ($groupedProducts as $product) {
                $productIdsAdded[] = $product->getSku();
            }
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
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
                            $productIdsAdded[] = $selection->getSku();
                        }
                    }
                }
            }
        } else {
            // All other types
            $productIdsAdded[] = $product->getSku();
        }

        // create a session variable named "AddToCartProduct" to store the product ID
        if (!empty($productIdsAdded)) {
            Mage::getModel('core/session')->setAddToCartProducts($productIdsAdded);
        }
    }

    public function setRemoveFromCartProductId()
    {
        $id = (int)Mage::app()->getRequest()->getParam('id');
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        $product = $cart->getItemById($id);

        if (!$product->getSku()) {
            return;
        }

        // create a session variable named "RemoveFromCartProduct" to store the product ID
        Mage::getModel('core/session')->setRemoveFromCartProduct(
            new Varien_Object(array(
                'sku' => $product->getSku()
            ))
        );
    }

    public function setWishlistProductId($observer)
    {
        $item = $observer['item'];
        $product = $item->getProduct();
        $option = $item->getOptionByCode('simple_product');

        if ($option) {
            // Fetch the simple product with configured options if available
            $simpleProductId = $option->getValue();
            $product = Mage::getModel('catalog/product')->load($simpleProductId);
        }

        if (!$product->getSku()) {
            return;
        }

        // create a session variable named "WishlistProduct" to store the product ID
        Mage::getModel('core/session')->setWishlistProduct(
            new Varien_Object(array(
                'sku' => $product->getSku()
            ))
        );
    }

    private function setIdentifyData($email, $source)
    {
        Mage::getModel('core/session')->setIdentifyData(
            new Varien_Object(array(
                'email' => $email,
                'source' => $source
            ))
        );
    }

    public function newSubscriptionIdentify()
    {
        $request = Mage::app()->getRequest();
        $email = $request->getPost('email');

        if ($email) {
            $this->setIdentifyData($email, 'newsletter subscribe');
        }
    }

    // New registrations will trigger this as well
    public function loginIdentify($observer)
    {
        $customer = $observer->getCustomer();

        if ($customer->getEmail()) {
            $this->setIdentifyData($customer->getEmail(), 'login');
        }
    }
}
