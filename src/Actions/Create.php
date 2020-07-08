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
 * Class Create
 * @package Orderbynull\PgSqlBuilder\Actions
 */
class Create extends AbstractAction
{
    use ReturningAwareTrait;

    /**
     * @var array
     */
    private array $userInputs = [];

    /**
     * @var DataInput|null
     */
    private ?DataInput $dataInput = null;

    /**
     * @var array
     */
    private array $attributesValues = [];

    /**
     * @param EntityAttribute $attribute
     * @param UserInput $userInput
     * @throws InputTypeException
     */
    private function registerUserInput(EntityAttribute $attribute, UserInput $userInput): void
    {
        if (!is_null($userInput->value) && in_array($attribute->attributeType, [Type::ENUM, Type::FILE])) {
            if (!is_array($userInput->value)) {
                throw new InputTypeException('UserInput value must be array for ENUM or FILE type');
            }

            $userInput->value = sprintf('["%s"]', implode('","', $userInput->value));
        }

        $this->userInputs[$attribute->getPlaceholder(true)] = $userInput->value;
    }

    /**
     * @return array
     */
    public function getUserInputBindings(): array
    {
        return $this->userInputs;
    }

    /**
     * @param DataInput|null $dataInput
     */
    public function setDataInput(?DataInput $dataInput): void
    {
        $this->dataInput = $dataInput;
    }

    /**
     * @param EntityAttribute $entityAttribute
     * @param InputInterface $input
     * @throws InputTypeException
     */
    public function setAttributeValue(EntityAttribute $entityAttribute, InputInterface $input): void
    {
        switch (true) {
            case $input instanceof DataInput:
                if (empty($this->dataInput)) {
                    $this->dataInput = $input;
                } else if ($input->sourceNodeId != $this->dataInput->sourceNodeId) {
                    throw new InputTypeException(
                        sprintf(
                            'Create action can have only one input data and it\'s already set to sourceNodeId=%d',
                            $this->dataInput->sourceNodeId
                        )
                    );
                }
                $this->attributesValues[] = [$entityAttribute, $input];
                break;
            case $input instanceof UserInput:
                $this->attributesValues[] = [$entityAttribute, $input];
                $this->registerUserInput($entityAttribute, $input);
                break;
        }
    }

    /**
     * @return string
     * @throws InputTypeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\AttributeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    public function getSqlQuery(): string
    {
        $buildObjectChunks = [];

        foreach ($this->attributesValues as $input) {
            list($entityAttribute, $input) = $input;

            switch (true) {
                case $input instanceof DataInput:
                    $objectValue = Type::cast(
                        "node_{$input->sourceNodeId}.{$input->sourceNodeColumn}",
                        $entityAttribute->attributeType
                    );
                    break;
                case $input instanceof UserInput:
                    if (is_null($input->value)) {
                        $objectValue = 'null';
                    } else if (in_array($entityAttribute->attributeType, [Type::ENUM, Type::FILE])) {
                        $objectValue = Type::cast(
                            sprintf("'[%s]'", $entityAttribute->getPlaceholder(true)),
                            $entityAttribute->attributeType
                        );
                    } else {
                        $objectValue = Type::cast(
                            sprintf("'%s'", $entityAttribute->getPlaceholder(true)),
                            $entityAttribute->attributeType
                        );
                    }

                    break;
                default:
                    throw new InputTypeException(sprintf('Unknown input source `%s` in %s', get_class($input), __METHOD__));
            }
            $buildObjectChunks[] = "jsonb_build_object('{$entityAttribute->attributeId}', jsonb_build_object('value', {$objectValue}))";
        }

        $queryChunks = [
            'INSERT',
            'INTO',
            sprintf('entity_values AS _%d', $this->baseEntityId),
            '(entity_id, attributes, created_at, updated_at)',
            'SELECT',
            sprintf('%d,', $this->baseEntityId),
            sprintf('%s,', join('||', $buildObjectChunks)),
            'NOW(),',
            'NOW()',
            !empty($this->dataInput) ? sprintf('FROM data_input.node_%d', $this->dataInput->sourceNodeId) : '',
            'RETURNING *'
        ];

        $insertQuery = join(' ', $queryChunks);

        return sprintf(
            'WITH rows_inserted AS (%s) SELECT %s FROM rows_inserted AS _%d',
            $insertQuery,
            $this->buildReturning(),
            $this->baseEntityId
        );
    }

    /**
     * @inheritDoc
     */
    public function getResultColumnsMeta(): array
    {
        return $this->getReturningColumnsMeta();
    }
}