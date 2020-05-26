<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Traits;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Blocks\ResultColumnMeta;
use Orderbynull\PgSqlBuilder\Actions\Blocks\Summary;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Trait ReturningAwareTrait
 * @package Orderbynull\PgSqlBuilder\Traits
 */
trait ReturningAwareTrait
{
    /**
     * @var array
     */
    private array $summarization = [];

    /**
     * @var bool
     */
    private bool $groupingUsed = false;

    /**
     * @var bool
     */
    private bool $aggFunctionsUsed = false;

    /**
     * @var array
     */
    private array $attributesToReturn = [];

    /**
     * @var array
     */
    private array $returningColumnsMeta = [];

    /**
     * @return Summary[]
     */
    private function getSummary(): array
    {
        return $this->summarization;
    }

    /**
     * @param int $index
     * @param int $entityId
     * @param string $attributeId
     * @return array
     */
    private function getAttributeSummaryMeta(int $index, int $entityId, string $attributeId): array
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
     * @param ResultColumnMeta $columnMeta
     */
    private function registerReturningAttribute(ResultColumnMeta $columnMeta): void
    {
        $this->returningColumnsMeta[(string)$columnMeta] = $columnMeta;
    }

    /**
     * @return string
     * @throws AttributeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    protected function buildReturning(): string
    {
        $chunks = [];
        $timesSeen = [];

        /** @var EntityAttribute $attribute */
        foreach ($this->attributesToReturn as $attribute) {
            isset($timesSeen[$attribute->attributeId]) ? $timesSeen[$attribute->attributeId]++ : $timesSeen[$attribute->attributeId] = 1;

            $attributePath = $attribute->getPath();
            $attributeAlias = $attribute->getPlaceholder();
            $attributeAlias = sprintf('%s_%d', $attributeAlias, $timesSeen[$attribute->attributeId]);

            [$attributeUsedInGrouping, $attributeAggFunction] = $this->getAttributeSummaryMeta(
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
                        $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute, $attributeAggFunction));
                    } else {
                        $chunks[] = sprintf('%s AS %s', Type::cast($attributePath, $attribute->attributeType), $attributeAlias);
                        $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute));
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
                    $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute, $attributeAggFunction));
                    break;

                case !$this->groupingUsed && !$this->aggFunctionsUsed:
                    $chunks[0] = sprintf('_%s.id AS row_id', $this->baseEntityId);
                    $chunks[] = sprintf('%s AS %s', Type::cast($attributePath, $attribute->attributeType), $attributeAlias);
                    $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute));
                    break;
            }
        }

        if (empty($chunks)) {
            $chunks[] = sprintf('_%d.id as row_id', $this->baseEntityId);
        }

        return join(', ', $chunks);
    }

    /**
     * @return array
     */
    private function getReturningColumnsMeta(): array
    {
        return array_values($this->returningColumnsMeta);
    }

    /**
     * @param EntityAttribute $attribute
     */
    public function addAttributeToReturn(EntityAttribute $attribute): void
    {
        $this->attributesToReturn[] = $attribute;
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
}