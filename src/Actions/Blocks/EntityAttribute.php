<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions\Blocks;

/**
 * Class EntityAttribute
 * @package Orderbynull\PgSqlBuilder\Actions\Blocks
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('(%d.%s)::%s', $this->entityId, $this->attributeId, $this->attributeType);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return sprintf("_%d.attributes->'%s'->>'value'", $this->entityId, $this->attributeId);
    }

    /**
     * @param bool $addColon
     * @param string $suffix
     * @return string
     */
    public function getPlaceholder(bool $addColon = false, string $suffix = ''): string
    {
        $placeholder = sprintf('%sent_%d_attr_%s%s', $addColon ? ':' : '', $this->entityId, $this->attributeId, $suffix);
        return str_replace('-', '_', $placeholder);
    }
}