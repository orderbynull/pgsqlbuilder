<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Utils;

use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;

/**
 * Class Type
 * @package Orderbynull\PgSqlBuilder\Utils
 */
class Type
{
    /**
     * @param string $value
     * @param string $toType
     * @return string
     * @throws TypeCastException
     */
    public static function cast(string $value, string $toType): string
    {
        switch ($toType) {
            case 'file':
            case 'enum':
            case 'text':
            case 'string':
            case 'datetime':
                return sprintf('(%s)::text', $value);

            case 'integer':
            case 'foreign_key':
                return sprintf('(%s)::int', $value);

            case 'decimal':
                return sprintf('(%s)::decimal', $value);

            case 'boolean':
                return sprintf('(%s)::bool', $value);
        }

        throw new TypeCastException(sprintf('Type casting to %s is not supported in %s', $toType, __METHOD__));
    }
}