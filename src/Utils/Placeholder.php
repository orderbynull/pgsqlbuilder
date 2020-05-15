<?php
declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Utils;

/**
 * Class Placeholder
 * @package Orderbynull\PgSqlBuilder\Utils
 */
class Placeholder
{
    /**
     * @param int $entityId
     * @param string $attributeId
     * @param bool $addColon
     * @return string
     */
    public static function make(int $entityId, string $attributeId, bool $addColon = false): string
    {
        return str_replace('-', '_', sprintf('%sent_%d_attr_%s', $addColon ? ':' : '', $entityId, $attributeId));
    }
}