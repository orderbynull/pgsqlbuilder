<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action\Pieces;

/**
 * Class FiltrationGroup
 * @package Orderbynull\PgSqlBuilder
 */
class FiltrationRule
{
    /**
     * @var int
     */
    private int $entityId;

    /**
     * @var string
     */
    private string $attributeId;

    /**
     * @var string
     */
    private string $attributeType;

    /**
     * @var string
     */
    private string $comprasionOperator;

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

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getAttributeId(): string
    {
        return $this->attributeId;
    }

    /**
     * @return string
     */
    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    /**
     * @return string
     */
    public function getComprasionOperator(): string
    {
        return $this->comprasionOperator;
    }
}