<?php
/**
 * Copyright © Eriocnemis, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Eriocnemis\SalesAutoCancelRuleQueue\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Helper
 */
class Data extends AbstractHelper
{
    /**
     * Enabled config path
     */
    private const XML_ENABLED = 'sales/eriocnemis_sales_autocancel_rule/async_enabled';

    /**
     * Checks async functionality should be enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAsynchronous($storeId = null)
    {
        return $this->isSetFlag(self::XML_ENABLED, $storeId);
    }

    /**
     * Retrieve config value by path and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    private function getValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Retrieve config flag
     *
     * @param string $path
     * @param int|null $storeId
     * @return bool
     */
    private function isSetFlag($path, $storeId = null)
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
