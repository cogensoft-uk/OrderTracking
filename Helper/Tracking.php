<?php

namespace Cogensoft\OrderTracking\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Tracking extends AbstractHelper
{
	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field, ScopeInterface::SCOPE_STORE, $storeId
		);
	}

	public function getGeneralConfig($code, $storeId = null)
	{

		return $this->getConfigValue('cogensoft_tracking/general/'. $code, $storeId);
	}

	public function formatPrice($price)
	{
		return sprintf('%.2F', $price);
	}

	public function formatOrderNumber($orderNumber) {
		return ltrim($orderNumber, '0');
	}

}
