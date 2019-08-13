<?php
class Bluecore_Integration_Block_Category extends Bluecore_Integration_Block_Common_Abstract
{
    private function getCurrentCategoryName()
    {
        return $this->getCurrentCategory()->getName();
    }

    private function getBreadcrumbs()
    {
        $categoryNames = array();
        $category = $this->getCurrentCategory();
        if ($category) {
            $categories = $category->getParentCategories();
            foreach ($categories as $category) {
                $categoryNames[] = $category->getName();
            }
        }
        return join(',', $categoryNames);
    }

    public function eventData()
    {
        $data = array(
            "category" => $this->getCurrentCategoryName(),
            "breadcrumbs" => $this->getBreadcrumbs()
        );

        return $this->encodeEventData($data);
    }
}
