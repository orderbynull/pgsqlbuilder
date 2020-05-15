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
            case 'string':
                return sprintf('(%s)::text', $value);
            case 'integer':
                return sprintf('(%s)::int', $value);
        }

        throw new TypeCastException(sprintf('Type casting to %s is not supported in %s', $toType, __METHOD__));
    }
}