<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Input;

/**
 * Class UserInput
 * @package Orderbynull\PgSqlBuilder\Input
 */
class UserInput implements InputInterface
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * UserInput constructor.
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}