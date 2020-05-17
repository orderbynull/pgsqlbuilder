<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action\Pieces;

/**
 * Class EntityAttribute
 * @package Orderbynull\PgSqlBuilder
 */
class EntityAttribute
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
     * EntityAttribute constructor.
     * @param int $entityId
     * @param string $attributeId
     * @param string $attributeType
     */
    public function __construct(int $entityId, string $attributeId, string $attributeType)
    {
        $this->entityId = $entityId;
        $this->attributeId = $attributeId;
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
     * @return string
     */
    public function getAttributeType(): string
    {
        return $this->attributeType;
    }
}