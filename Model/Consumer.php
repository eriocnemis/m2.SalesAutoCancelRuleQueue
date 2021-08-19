<?php
/**
 * Copyright © Eriocnemis, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Eriocnemis\SalesAutoCancelRuleQueue\Model;

use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Eriocnemis\SalesAutoCancelRule\Api\CancelOrderInterface;
use Eriocnemis\SalesAutoCancelRuleApi\Api\Data\RuleInterfaceFactory;

/**
 * Consumer for auto cancel message
 */
class Consumer
{
    /**
     * @var CancelOrderInterface
     */
    private $cancelOrder;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RuleInterfaceFactory
     */
    private $ruleFactory;

    /**
     * Initialize consumer
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param RuleInterfaceFactory $ruleFactory
     * @param CancelOrderInterface $cancelOrder
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RuleInterfaceFactory $ruleFactory,
        CancelOrderInterface $cancelOrder,
        SerializerInterface $serializer,
        EntityManager $entityManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->ruleFactory = $ruleFactory;
        $this->cancelOrder = $cancelOrder;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * Consumer process
     *
     * @param OperationListInterface $operationList
     * @return void
     */
    public function process(OperationListInterface $operationList)
    {
        foreach ($operationList->getItems() as $operation) {
            $status = $this->processOperation($operation);
            $operation->setStatus($status);
            $operation->setResultMessage('');
        }
        $this->entityManager->save($operationList);
    }

    /**
     * Process bulk operation
     *
     * @param OperationInterface $operation
     * @return int
     */
    private function processOperation(OperationInterface $operation)
    {
        $operationStatus = OperationInterface::STATUS_TYPE_COMPLETE;

        try {
            $data = $this->serializer->unserialize(
                $operation->getSerializedData()
            );

            if (is_array($data)) {
                $this->cancelOrder->execute(
                    $this->orderRepository->get($data['order_id']),
                    $this->ruleFactory->create(['data' => $data['rule_data']])
                );
            }
        } catch (\Exception $e) {
            $operationStatus = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
        }

        return $operationStatus;
    }
}
