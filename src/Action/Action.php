<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

use Orderbynull\PgSqlBuilder\Action\Pieces\FiltrationRule;
use Orderbynull\PgSqlBuilder\Exceptions\FiltrationException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Attribute;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Action
 * @package Orderbynull\PgSqlBuilder\Action
 */
abstract class Action
{
    /**
     * @var int
     */
    protected int $baseEntityId;

    /**
     * @var array
     */
    protected array $groupOfRules = [];

    /**
     * @var array
     */
    protected array $filtrationRules = [];

    /**
     * @var array
     */
    protected array $dataInputLimits = [];

    /**
     * @var array
     */
    protected array $userInputBindings = [];

    /**
     * @param int $baseEntityId
     * @return $this
     */
    public function setBaseEntityId(int $baseEntityId): self
    {
        $this->baseEntityId = $baseEntityId;

        return $this;
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
     * @return $this
     */
    public function openFiltrationGroup(?string $operator = null): self
    {
        if (empty($this->groupOfRules)) {
            return $this;
        }

        $this->filtrationRules[] = $this->groupOfRules;

        if ($operator) {
            $this->filtrationRules[] = $operator;
        }

        $this->groupOfRules = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function closeFiltrationGroup(): self
    {
        !empty($this->groupOfRules) && $this->filtrationRules[] = $this->groupOfRules;

        return $this;
    }

    /**
     * @param string|null $operator
     * @param FiltrationRule $rule
     * @return $this
     * @throws FiltrationException
     */
    public function addFiltrationRule(?string $operator, FiltrationRule $rule): self
    {
        // AND/OR не может быть первым в группе условий
        if ($operator && !end($this->groupOfRules) instanceof FiltrationRule) {
            throw new FiltrationException('AND\OR can be added after condition only');
        }

        $operator && $this->groupOfRules[] = $operator;

        // Правила фильтрации не могут идти подряд без разделения AND/OR
        if (end($this->groupOfRules) instanceof FiltrationRule) {
            throw new FiltrationException('Cannot join two conditions without AND\OR');
        }

        $this->groupOfRules[] = $rule;

        if ($rule->getInputSource() instanceof UserInput) {
            $placeholder = Attribute::placeholder($rule->getEntityId(), $rule->getAttributeId());
            $this->userInputBindings[$placeholder] = $rule->getInputSource()->getValue();
        }

        return $this;
    }

    /**
     * @param int $nodeId
     * @param array $rowIds
     * @return $this
     */
    public function limitDataInputTo(int $nodeId, array $rowIds): self
    {
        $this->dataInputLimits[$nodeId] = $rowIds;

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getQuery(): string;

    /**
     * @return string
     * @throws InputTypeException
     * @throws TypeCastException
     */
    protected function buildWhere(): string
    {
        $chunks = [];

        foreach ($this->filtrationRules as $value) {
            if (is_array($value)) {
                $chunks[] = '(';
                foreach ($value as $v) {
                    if ($v instanceof FiltrationRule) {
                        $chunks[] = sprintf(
                            "%s %s %s",
                            Type::cast(
                                Attribute::path($v->getEntityId(), $v->getAttributeId()),
                                $v->getAttributeType()
                            ),
                            $v->getComprasionOperator(),
                            $this->buildInput($v)
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
     * @param FiltrationRule $rule
     * @return string
     * @throws InputTypeException
     * @throws TypeCastException
     */
    private function buildInput(FiltrationRule $rule)
    {
        $input = $rule->getInputSource();

        if ($input instanceof DataInput) {
            return sprintf(
                'ANY(SELECT %s FROM node_%d %s)',
                Type::cast($input->getSourceNodeColumn(), $rule->getAttributeType()),
                $input->getSourceNodeId(),
                isset($this->dataInputLimits[$input->getSourceNodeId()]) ? sprintf('WHERE row_id IN (%s)', join(',', $this->dataInputLimits[$input->getSourceNodeId()])) : ''
            );
        }

        if ($input instanceof UserInput) {
            return Type::cast(
                Attribute::placeholder($rule->getEntityId(), $rule->getAttributeId(), true),
                $rule->getAttributeType()
            );
        }

        throw new InputTypeException(sprintf('Unknown input source `%s`', get_class($rule->getInputSource())));
    }
}