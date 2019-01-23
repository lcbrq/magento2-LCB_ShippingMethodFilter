<?php

namespace LCB\ShippingMethodFilter\Plugin;

/**
 * @category    LCB
 * @package     LCB_ShippingMethodFilter
 * @author      Tomasz Silpion Gregorczyk <tomasz@silpion.com.pl>
 **/
class Result
{

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var \Magento\Framework\Event\Manager $eventManager
     */
    protected $eventManager;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Event\Manager $eventManager
     */
    public function __construct(
            \Magento\Customer\Model\Session $customerSession,
            \Magento\Checkout\Model\Session $checkoutSession,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Framework\Event\Manager $eventManager
        )
    {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig     = $scopeConfig;
        $this->eventManager    = $eventManager;
    }

    /**
     * Filter shipping methods
     * 
     * @param \Magento\Shipping\Model\Rate\Result $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllRates(\Magento\Shipping\Model\Rate\Result $subject, $result)
    {

        $filteredRates   = array();
        $customerGroupId = $this->customerSession->getCustomer()->getGroupId();

        foreach ($result as $rate) {
            
            $rate->setIsActive(true);

            $isAllowed = true;

            $carrierCode = $rate->getCarrier();
            $methodCode  = $rate->getMethod();

            $dissalowedCustomerGroups = $this->scopeConfig->getValue('shipping_restriction/' . $carrierCode . '_' . $methodCode . '/customer_groups', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $maxWeight = (float) $this->scopeConfig->getValue('shipping_restriction/' . $carrierCode . '_' . $methodCode . '/max_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $maxHeight = (float) $this->scopeConfig->getValue('shipping_restriction/' . $carrierCode . '_' . $methodCode . '/max_height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $maxLength = (float) $this->scopeConfig->getValue('shipping_restriction/' . $carrierCode . '_' . $methodCode . '/max_length', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $maxWidth = (float) $this->scopeConfig->getValue('shipping_restriction/' . $carrierCode . '_' . $methodCode . '/max_width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if (
                    ($dissalowedCustomerGroups && $customerGroupId == \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID) || 
                    ($dissalowedCustomerGroups && !in_array($customerGroupId, explode(',', $dissalowedCustomerGroups)))
               ) {
                $rate->setIsActive(false);
            }

            if($rate->getIsActive() && ($maxWeight || $maxHeight || $maxLength || $maxWidth)) {
                $cartWeight = 0;
                $itemsHeight = [];
                $itemsLength = [];
                $itemsWidth = [];
                $quote = $this->checkoutSession->getQuote();
                if($quote && $quote->getId()) {
                    $items = $quote->getAllVisibleItems();
                    foreach($items as $item) {
                        $itemId = $item->getId();
                        $cartWeight += ($item->getWeight() * $item->getQty());
                        $product = $item->getProduct();
                        if($product) {
                            $itemsHeight[$itemId] = $product->getHeight();
                            $itemsLength[$itemId] = $product->getLength();
                            $itemsWidth[$itemId] = $product->getWidth();
                        }
                    }

                    if($maxWeight && $cartWeight > $maxWeight) {
                        $rate->setIsActive(false);
                    }

                    if($maxHeight && max($itemsHeight) > $maxHeight) {
                        $rate->setIsActive(false);
                    }

                    if($maxLength && max($itemsLength) > $maxLength) {
                        $rate->setIsActive(false);
                    }

                    if($maxWidth && max($itemsWidth) > $maxWidth) {
                        $rate->setIsActive(false);
                    }
                }
            }

            $this->eventManager->dispatch('shipping_rate_is_active', ['rate' => $rate]);

            if ($rate->getIsActive()) {
                $filteredRates[] = $rate;
            }

        }

        return $filteredRates;

    }

} 