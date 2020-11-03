<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Traits;

use Orderbynull\PgSqlBuilder\Actions\Blocks\Condition;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\FiltrationException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Trait WhereAwareTrait
 * @package Orderbynull\PgSqlBuilder\Traits
 */
trait WhereAwareTrait
{
    /**
     * @var array
     */
    protected array $conditionsUserInputs = [];
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
    private array $conditions = [];
    /**
     * @var array
     */
    private array $groupOfRules = [];
    /**
     * @var array
     */
    private array $attributesValues = [];

    /**
     * @param string|null $operator
     */
    public function openConditionsGroup(?string $operator = null): void
    {
        if (!is_null($operator)) {
            $this->conditions[] = strtoupper($operator);
        }
    }

    public function closeConditionsGroup(): void
    {
        if (!empty($this->groupOfRules)) {
            $this->conditions[] = $this->groupOfRules;
            $this->groupOfRules = [];
        }
    }

    /**
     * @param string|null $logic
     * @param Condition $condition
     * @throws FiltrationException
     */
    public function addCondition(?string $logic, Condition $condition): void
    {
        // AND/OR не может быть первым в группе условий
        if (!is_null($logic) && !end($this->groupOfRules) instanceof Condition) {
            throw new FiltrationException('AND\OR can be added after condition only');
        }

        if (!is_null($logic)) {
            $this->groupOfRules[] = $logic;
        }

        // Правила фильтрации не могут идти подряд без разделения AND/OR
        if (end($this->groupOfRules) instanceof Condition) {
            throw new FiltrationException('Cannot join two conditions without AND\OR');
        }

        $this->groupOfRules[] = $condition;
    }

    /**
     * @param int $baseEntityId
     * @return string
     * @throws InputTypeException
     * @throws TypeCastException
     */
    protected function buildWhere(int $baseEntityId): string
    {
        $chunks = [];

        foreach ($this->conditions as $value) {
            if (is_array($value)) {
                $chunks[] = '(';
                foreach ($value as $v) {
                    if ($v instanceof Condition) {
                        $chunks[] = $this->buildCondition($v);
                    } else {
                        $chunks[] = $v;
                    }
                }
                $chunks[] = ')';
            } else {
                $chunks[] = $value;
            }
        }

        if (count($chunks)) {
            return sprintf('WHERE _%d.entity_id = %d AND (%s) AND deleted_at IS NULL', $baseEntityId, $baseEntityId, join(' ', $chunks));
        }

        return sprintf('WHERE _%d.entity_id = %d AND deleted_at IS NULL', $baseEntityId, $baseEntityId);
    }

    /**
     * Преобразует Condition в строку вида "attribute = value"
     *
     * @param Condition $condition
     * @return string
     * @throws InputTypeException
     * @throws TypeCastException
     */
    private function buildCondition(Condition $condition): string
    {
        $attributeValue = Type::cast($condition->attribute->getValue(), $condition->attribute->attributeType);
        switch ($condition->attribute->attributeType) {
            case Type::ENUM:
            case Type::FILE:
            case Type::SIGN:
                if ($condition->comprasionOperator === '<>') {
                    return sprintf('NOT(%s %s %s)', $attributeValue, '??|', $this->rightPartOfConditionToSql($condition));
                }
                return sprintf('%s %s %s', $attributeValue, '??|', $this->rightPartOfConditionToSql($condition));
        }

        return sprintf('%s %s %s', $attributeValue, $condition->comprasionOperator, $this->rightPartOfConditionToSql($condition));
    }

    /**
     * @param Condition $condition
     * @return string
     * @throws InputTypeException
     * @throws TypeCastException
     */
    private function rightPartOfConditionToSql(Condition $condition): string
    {
        $input = $condition->value;

        if ($input instanceof DataInput) {
            switch ($condition->attribute->attributeType) {
                case Type::ENUM:
                case Type::FILE:
                case Type::SIGN:
                    return sprintf(
                        '(SELECT array_agg(value)::text[] from data_input.node_%d, jsonb_array_elements_text(%s::jsonb) %s)',
                        $input->sourceNodeId,
                        $input->sourceNodeColumn,
                        isset($this->dataInputLimits[$input->sourceNodeId]) ? sprintf('WHERE row_id IN (%s)', join(',', $this->dataInputLimits[$input->sourceNodeId])) : ''
                    );

                default:
                    return sprintf(
                        'ANY(SELECT %s FROM data_input.node_%d %s)',
                        Type::cast($input->sourceNodeColumn, $condition->attribute->attributeType),
                        $input->sourceNodeId,
                        isset($this->dataInputLimits[$input->sourceNodeId]) ? sprintf('WHERE row_id IN (%s)', join(',', $this->dataInputLimits[$input->sourceNodeId])) : ''
                    );
            }
        }

        if ($input instanceof UserInput) {
            switch ($condition->attribute->attributeType) {
                case Type::ENUM:
                case Type::FILE:
                case Type::SIGN:
                    return sprintf("ARRAY[%s]::text[]", $this->placeValue($condition, true));
                default:
                    return Type::cast(
                        sprintf("'%s'", $this->placeValue($condition, true)),
                        $condition->attribute->attributeType
                    );
            }
        }

        throw new InputTypeException(sprintf('Unknown input source `%s` in %s', get_class($input), __METHOD__));
    }

    /**
     * @param Condition $condition
     * @param bool $addColon
     * @return string
     * @throws InputTypeException
     */
    private function placeValue(Condition $condition, bool $addColon = false): string
    {
        if (!($condition->value instanceof UserInput)) {
            throw new InputTypeException(sprintf('Only UserInput allowed in %s', __METHOD__));
        }

        $placeholder = (string)random_int(1, PHP_INT_MAX);

        if ($addColon === true) {
            $placeholder = ":{$placeholder}";
        }

        if (!is_null($condition->value) && in_array($condition->attribute->attributeType, [Type::ENUM, Type::FILE, Type::SIGN])) {
            if (!is_array($condition->value->value)) {
                throw new InputTypeException('UserInput value must be array for ENUM, FILE and SIGN types');
            }

            $condition->value->value = sprintf("'%s'", implode("','", $condition->value->value));
        }

        $this->conditionsUserInputs[$placeholder] = $condition->value->value;

        return $placeholder;
    }
}