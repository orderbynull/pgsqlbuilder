<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

/**
 * Class Condition
 * @package Orderbynull\PgSqlBuilder\Action\Select
 */
class Condition
{
    /**
     * @var EntityAttribute
     */
    public EntityAttribute $attribute;

    /**
     * @var string
     */
    public string $comprasionOperator;

    /**
     * Condition constructor.
     * @param EntityAttribute $attribute
     * @param string $comprasionOperator
     */
    public function __construct(EntityAttribute $attribute, string $comprasionOperator)
    {
        $this->attribute = $attribute;
        $this->comprasionOperator = $comprasionOperator;
    }
}