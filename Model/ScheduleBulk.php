<?php
/**
 * Copyright © Eriocnemis, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Eriocnemis\SalesAutoCancelRuleQueue\Model;

use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Eriocnemis\SalesAutoCancelRuleApi\Api\Data\RuleInterface;

/**
 * Schedule bulk cancel of orders
 */
class ScheduleBulk
{
    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var OperationBuilder
     */
    private $operationBuilder;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * Initialize schedule bulk
     *
     * @param BulkManagementInterface $bulkManagement
     * @param IdentityGeneratorInterface $identityService
     * @param DataObjectProcessor $dataObjectProcessor
     * @param OperationBuilder $operationBuilder
     */
    public function __construct(
        BulkManagementInterface $bulkManagement,
        IdentityGeneratorInterface $identityService,
        DataObjectProcessor $dataObjectProcessor,
        OperationBuilder $operationBuilder
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->identityService = $identityService;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->operationBuilder = $operationBuilder;
    }

    /**
     * Schedule new bulk
     *
     * @param RuleInterface $rule
     * @param OrderInterface[] $orders
     * @throws LocalizedException
     * @return void
     */
    public function execute(RuleInterface $rule, array $orders)
    {
        if (0 == count($orders)) {
            return;
        }

        $bulkUuid = $this->identityService->generateId();
        $operations = $this->getBulkOperations($rule, $orders, $bulkUuid);
        $description = (string)__('Automatic cancellation of orders with an expired payment period.');

        $result = $this->bulkManagement->scheduleBulk($bulkUuid, $operations, $description);
        if (!$result) {
            throw new LocalizedException(
                __('Something went wrong while processing the request.')
            );
        }
    }

    /**
     * Retrieve bulk operations
     *
     * @param RuleInterface $rule
     * @param OrderInterface[] $orders
     * @param string $bulkUuid
     * @return OperationInterface[]
     */
    private function getBulkOperations(RuleInterface $rule, array $orders, $bulkUuid)
    {
        $operations = [];
        $data = $this->getRuleData($rule);

        foreach ($orders as $order) {
            $operations[] = $this->operationBuilder->build(
                $bulkUuid,
                'eriocnemis_sales.autocancel.order',
                ['order_id' => $order->getEntityId(), 'rule_data' => $data]
            );
        }
        return $operations;
    }

    /**
     * Retrieve rule data
     *
     * @param RuleInterface $rule
     * @return mixed[]
     */
    private function getRuleData(RuleInterface $rule)
    {
        return $this->dataObjectProcessor->buildOutputDataArray(
            $rule,
            RuleInterface::class
        );
    }
}
