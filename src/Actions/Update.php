<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\InputInterface;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Update
 * @package Orderbynull\PgSqlBuilder\Actions
 */
class Update extends Select
{
    /**
     * @var array
     */
    private array $attributesToUpdate = [];

    /**
     * @var array
     */
    private array $attributesValuesUserInputs = [];

    /**
     * @param EntityAttribute $attribute
     * @param UserInput $userInput
     */
    private function registerAttributeValueUserInput(EntityAttribute $attribute, UserInput $userInput): void
    {
        if (in_array($attribute->attributeType, [Type::ENUM, Type::FILE])) {
            if (!is_array($userInput->value)) {
                throw new InputTypeException('UserInput value must be array for ENUM or FILE type');
            }

            $userInput->value = sprintf('"%s"', implode('","', $userInput->value));
        }

        $this->attributesValuesUserInputs[$attribute->getPlaceholder(true, '_av')] = $userInput->value;
    }

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
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function getSqlQuery(): string
    {
        $objectsChunks = [];

        /** @var array $attribute */
        foreach ($this->attributesToUpdate as $attributeInput) {
            list($attribute, $input) = $attributeInput;

            switch (true) {
                case $input instanceof UserInput:
                    $this->registerAttributeValueUserInput($attribute, $input);

                    $objectsChunks[] = sprintf(
                        "jsonb_build_object('%s', jsonb_build_object('value', %s))",
                        $attribute->attributeId,
                        Type::cast('\'' . $attribute->getPlaceholder(true, '_av') . '\'', $attribute->attributeType)
                    );
                    break;
                case $input instanceof DataInput:
                    $objectsChunks[] = sprintf(
                        "jsonb_build_object('%s', jsonb_build_object('value', (SELECT %s FROM data_input.node_%d %s ORDER BY row_id DESC LIMIT 1)))",
                        $attribute->attributeId,
                        Type::cast($input->sourceNodeColumn, $attribute->attributeType),
                        $input->sourceNodeId,
                        isset($this->dataInputLimits[$input->sourceNodeId]) ? sprintf('WHERE row_id IN (%s)', join(',', $this->dataInputLimits[$input->sourceNodeId])) : ''
                    );
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
            sprintf('_%d.id IN(SELECT row_id FROM rows_to_update)', $this->baseEntityId),
            'RETURNING *'
        ];

        $selectQuery = parent::getSqlQuery();
        $updateQuery = join(' ', $queryChunks);

        return sprintf(
            'WITH rows_to_update AS (%s), updated_rows AS (%s) SELECT %s FROM updated_rows AS _%d',
            $selectQuery,
            $updateQuery,
            $this->buildReturning(),
            $this->baseEntityId
        );
    }

    /**
     * @inheritDoc
     */
    public function getUserInputBindings(): array
    {
        return array_merge(parent::getUserInputBindings(), $this->attributesValuesUserInputs);
    }
}