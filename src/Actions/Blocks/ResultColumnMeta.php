<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions\Blocks;

/**
 * Class ResultColumnMeta
 * @package Orderbynull\PgSqlBuilder\Actions\Blocks
 */
class ResultColumnMeta
{
    /**
     * @var string
     */
    public string $columnId;

    /**
     * @var string
     */
    public ?string $aggFunction;

    /**
     * @var EntityAttribute
     */
    public EntityAttribute $attribute;

    /**
     * ResultColumnMeta constructor.
     * @param string $columnId
     * @param EntityAttribute $attribute
     * @param string|null $aggFunction
     */
    public function __construct(string $columnId, EntityAttribute $attribute, ?string $aggFunction = null)
    {
        $this->columnId = $columnId;
        $this->attribute = $attribute;
        $this->aggFunction = $aggFunction;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s_%s_%s', $this->columnId, (string)$this->attribute, $this->aggFunction ?? '');
    }
}