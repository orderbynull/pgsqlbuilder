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
    private string $searchString = '';


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
        $subQuery  = $this->buildWhereSubQuery();
        $returning = $this->buildReturning();

        $where = sprintf('WHERE _%d.id IN (%s)', $this->baseEntityId, $subQuery);

        if(!empty($this->searchString)){
            $search = $this->buildSearchCondition($this->getResultColumnsMeta(), $this->searchString);
            $where .= " AND $search";
        }
        
        $chunks = [
            'SELECT',
            $returning,
            'FROM',
            sprintf('entity_values AS _%d', $this->baseEntityId),
            $this->buildJoins(),
            sprintf('WHERE _%d.id IN (%s)', $this->baseEntityId, $subQuery),
            $this->buildGroupBy(),
            $this->buildSorting(),
        ];

        return join(' ', array_filter($chunks));
    }

    /**
     * @param array $columns
     * @param string $searchString
     * @return string
     */
    private function buildSearchCondition(array $columns, string $searchString): string
    {
        $columns = array_map(
            function ($column) {
                return $column->columnId . '::text';
            },
            $columns
        );

        $columns = implode(',', $columns);
        $searchString = strtolower($searchString);

        return "LOWER(concat_ws(' ', $columns)) LIKE '%$searchString%'";
    }

    /**
     * @return string
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function buildWhereSubQuery(): string
    {
        return join(
            ' ',
            array_filter(
                [
                    'SELECT',
                    'id',
                    'FROM',
                    sprintf('entity_values AS _%d', $this->baseEntityId),
                    $this->buildWhere($this->baseEntityId),
                    $this->buildLimit(),
                    $this->buildOffset()
                ]
            )
        );
    }

    /**
     * @return string
     */
    private function buildLimit(): string
    {
        if ($this->limit > 0) {
            return 'LIMIT ' . $this->limit;
        }

        return '';
    }

    /**
     * @return string
     */
    private function buildOffset(): string
    {
        if ($this->offset > 0) {
            return 'OFFSET ' . $this->offset;
        }

        return '';
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

        return count($chunks) ? sprintf('ORDER BY %s', join(',', $chunks)) : '';
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
