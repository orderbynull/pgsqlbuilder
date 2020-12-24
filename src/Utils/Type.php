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
    const FILE        = 'file';
    const LINK        = 'link';
    const ENUM        = 'enum';
    const SIGN        = 'sign';
    const TEXT        = 'text';
    const STRING      = 'string';
    const INTEGER     = 'integer';
    const DECIMAL     = 'decimal';
    const BOOLEAN     = 'boolean';
    const CALENDAR    = 'calendar';
    const DATETIME    = 'date_time';
    const FOREIGN_KEY = 'foreign_key';

    /**
     * @param string $value
     * @param string $toType
     * @return string
     * @throws TypeCastException
     */
    public static function cast(string $value, string $toType): string
    {
        switch ($toType) {
            case self::DATETIME:
                return sprintf('(%s)::timestamptz', $value);

            case self::TEXT:
            case self::STRING:
            case self::LINK:
            case self::CALENDAR:
                return sprintf('trim((%s)::text)', $value);

            case self::FILE:
            case self::SIGN:
            case self::ENUM:
                return sprintf('(%s)::jsonb', $value);

            case self::INTEGER:
            case self::FOREIGN_KEY:
                return sprintf('(%s)::int', $value);

            case self::DECIMAL:
                return sprintf('(%s)::decimal', $value);

            case self::BOOLEAN:
                return sprintf('(%s)::bool', $value);
        }

        throw new TypeCastException(sprintf('Type casting to `%s` is not supported in %s', $toType, __METHOD__));
    }
}
