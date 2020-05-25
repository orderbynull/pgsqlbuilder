<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Traits;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Trait ReturningAwareTrait
 * @package Orderbynull\PgSqlBuilder\Traits
 */
trait ReturningAwareTrait
{
    /**
     * @var array
     */
    private array $attributesToReturn = [];

    /**
     * @return string
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    protected function buildReturning(): string
    {
        $chunks = [];

        /** @var EntityAttribute $attribute */
        foreach ($this->attributesToReturn as $attribute) {
            $chunks[] = sprintf(
                '%s AS %s_1',
                Type::cast($attribute->getPath(), $attribute->attributeType),
                $attribute->getPlaceholder()
            );
        }

        return !empty($chunks) ? sprintf('id AS row_id, %s', join(', ', $chunks)) : '';
    }

    /**
     * @param EntityAttribute $attribute
     */
    public function addAttributeToReturn(EntityAttribute $attribute): void
    {
        $this->attributesToReturn[] = $attribute;
    }
}