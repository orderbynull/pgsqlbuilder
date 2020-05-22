<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Actions\Blocks\ResultColumnMeta;

/**
 * Class AbstractAction
 * @package Orderbynull\PgSqlBuilder\Actions
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
     * AbstractAction constructor.
     * @param int $baseEntityId
     */
    public function __construct(int $baseEntityId)
    {
        $this->baseEntityId = $baseEntityId;
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
    abstract public function getSqlQuery(): string;

    /**
     * @return array
     */
    abstract public function getUserInputBindings(): array;

    /**
     * @return ResultColumnMeta[]
     */
    abstract public function getResultColumnsMeta(): array;
}