<?php

declare(strict_types=1);

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Select;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class SortingTest
 */
class SortingTest extends BaseTest
{
    /**
     * @return array
     */
    public function sortingDirectionProvider(): array
    {
        return [
            ['ASC', '[{"row_id":1}, {"row_id":2}, {"row_id":3}]'],
            ['DESC', '[{"row_id":3}, {"row_id":2}, {"row_id":1}]']
        ];
    }

    /**
     * @param string $direction
     * @param string $expectedJson
     * @throws AttributeException
     * @throws InputTypeException
     * @throws Throwable
     * @throws TypeCastException
     *
     * @dataProvider sortingDirectionProvider
     */
    public function testTheMostMinimalSortingWithoutAggregationDesc(string $direction, string $expectedJson): void
    {
        // arrange
        $sut = new Select(155);
        $sut->addSorting(new EntityAttribute(155, '02180769-544e-4a8e-84ef-5edbaadf8f69', Type::INTEGER), $direction);

        // act
        $query = $sut->getSqlQuery();

        // assert
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->jsonResult($query));
    }

    protected function up(): string
    {
        return <<<RAW
            CREATE TABLE entity_values
            (
                id bigserial not null constraint entity_values_pkey primary key,
                entity_id bigint not null,
                attributes jsonb not null,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0)
            );
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (1, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": 1}}', '2020-06-29 10:04:46', '2020-06-29 15:51:56', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (2, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": 2}}', '2020-06-29 10:04:46', '2020-06-29 15:51:56', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (3, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": 3}}', '2020-06-29 10:04:46', '2020-06-29 15:51:56', null);
        RAW;
    }
}