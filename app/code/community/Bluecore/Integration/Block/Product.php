<?php
class Bluecore_Integration_Block_Product extends Bluecore_Integration_Block_Common_Abstract
{
    private function getProductType($product)
    {
        return $product->getTypeId();
    }

    private function skuId($product)
    {
        return trim($product->getSku());
    }

    private function name($product)
    {
        return trim($product->getName());
    }

    private function url($product)
    {
        return $product->getProductUrl();
    }

    private function price($product)
    {
        return $product->getFinalPrice();
    }

    private function originalPrice($product)
    {
        $price = $product->getPrice();
        if ($product->getSpecialPrice() < $price) {
            return $price;
        }
    }

    private function image($product)
    {
        return $product->getImageUrl();
    }

    private function outOfStock($product)
    {
        return !($product->getStockItem()->getIsInStock());
    }

    private function inventory($product)
    {
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            return null;
        }
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $qty = $stock->getQty();
        if ($qty > 0) {
            return $qty;
        }
    }

    private function category($product)
    {
        return $product->getCategory();
    }

    private function categoryName($product)
    {
        $category = $this->category($product);
        if ($category) {
            return $category->getName();
        }
    }

    private function categories($product)
    {
        $categoryNames = array();
        $catIds = $product->getCategoryIds();
        foreach ($catIds as $catId) {
            $category = Mage::getModel('catalog/category')->load($catId);
            if ($category) {
                $categoryNames[] = $category->getName();
            }
        }
        return $categoryNames;
    }

    private function breadcrumbs($product)
    {
        $categoryNames = array();
        $category = $this->category($product);
        if ($category) {
            $categories = $category->getParentCategories();
            foreach ($categories as $category) {
                $categoryNames[] = $category->getName();
            }
        }
        return $categoryNames;
    }

    // Search and view events should only surface products where this is true
    private function isVisible($product)
    {
        return $product->isVisibleInCatalog() && $product->isVisibleInSiteVisibility();
    }

    private function associatedProducts($product)
    {
        $products = array();
        $productType = $product->getTypeInstance(true);
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $collection = $productType->getUsedProducts(null, $product);
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $usedOptions = $productType->getOptionsIds($product);
            $collection = $productType->getSelectionsCollection($usedOptions, $product);

            //Mage::getResourceModel('bundle/selection')->getChildrenIds(447, false);

        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $collection = $productType->getAssociatedProductCollection($product);
        }

        if ($collection) {
            foreach ($collection as $associatedProduct) {
                $simpleproduct = Mage::getModel('catalog/product')->load($associatedProduct->getId());
                $data = array(
                    "id" => $this->skuId($simpleproduct),
                    "name" => $this->name($simpleproduct),
                    "price" => $this->price($simpleproduct),
                    // "originalPrice" => $this->originalPrice($simpleproduct),
                    // Use product URL if viewable, otherwise return configurable product URL
                    "url" => $this->isVisible($simpleproduct) ? $this->url($simpleproduct) : $this->url($product),
                    // Use product image, or configurable if undefined
                    "image" => $this->image($simpleproduct) ? $this->image($simpleproduct) : $this->image($product),
                    "outOfStock" => $this->outOfStock($simpleproduct),
                    "inventory" => $this->inventory($simpleproduct),
                    "isVisible" => $this->isVisible($simpleproduct),
                    "type" => $this->getProductType($simpleproduct),
                );

                // add attributes such as size, color, width etc
                $data = $this->addAttribuesToArray($data, $associatedProduct);

                array_push($products, $data);
            }
        }

        return $products;
    }

    private function includeAttribute($product, $attribute)
    {
        $blacklist = array(
            'price',
            'sku',
            'status',
            'name',
            'small_image_label',
            'image_label',
            'thumbnail_label',
            'description', // allow short_description
            'tax_class_id',
            'expected_shipping_date'
        );
        $isBlacklisted = in_array($attribute->getAttributeCode(), $blacklist);
        $isVisibleOnFront = $attribute->getIsVisibleOnFront();
        $isSearchable = $attribute->getData('is_searchable');
        $isVisibleInSearch = $attribute->getData('is_visible_in_advanced_search');
        $isFilterable = $attribute->getData('is_filterable');
        $isFilterableSearch = $attribute->getData('is_filterable_in_search');
        $isIndexed = $isSearchable || $isVisibleInSearch || $isFilterable || $isFilterableSearch;

        return !$isBlacklisted && ($isIndexed || $isVisibleOnFront);
    }

    private function allAttributes($product)
    {
        $attributes = array();
        foreach ($product->getAttributes() as $attribute) {
            if ($this->includeAttribute($product, $attribute)) {
                array_push($attributes, $attribute);
            }
        }
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $cAttrs = $product->getTypeInstance(true)->getUsedProductAttributes($product);
            foreach ($cAttrs as $attribute) {
                if ($this->includeAttribute($product, $attribute)) {
                    array_push($attributes, $attribute);
                }
            }
        }
        return $attributes;
    }

    private function attributeNames($product)
    {
        $attributeNames = array();
        foreach ($this->allAttributes($product) as $attribute) {
            $attrCode = $attribute->getAttributeCode();
            array_push($attributeNames, $attrCode);
        }
        return $attributeNames;
    }

    private function addAttribuesToArray($data, $product)
    {
        foreach ($this->allAttributes($product) as $attribute) {
            $attrCode = $attribute->getAttributeCode();
            $attrValue = $attribute->getFrontend()->getValue($product);
            $ignoredValues = array(
                Mage::helper('catalog')->__('N/A'),
                Mage::helper('catalog')->__('No'),
                'no_selection'
            );

            if ($attrValue && !in_array($attrValue, $ignoredValues)) {
                $data['attr_' . $attrCode] = $attrValue;
            }
        }
        return $data;
    }

    public function eventData()
    {
        $product = $this->getCurrentProduct();
        $data = array(
            "id" => $this->skuId($product),
            "name" => $this->name($product),
            "price" => $this->price($product),
            // "originalPrice" => $this->originalPrice($product),
            "url" => $this->url($product),
            "image" => $this->image($product),

            "breadcrumbs" => $this->breadcrumbs($product),
            "category" => $this->categoryName($product),
            "categories" => $this->categories($product),
            "outOfStock" => $this->outOfStock($product),
            "inventory" => $this->inventory($product),
            "isVisible" => $this->isVisible($product),
            "type" => $this->getProductType($product),

            "attributeVariations" => $this->attributeNames($product),
            "variants" => $this->associatedProducts($product)
        );

        // Add product attributes
        $data = $this->addAttribuesToArray($data, $product);

        return $this->encodeEventData($data);
    }
}
