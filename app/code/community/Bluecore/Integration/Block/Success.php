<?php
class Bluecore_Integration_Block_Success extends Bluecore_Integration_Block_Common_Abstract
{
    private function getOrder($orderId)
    {
        $order = Mage::getModel('sales/order');
        return $order->loadByIncrementId($orderId);
    }

    private function getCustomerEmail($order)
    {
        return $order->getCustomerEmail();
    }

    private function getTotal($order)
    {
        // $total = $order->getSubtotal();  // w/o tax (no shipping)
        // $total = $order->getSubtotalInclTax();    // w/ tax (no shipping)
        $total = $order->getGrandTotal();  // w/ tax and shipping (discounts applied)
        return $total;
    }

    private function getProductIds($order)
    {
        $products = array();
        foreach ($order->getAllVisibleItems() as $item) {
            $childItems = $item->getChildrenItems();
            if (!empty($childItems)) {
                foreach ($childItems as $childItem) {
                    $products[] = array(id => $childItem->getSku());
                }
            } else {
                $products[] = array(id => $item->getSku());
            }
        }

        return $products;
    }

    private function getOrderIds()
    {
        $orderIds = Mage::getSingleton('core/session')->getOrderIds(true);
        if ($orderIds) {
            // get just the customer facing IDs
            $orderIds = array_values($orderIds);
        } else {
            $singleOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $orderIds = array($singleOrderId);
        }
        return $orderIds;
    }

    public function ordersEventData()
    {
        $events = array();
        foreach ($this->getOrderIds() as $orderId) {
            // fetch order
            $order = $this->getOrder($orderId);

            $orderEvent = array(
                "email" => $this->getCustomerEmail($order),
                "total" => $this->getTotal($order),
                "productIds" => $this->getProductIds($order),
                "orderId" => $orderId,
            );
            $orderEvent = $this->encodeEventData($orderEvent);

            $events[] = $orderEvent;
        }

        return $events;
    }
}
