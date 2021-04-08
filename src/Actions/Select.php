<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Blocks\Join;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Traits\ReturningAwareTrait;
use Orderbynull\PgSqlBuilder\Traits\WhereAwareTrait;
use Orderbynull\PgSqlBuilder\Utils\Type;
use Porto\Containers\EAV\Enums\EntityAttributeTypeEnum;

/**
 * Class Select
 * @package Orderbynull\PgSqlBuilder\Actions
 */
class Select extends AbstractAction
{
    use WhereAwareTrait;
    use ReturningAwareTrait;

    /**
     * @var array
     */
    private array $joins = [];

    /**
     * @var array
     */
    private array $sorting = [];

    /**
     * @var int
     */
    private int $limit = 0;

    /**
     * @var int
     */
    private int $offset = 0;

    /**
     * @var string
     */
    protected string $searchString = '';

    /**
     * @param Join $join
     * @return $this|void
     */
    public function addJoin(Join $join): void
    {
        $this->joins[] = $join;
    }

    /**
     * @param EntityAttribute $attribute
     * @param string $direction
     */
    public function addSorting(EntityAttribute $attribute, string $direction): void
    {
        $this->sorting[] = [$attribute, $direction];
    }

    /**
     * @param string $searchString
     */
    public function addSearchString(string $searchString): void
    {
        $this->searchString = $searchString;
    }

    /**
     * @param int $limit
     */
    public function addLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @param int $offset
     */
    public function addOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function getSqlQuery(): string
    {
        $returning = $this->buildReturning();
        $where = $this->buildWhere($this->baseEntityId);

        if (!empty($this->searchString)) {
            $condition = $this->buildSearchCondition($this->getResultColumnsMeta(), $this->searchString);

            if ($condition) {
                $where .= ' AND ' . $condition;
            }
        }

        $chunks = [
            'SELECT',
            sprintf('_%d.id', $this->baseEntityId),
            'FROM',
            sprintf('entity_values AS _%d', $this->baseEntityId),
            $this->buildJoins(),
            $where,
            $this->buildGroupBy(),
        ];

        if ($this->limit) {
            $chunks[] = sprintf('LIMIT %d', $this->limit);
        }

        if ($this->offset) {
            $chunks[] = sprintf('OFFSET %d', $this->offset);
        }

        $subQuery = $this->createSqlQuery($chunks);

        $sql = $this->createSqlQuery(
            [
                'SELECT',
                $returning,
                'FROM',
                sprintf('entity_values AS _%d', $this->baseEntityId),
                $this->buildJoins(),
                sprintf('WHERE _%d.id IN (%s)', $this->baseEntityId, $subQuery),
                $this->buildGroupBy(),
                $this->buildSorting(),
            ]
        );

        return $this->prepareSqlQuery($sql);
    }

    /**
     * @param array $columns
     * @param string $searchString
     * @return string|null
     */
    protected function buildSearchCondition(array $columns, string $searchString): ?string
    {
        $attrs = [];
        $searchString = strtolower($searchString);

        $prefix = sprintf('_%d.attributes->', $this->baseEntityId);
        $postfix = '->>\'value\' ';

        foreach ($columns as $column) {
            if ($column->attribute->attributeType === EntityAttributeTypeEnum::FOREIGN_KEY) {
                continue;
            }

            $attrs[] = '(' . $prefix . "'{$column->attribute->attributeId}'" . $postfix . ')::text' . " ILIKE '%$this->searchString%'";
        }

        if (count($attrs)) {
            return '(' . implode(' OR ', $attrs) . ')';
        }
    }

    /**
     * @param array $chunks
     * @return string
     */
    protected function createSqlQuery(array $chunks = []): string
    {
        return join(' ', array_filter($chunks));
    }

    /**
     * @param string $sql
     * @return string
     */
    protected function prepareSqlQuery(string $sql): string
    {
        return strtr(
            $sql,
            array_map(
                function ($value) {
                    if (is_bool($value)) {
                        return (int)$value;
                    }
                    return $value;
                },
                $this->getUserInputBindings()
            )
        );
    }

    /**
     * @return string
     */
    protected function buildJoins(): string
    {
        $chunks = [];

        /** @var Join $join */
        foreach ($this->joins as $join) {
            $chunks[] = sprintf(
                "LEFT JOIN entity_values AS _%d ON _%d.entity_id = %d AND (_%d.attributes->'%s'->>'value')::int = _%d.id",
                $join->joinedEntityId,
                $join->masterEntityId,
                $join->masterEntityId,
                $join->masterEntityId,
                $join->joinAttributeId,
                $join->joinedEntityId,
            );
        }

        return join(' ', $chunks);
    }

    /**
     * @return string
     * @throws TypeCastException
     */
    protected function buildGroupBy(): string
    {
        $chunks = [];

        foreach ($this->getSummary() as $summary) {
            if (!$summary->shouldGroup) {
                continue;
            }

            $chunks[] = Type::cast(
                $summary->attribute->getValue(),
                $summary->attribute->attributeType
            );
        }

        return count($chunks) ? sprintf('GROUP BY %s', join(',', $chunks)) : '';
    }

    /**
     * @return string
     */
    protected function buildSorting(): string
    {
        $chunks = [];

        /** @var array $sorting */
        foreach ($this->sorting as $sorting) {
            list($attribute, $direction) = $sorting;

            $chunks[] = sprintf(
                '%s %s',
                Type::cast($attribute->getValue(), $attribute->attributeType),
                strtoupper($direction)
            );
        }

        $default = sprintf('ORDER BY _%d.id ASC', $this->baseEntityId);

        return count($chunks) ? sprintf('ORDER BY %s', join(',', $chunks)) : $default;
    }

    /**
     * @inheritDoc
     */
    public function getUserInputBindings(): array
    {
        return $this->conditionsUserInputs;
    }

    /**
     * @return array
     */
    public function getResultColumnsMeta(): array
    {
        return $this->getReturningColumnsMeta();
    }
}
