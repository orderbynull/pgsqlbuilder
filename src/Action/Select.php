<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

use Orderbynull\PgSqlBuilder\Action\Pieces\EntityAttribute;
use Orderbynull\PgSqlBuilder\Action\Pieces\Summary;

/**
 * Class Select
 * @package Orderbynull\PgSqlBuilder
 */
class Select extends Action
{
    /**
     * @var array
     */
    private array $summarization = [];

    /**
     * @var array
     */
    private array $returningAttributes = [];

    /**
     * @param EntityAttribute $attribute
     * @return $this
     */
    public function addReturningAttribute(EntityAttribute $attribute): self
    {
        $this->returningAttributes[] = $attribute;

        return $this;
    }

    /**
     * @param Summary $summary
     * @return $this
     */
    public function addSummary(Summary $summary): self
    {
        $this->summarization[] = $summary;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        $chunks = array_filter([
            sprintf('SELECT * FROM entity_values AS _%d', $this->baseEntityId),
            $this->buildWhere(),
            $this->buildJoins(),
            $this->buildGroupBy(),
            'ORDER BY id DESC',
            'LIMIT ALL'
        ]);

        return join(' ', $chunks);
    }

    private function buildJoins(): string
    {
        return '';
    }

    /**
     * @return string
     */
    private function buildGroupBy(): string
    {
        return '';
    }
}