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
}