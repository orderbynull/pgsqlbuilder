<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions\Blocks;

use Orderbynull\PgSqlBuilder\Input\InputInterface;

/**
 * Class Condition
 * @package Orderbynull\PgSqlBuilder\Actions\Blocks
 */
class Condition
{
    /**
     * @var InputInterface
     */
    public InputInterface $value;

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
     * @param InputInterface $value
     */
    public function __construct(EntityAttribute $attribute, string $comprasionOperator, InputInterface $value)
    {
        $this->value = $value;
        $this->attribute = $attribute;
        $this->comprasionOperator = $comprasionOperator;
    }
}