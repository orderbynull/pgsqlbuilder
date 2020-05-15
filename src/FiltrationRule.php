<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Input\InputInterface;

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
     * @var InputInterface
     */
    private InputInterface $comprasionSource;

    /**
     * FiltrationRule constructor.
     * @param int $entityId
     * @param string $attributeId
     * @param string $attributeType
     * @param string $comprasionOperator
     * @param InputInterface $comprasionSource
     */
    public function __construct(int $entityId, string $attributeId, string $attributeType, string $comprasionOperator, InputInterface $comprasionSource)
    {
        $this->entityId = $entityId;
        $this->attributeId = $attributeId;
        $this->attributeType = $attributeType;
        $this->comprasionOperator = $comprasionOperator;
        $this->comprasionSource = $comprasionSource;
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

    /**
     * @return InputInterface
     */
    public function getInputSource(): InputInterface
    {
        return $this->comprasionSource;
    }
}