<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Input;

/**
 * Class DataInput
 * @package Orderbynull\PgSqlBuilder\Input
 */
class DataInput implements InputInterface
{
    /**
     * @var int
     */
    public int $sourceNodeId;

    /**
     * @var string
     */
    public string $sourceNodeColumn;

    /**
     * DataInput constructor.
     * @param int $sourceNodeId
     * @param string $sourceNodeColId
     */
    public function __construct(int $sourceNodeId, string $sourceNodeColId)
    {
        $this->sourceNodeId = $sourceNodeId;
        $this->sourceNodeColumn = $sourceNodeColId;
    }
}