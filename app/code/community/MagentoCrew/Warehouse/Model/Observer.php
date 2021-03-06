<?php
/**
 * Observer module warehouse
 * @copyright   Copyright (c) 2016 MagentoCrew
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MagentoCrew_Warehouse_Model_Observer
    extends Varien_Object
{
    /**
     * Convert a catalog layer block with the right templates
     * Observes: controller_action_layout_generate_blocks_after
     *
     * @param Varien_Event_Observer $observer
     */
    public function convertLayerBlock(Varien_Event_Observer $observer)
    {
        $controller = $observer->getEvent()->getAction();
        $front = $controller->getRequest()->getRouteName();
        $controllerName = $controller->getRequest()->getControllerName();
        $action = $controller->getRequest()->getActionName();

        // Perform this operation if we're on a category view page or search results page
        if (($front == 'catalog' && $controllerName == 'category' && $action == 'view')
            || ($front == 'catalogsearch' && $controllerName == 'result' && $action == 'index')) {

            // Block name for layered navigation differs depending on which Magento edition we're in
            $blockName = 'catalog.leftnav.extend';
            if ($front == 'catalogsearch') {
                $blockName = 'catalogsearch.leftnav.extend';
            }
            Mage::helper('configurableswatches/productlist')->convertLayerBlock($blockName);
        }
    }
    
    /**
     * Add warehouse info of the product from quote as additional options
     * Observes: sales_quote_item_load_after
     *
     * @param Varien_Event_Observer $observer
     */
    public function addWarehouseOptionInfo(Varien_Event_Observer $observer)
    {
        /**
         * @var Mage_Sales_Model_Quote $quote
         */
        $quote = $observer->getEvent()->getQuote();
        $items = $quote->getItemsCollection();
        
        if (count($items)) {
            foreach ($items as $item) {
                $warehouse = Mage::getModel('mc_warehouse/warehouse')
                    ->getCollection()
                    ->getWarehouseNamesByProductId($item->getProductId());
                
                if (!count($warehouse)) {
                    continue;
                }
                
                $option = array(
                    'item_id'       => $item->getId(),
                    'product_id'    => $item->getProductId(),
                    'code'          => 'additional_options',
                    'value'         => serialize(
                            array('warehouse_product' => array(
                                'label' => 'Warehouse',
                                'value' => implode(", ", $warehouse))
                            ))
                    );
                
                $item->addOption($option);
            }
        }
    }
}
