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
    private int $sourceNodeId;

    /**
     * @var string
     */
    private string $sourceNodeColumn;

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

    /**
     * @return int
     */
    public function getSourceNodeId(): int
    {
        return $this->sourceNodeId;
    }

    /**
     * @return string
     */
    public function getSourceNodeColumn(): string
    {
        return $this->sourceNodeColumn;
    }
}