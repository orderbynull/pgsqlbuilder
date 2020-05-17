<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\FiltrationException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\InputInterface;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Attribute;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Action
 * @package Orderbynull\PgSqlBuilder\Action
 */
abstract class AbstractAction
{
    /**
     * Base entity for action.
     *
     * @var int
     */
    protected int $baseEntityId;

    /**
     * Rules to apply to WHERE operator.
     * Structure:
     * [
     *    [Condition, AND|OR, condition, ...],
     *    AND|OR,
     *    [Condition, AND|OR, condition, ...],
     *    ...
     * ]
     *
     * @var array
     */
    protected array $conditions = [];

    /**
     * @var array
     */
    protected array $groupOfRules = [];

    /**
     * Holds an array of rowIds for each nodeId which data must be limited to these rowIds.
     * It works like SELECT * FROM nodeId WHERE row_id IN(rowId, rowId, ...).
     * Structure:
     * [
     *    nodeId::int => [rowId::int, ..., rowId::int],
     *    ...
     * ]
     *
     * @var array
     */
    protected array $dataInputLimits = [];

    /**
     * @var array
     */
    protected array $userInputBindings = [];

    /**
     * @var array
     */
    protected array $filterAttributeValues = [];

    /**
     * @param int $baseEntityId
     */
    public function setBaseEntityId(int $baseEntityId): void
    {
        $this->baseEntityId = $baseEntityId;
    }

    /**
     * @return array
     */
    public function getQueryBindings(): array
    {
        return $this->userInputBindings;
    }

    /**
     * @param string|null $operator
     */
    public function openConditionsGroup(?string $operator = null): void
    {
        if (empty($this->groupOfRules)) {
            return;
        }

        $this->conditions[] = $this->groupOfRules;

        if ($operator) {
            $this->conditions[] = $operator;
        }

        $this->groupOfRules = [];
    }

    public function closeConditionsGroup(): void
    {
        !empty($this->groupOfRules) && $this->conditions[] = $this->groupOfRules;
    }

    /**
     * @param Condition $condition
     * @param string|null $operator
     * @throws FiltrationException
     */
    public function addCondition(Condition $condition, ?string $operator = null): void
    {
        // AND/OR не может быть первым в группе условий
        if ($operator && !end($this->groupOfRules) instanceof Condition) {
            throw new FiltrationException('AND\OR can be added after condition only');
        }

        if (!empty($operator)) {
            $this->groupOfRules[] = $operator;
        }

        // Правила фильтрации не могут идти подряд без разделения AND/OR
        if (end($this->groupOfRules) instanceof Condition) {
            throw new FiltrationException('Cannot join two conditions without AND\OR');
        }

        $this->groupOfRules[] = $condition;
    }

    /**
     * @param int $entityId
     * @param string $attributeId
     * @param InputInterface $input
     */
    public function setConditionsAttributeValue(int $entityId, string $attributeId, InputInterface $input): void
    {
        $placeholder = Attribute::placeholder($entityId, $attributeId, true);

        if ($input instanceof UserInput) {
            $this->userInputBindings[$placeholder] = $input->value;
        }

        $this->filterAttributeValues[$placeholder] = $input;
    }

    /**
     * @param int $nodeId
     * @param array $rowIds
     */
    public function limitDataInputTo(int $nodeId, array $rowIds): void
    {
        $this->dataInputLimits[$nodeId] = $rowIds;
    }

    /**
     * @return string
     */
    abstract public function getQuery(): string;

    /**
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    protected function buildWhere(): string
    {
        $chunks = [];

        foreach ($this->conditions as $value) {
            if (is_array($value)) {
                $chunks[] = '(';
                foreach ($value as $v) {
                    if ($v instanceof Condition) {
                        $chunks[] = sprintf(
                            "%s %s %s",
                            Type::cast(
                                Attribute::path($v->entityId, $v->attributeId),
                                $v->attributeType
                            ),
                            $v->comprasionOperator,
                            $this->buildConditionInput($v)
                        );
                    } else {
                        $chunks[] = $v;
                    }
                }
                $chunks[] = ')';
            } else {
                $chunks[] = $value;
            }
        }

        return sizeof($chunks) ? sprintf('WHERE %s', join(' ', $chunks)) : '';
    }

    /**
     * @param Condition $condition
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    private function buildConditionInput(Condition $condition): string
    {
        $placeholder = Attribute::placeholder($condition->entityId, $condition->attributeId, true);

        if (empty($this->filterAttributeValues[$placeholder])) {
            throw new AttributeException(
                sprintf(
                    'Missing value for attribute `%d.%s` in %s',
                    $condition->entityId,
                    $condition->attributeId,
                    __METHOD__
                )
            );
        }

        $input = $this->filterAttributeValues[$placeholder];

        if ($input instanceof DataInput) {
            return sprintf(
                'ANY(SELECT %s FROM node_%d %s)',
                Type::cast($input->getSourceNodeColumn(), $condition->attributeType),
                $input->getSourceNodeId(),
                isset($this->dataInputLimits[$input->getSourceNodeId()]) ? sprintf('WHERE row_id IN (%s)', join(',', $this->dataInputLimits[$input->getSourceNodeId()])) : ''
            );
        }

        if ($input instanceof UserInput) {
            return Type::cast(
                sprintf("'%s'", Attribute::placeholder($condition->entityId, $condition->attributeId, true)),
                $condition->attributeType
            );
        }

        throw new InputTypeException(sprintf('Unknown input source `%s`', get_class($input)));
    }
}