<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

/**
 * Class Condition
 * @package Orderbynull\PgSqlBuilder\Action\Select
 */
class Condition
{
    /**
     * @var int
     */
    public int $entityId;

    /**
     * @var string
     */
    public string $attributeId;

    /**
     * @var string
     */
    public string $attributeType;

    /**
     * @var string
     */
    public string $comprasionOperator;

    /**
     * FiltrationRule constructor.
     * @param int $entityId
     * @param string $attributeId
     * @param string $attributeType
     * @param string $comprasionOperator
     */
    public function __construct(int $entityId, string $attributeId, string $attributeType, string $comprasionOperator)
    {
        $this->entityId = $entityId;
        $this->attributeId = $attributeId;
        $this->attributeType = $attributeType;
        $this->comprasionOperator = $comprasionOperator;
    }
}