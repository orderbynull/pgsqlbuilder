<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action\Create;

use Orderbynull\PgSqlBuilder\Action\AbstractAction;
use Orderbynull\PgSqlBuilder\Action\EntityAttribute;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\InputInterface;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Create
 * @package Orderbynull\PgSqlBuilder\Action\Create
 */
class Create extends AbstractAction
{
    /**
     * @var array
     */
    private array $dataInputs = [];

    /**
     * @var array
     */
    private array $userInputs = [];

    /**
     * @var int
     */
    private int $dataInputNodeId;

    /**
     * @param EntityAttribute $entityAttribute
     * @param InputInterface $input
     */
    public function setAttributeValue(EntityAttribute $entityAttribute, InputInterface $input): void
    {
        switch (true) {
            case $input instanceof DataInput:
                $this->dataInputs[] = [$entityAttribute, $input];
                $this->dataInputNodeId = $input->getSourceNodeId();
                break;
            case $input instanceof UserInput:
                $this->userInputs[] = [$entityAttribute, $input];
                $this->registerUserInputAsBinding($entityAttribute, $input);
                break;
        }
    }

    /**
     * @return string
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    public function getQuery(): string
    {
        if (!empty($this->dataInputs)) {
            return $this->buildQueryWithDataInput();
        }

        return $this->buildQueryWithoutDataInput();
    }

    /**
     * @return string
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    private function buildQueryWithDataInput(): string
    {
        $buildObjectChunks = [];

        foreach ($this->userInputs as $userInput) {
            list($entityAttribute, $input) = $userInput;

            $objectValue = Type::cast(
                sprintf("'%s'", $entityAttribute->getPlaceholder(true)),
                $entityAttribute->attributeType
            );
            $buildObjectChunks[] = "jsonb_build_object('{$entityAttribute->attributeId}', jsonb_build_object('value', {$objectValue}))";
        }

        foreach ($this->dataInputs as $dataInput) {
            list($entityAttribute, $input) = $dataInput;

            $objectValue = Type::cast(
                "node_{$input->getSourceNodeId()}.{$input->getSourceNodeColumn()}",
                $entityAttribute->attributeType
            );
            $buildObjectChunks[] = "jsonb_build_object('{$entityAttribute->attributeId}', jsonb_build_object('value', {$objectValue}))";
        }

        $queryChunks = [
            'INSERT',
            'INTO',
            'entity_values',
            '(entity_id, attributes, created_at, updated_at)',
            'SELECT',
            sprintf('%d,', $this->baseEntityId),
            sprintf('%s,', join('||', $buildObjectChunks)),
            'NOW(),',
            'NOW()',
            'FROM',
            sprintf('node_%d', $this->dataInputNodeId)
        ];

        return join(' ', $queryChunks);
    }

    /**
     * @return string
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    private function buildQueryWithoutDataInput(): string
    {
        $buildObjectChunks = [];

        foreach ($this->userInputs as $userInput) {
            list($entityAttribute, $input) = $userInput;

            $objectValue = Type::cast(
                sprintf("'%s'", $entityAttribute->getPlaceholder(true)),
                $entityAttribute->attributeType
            );
            $buildObjectChunks[] = "jsonb_build_object('{$entityAttribute->attributeId}', jsonb_build_object('value', {$objectValue}))";
        }

        $queryChunks = [
            'INSERT',
            'INTO',
            'entity_values',
            '(entity_id, attributes, created_at, updated_at)',
            'SELECT',
            sprintf('%d,', $this->baseEntityId),
            sprintf('%s,', join('||', $buildObjectChunks)),
            'NOW(),',
            'NOW()'
        ];

        return join(' ', $queryChunks);
    }
}