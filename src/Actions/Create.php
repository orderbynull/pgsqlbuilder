<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Blocks\ResultColumnMeta;
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
     * @var int
     */
    private ?int $dataInputNodeId = null;

    /**
     * @var array
     */
    private array $attributesValues = [];

    /**
     * @param EntityAttribute $attribute
     * @param UserInput $userInput
     */
    private function registerUserInput(EntityAttribute $attribute, UserInput $userInput): void
    {
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
     * @param EntityAttribute $entityAttribute
     * @param InputInterface $input
     */
    public function setAttributeValue(EntityAttribute $entityAttribute, InputInterface $input): void
    {
        switch (true) {
            case $input instanceof DataInput:
                $this->attributesValues[] = [$entityAttribute, $input];
                $this->dataInputNodeId = $input->sourceNodeId;
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
                    $objectValue = Type::cast(
                        sprintf("'%s'", $entityAttribute->getPlaceholder(true)),
                        $entityAttribute->attributeType
                    );
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
            !empty($this->dataInputNodeId) ? sprintf('FROM node_%d', $this->dataInputNodeId) : '',
            $this->buildReturning()
        ];

        return join(' ', $queryChunks);
    }

    /**
     * @inheritDoc
     */
    public function getResultColumnsMeta(): array
    {
        return [];
    }


}