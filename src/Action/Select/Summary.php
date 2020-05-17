<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action\Select;

/**
 * Class Summary
 * @package Orderbynull\PgSqlBuilder\Action\Select
 */
class Summary
{
    /**
     * @var int
     */
    public int $entityId;

    /**
     * @var string
     */
    public string $attributeId;

    /**
     * @var bool
     */
    public bool $shouldGroup;

    /**
     * @var string|null
     */
    public ?string $aggFuncName;

    /**
     * @var string
     */
    public string $attributeType;

    /**
     * Summary constructor.
     * @param int $entityId
     * @param string $attributeId
     * @param bool $shouldGroup
     * @param string|null $aggFuncName
     * @param string $attributeType
     */
    public function __construct(int $entityId, string $attributeId, bool $shouldGroup, ?string $aggFuncName, string $attributeType)
    {
        $this->entityId = $entityId;
        $this->attributeId = $attributeId;
        $this->shouldGroup = $shouldGroup;
        $this->aggFuncName = $aggFuncName;
        $this->attributeType = $attributeType;
    }
}