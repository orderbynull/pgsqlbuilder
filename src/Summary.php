<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder;

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
    private string $aggFuncName;

    /**
     * @var string
     */
    private string $attributeType;
}