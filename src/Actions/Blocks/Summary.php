<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions\Blocks;

/**
 * Class Summary
 * @package Orderbynull\PgSqlBuilder\Actions\Blocks
 */
class Summary
{
    /**
     * @var bool
     */
    public bool $shouldGroup;

    /**
     * @var string|null
     */
    public ?string $aggFuncName;

    /**
     * @var EntityAttribute
     */
    public EntityAttribute $attribute;

    /**
     * Summary constructor.
     * @param bool $shouldGroup
     * @param string|null $aggFuncName
     * @param EntityAttribute $attribute
     */
    public function __construct(EntityAttribute $attribute, bool $shouldGroup, ?string $aggFuncName)
    {
        $this->attribute = $attribute;
        $this->aggFuncName = $aggFuncName;
        $this->shouldGroup = $shouldGroup;
    }
}