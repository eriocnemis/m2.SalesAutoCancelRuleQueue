<?php
/**
 * Copyright © Eriocnemis, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Eriocnemis\SalesAutoCancelRuleQueue\Plugin\Api;

use Eriocnemis\SalesAutoCancelRuleApi\Api\Data\RuleInterface;
use Eriocnemis\SalesAutoCancelRule\Api\GetMatchOrderListInterface;
use Eriocnemis\SalesAutoCancelRule\Api\CancelOrdersByRuleInterface;
use Eriocnemis\SalesAutoCancelRuleQueue\Helper\Data as Helper;
use Eriocnemis\SalesAutoCancelRuleQueue\Model\ScheduleBulk;

/**
 * Cancel order by rule plugin
 */
class CancelOrdersByRulePlugin
{
    /**
     * @var GetMatchOrderListInterface
     */
    private $getMatchOrderList;

    /**
     * @var ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * Initialize plugin
     *
     * @param Helper $helper
     * @param ScheduleBulk $scheduleBulk
     * @param GetMatchOrderListInterface $getMatchOrderList
     * @return void
     */
    public function __construct(
        Helper $helper,
        ScheduleBulk $scheduleBulk,
        GetMatchOrderListInterface $getMatchOrderList
    ) {
        $this->helper = $helper;
        $this->scheduleBulk = $scheduleBulk;
        $this->getMatchOrderList = $getMatchOrderList;
    }

    /**
     * Cancel order by rule
     *
     * @param CancelOrdersByRuleInterface $subject
     * @param callable $proceed
     * @param RuleInterface $rule
     * @return void
     */
    public function aroundExecute(
        CancelOrdersByRuleInterface $subject,
        callable $proceed,
        RuleInterface $rule
    ) {
        if (!$this->helper->isAsynchronous()) {
            $proceed($rule);
        } else {
            $searchResult = $this->getMatchOrderList->execute($rule);
            if ($searchResult->getTotalCount()) {
                $this->scheduleBulk->execute($rule, $searchResult->getItems());
            }
        }
    }
}
