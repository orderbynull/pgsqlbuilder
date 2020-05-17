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
}