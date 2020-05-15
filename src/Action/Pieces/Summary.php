<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action\Pieces;

/**
 * Class Summary
 * @package Orderbynull\PgSqlBuilder
 */
class Summary
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
     * @var bool
     */
    private bool $shouldGroup;

    /**
     * @var string
     */
    private ?string $aggFuncName;

    /**
     * @var string
     */
    private string $attributeType;

    /**
     * Summary constructor.
     * @param int $entityId
     * @param string $attributeId
     * @param bool $shouldGroup
     * @param string|null $aggFuncName
     * @param string $attributeType
     */
    public function __construct(int $entityId, string $attributeId, bool $shouldGroup, ?string $aggFuncName, string $attributeType)
    {
        $this->entityId = $entityId;
        $this->attributeId = $attributeId;
        $this->shouldGroup = $shouldGroup;
        $this->aggFuncName = $aggFuncName;
        $this->attributeType = $attributeType;
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
     * @return bool
     */
    public function isShouldGroup(): bool
    {
        return $this->shouldGroup;
    }

    /**
     * @return string
     */
    public function getAggFuncName(): string
    {
        return $this->aggFuncName;
    }

    /**
     * @return string
     */
    public function getAttributeType(): string
    {
        return $this->attributeType;
    }
}