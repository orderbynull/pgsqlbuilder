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
            $chunks[] = Type::cast($attribute->getPath(), $attribute->attributeType);
        }

        return !empty($chunks) ? sprintf('RETURNING %s', join(',', $chunks)) : '';
    }

    /**
     * @param EntityAttribute $attribute
     */
    public function addAttributeToReturn(EntityAttribute $attribute): void
    {
        $this->attributesToReturn[] = $attribute;
    }
}