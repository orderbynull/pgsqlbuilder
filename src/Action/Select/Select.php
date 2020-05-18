<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action\Select;

use Orderbynull\PgSqlBuilder\Action\AbstractAction;
use Orderbynull\PgSqlBuilder\Action\EntityAttribute;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Select
 * @package Orderbynull\PgSqlBuilder
 */
class Select extends AbstractAction
{
    /**
     * @var array
     */
    private array $joins = [];

    /**
     * @var bool
     */
    private bool $groupingUsed = false;

    /**
     * @var array
     */
    private array $summarization = [];

    /**
     * @var bool
     */
    private bool $aggFunctionsUsed = false;

    /**
     * @var array
     */
    private array $returningAttributes = [];

    /**
     * @param EntityAttribute $attribute
     */
    public function addReturningAttribute(EntityAttribute $attribute): void
    {
        $this->returningAttributes[] = $attribute;
    }

    /**
     * @param Summary $summary
     */
    public function addSummary(Summary $summary): void
    {
        $this->summarization[] = $summary;

        !$this->groupingUsed && $this->groupingUsed = $summary->shouldGroup;
        !$this->aggFunctionsUsed && $this->aggFunctionsUsed = !empty($summary->aggFuncName);
    }

    /**
     * @param Join $join
     * @return $this|void
     */
    public function addJoin(Join $join): void
    {
        $this->joins[] = $join;
    }

    /**
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function getQuery(): string
    {
        $chunks = array_filter([
            'SELECT',
            $this->buildFields(),
            'FROM',
            'entity_values',
            sprintf('AS _%d', $this->baseEntityId),
            $this->buildJoins(),
            $this->buildWhere(),
            $this->buildGroupBy(),
            'LIMIT ALL'
        ]);

        return join(' ', $chunks);
    }

    /**
     * @return string
     * @throws AttributeException
     * @throws TypeCastException
     */
    private function buildFields(): string
    {
        $chunks = [];
        $timesSeen = [];

        /** @var EntityAttribute $attribute */
        foreach ($this->returningAttributes as $attribute) {
            isset($timesSeen[$attribute->attributeId]) ? $timesSeen[$attribute->attributeId]++ : $timesSeen[$attribute->attributeId] = 1;

            $attributePath = $attribute->getPath();
            $attributeAlias = $attribute->getPlaceholder();
            $attributeAlias = sprintf('%s_%d', $attributeAlias, $timesSeen[$attribute->attributeId]);

            [$attributeUsedInGrouping, $attributeAggFunction] = $this->attributeSummaryMeta(
                $timesSeen[$attribute->attributeId],
                $attribute->entityId,
                $attribute->attributeId
            );

            switch (true) {
                case $this->groupingUsed:
                    if (!$attributeUsedInGrouping && !$attributeAggFunction) {
                        throw new AttributeException(
                            sprintf(
                                'Attribute %d.%s must be either used in grouping or have aggregate function',
                                $attribute->entityId,
                                $attribute->attributeId
                            )
                        );
                    }
                    $fieldsDenseRank = [];
                    /** @var Summary $summary */
                    foreach ($this->summarization as $summary) {
                        if ($summary->shouldGroup === true) {
                            $fieldsDenseRank[] = Type::cast(
                                $summary->attribute->getPath(),
                                $summary->attribute->attributeType
                            );
                        }
                    }
                    $chunks[0] = sprintf('dense_rank() over (order by %s) AS row_id', join(', ', $fieldsDenseRank));
                    if ($attributeAggFunction) {
                        $chunks[] = sprintf('%s(%s) AS %s', $attributeAggFunction, Type::cast($attributePath, $attribute->attributeType), $attributeAlias);
                    } else {
                        $chunks[] = sprintf('%s AS %s', Type::cast($attributePath, $attribute->attributeType), $attributeAlias);
                    }
                    break;

                case !$this->groupingUsed && $this->aggFunctionsUsed:
                    if (!$attributeAggFunction) {
                        throw new AttributeException(
                            sprintf(
                                'Aggregate function must be set for attribute %d.%s',
                                $attribute->entityId,
                                $attribute->attributeId
                            )
                        );
                    }
                    $chunks[0] = '1 AS row_id';
                    $chunks[] = sprintf('%s(%s) AS %s', $attributeAggFunction, Type::cast($attributePath, $attribute->attributeType), $attributeAlias);
                    break;

                case !$this->groupingUsed && !$this->aggFunctionsUsed:
                    $chunks[0] = sprintf('_%s.id AS row_id', $this->baseEntityId);
                    $chunks[] = sprintf('%s AS %s', Type::cast($attributePath, $attribute->attributeType), $attributeAlias);
                    break;
            }
        }

        return join(', ', $chunks);
    }

    /**
     * @param int $index
     * @param int $entityId
     * @param string $attributeId
     * @return array
     */
    private function attributeSummaryMeta(int $index, int $entityId, string $attributeId): array
    {
        $data = [];

        /** @var Summary $summary */
        foreach ($this->summarization as $summary) {
            if ($summary->attribute->entityId == $entityId && $summary->attribute->attributeId == $attributeId) {
                $data[] = [$summary->shouldGroup, $summary->aggFuncName ?? null];
            }
        }

        if (count($data) > 0) {
            return $data[$index - 1];
        }

        return [false, null];
    }

    /**
     * @return string
     */
    private function buildJoins(): string
    {
        $chunks = [];

        /** @var Join $join */
        foreach ($this->joins as $join) {
            $chunks[] = sprintf(
                "JOIN entity_values AS _%d ON _%d.entity_id = %d AND (_%d.attributes->'%s'->>'value')::int = _%d.id",
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
    private function buildGroupBy(): string
    {
        $chunks = [];

        /** @var Summary $summary */
        foreach ($this->summarization as $summary) {
            if (!$summary->shouldGroup) {
                continue;
            }

            $chunks[] = Type::cast(
                $summary->attribute->getPath(),
                $summary->attribute->attributeType
            );
        }

        return count($chunks) ? sprintf('GROUP BY %s', join(',', $chunks)) : '';
    }
}