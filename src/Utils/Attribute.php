<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Utils;

/**
 * Class Attribute
 * @package Orderbynull\PgSqlBuilder\Utils
 */
class Attribute
{
    /**
     * @param int $entityId
     * @param string $attributeId
     * @return string
     */
    public static function path(int $entityId, string $attributeId): string
    {
        return sprintf("_%d.attributes->'%s'->>'value'", $entityId, $attributeId);
    }

    /**
     * @param int $entityId
     * @param string $attributeId
     * @param bool $addColon
     * @return string
     */
    public static function placeholder(int $entityId, string $attributeId, bool $addColon = false): string
    {
        return str_replace('-', '_', sprintf('%sent_%d_attr_%s', $addColon ? ':' : '', $entityId, $attributeId));
    }
}