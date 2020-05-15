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
}