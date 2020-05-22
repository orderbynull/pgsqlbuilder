<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\InputInterface;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Traits\ReturningAwareTrait;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Update
 * @package Orderbynull\PgSqlBuilder\Actions
 */
class Update extends Select
{
    use ReturningAwareTrait;

    /**
     * @var array
     */
    private array $attributesToUpdate = [];

    /**
     * @param EntityAttribute $attribute
     * @param InputInterface $input
     */
    public function setAttributeToUpdate(EntityAttribute $attribute, InputInterface $input): void
    {
        $this->attributesToUpdate[] = [$attribute, $input];
    }

    /**
     * @return string
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\AttributeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\InputTypeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    public function getSqlQuery(): string
    {
        $objectsChunks = [];

        /** @var array $attribute */
        foreach ($this->attributesToUpdate as $attributeInput) {
            list($attribute, $input) = $attributeInput;

            switch (true) {
                case $input instanceof UserInput:
                    $objectsChunks[] = sprintf(
                        "jsonb_build_object('%s', jsonb_build_object('value', %s))",
                        $attribute->attributeId,
                        Type::cast('\'' . $attribute->getPlaceholder(true) . '\'', $attribute->attributeType)
                    );
                    break;
                case $input instanceof DataInput:
                    $objectsChunks[] = sprintf("jsonb_build_object('%s', jsonb_build_object('value', 'x'))", $attribute->attributeId);
                    break;
                default:
                    throw new InputTypeException(sprintf('Unknown input source `%s` in %s', get_class($input), __METHOD__));
            }
        }

        $queryChunks = [
            'UPDATE',
            sprintf('entity_values AS _%d', $this->baseEntityId),
            'SET',
            'updated_at = NOW(),',
            'attributes = attributes ||',
            join(' || ', $objectsChunks),
            'WHERE',
            sprintf('_%d.id IN(SELECT row_id FROM source)', $this->baseEntityId),
            $this->buildReturning()
        ];

        $selectQuery = parent::getSqlQuery();
        $updateQuery = join(' ', $queryChunks);

        return sprintf('WITH source AS (%s) %s', $selectQuery, $updateQuery);
    }

    /**
     * @inheritDoc
     */
    public function getUserInputBindings(): array
    {
        return [];
    }
}