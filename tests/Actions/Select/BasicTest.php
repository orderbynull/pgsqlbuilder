<?php

declare(strict_types=1);

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Select;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class BasicTest
 */
class BasicTest extends BaseTest
{
    protected function up(): string
    {
        return <<<RAW
            create table entity_values
            (
                id bigserial not null
                    constraint entity_values_pkey
                        primary key,
                entity_id bigint not null,
                attributes jsonb not null,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0)
            );
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (1, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": 7456}, "13176abe-5142-4876-878f-f1cfe7a71ae3": {"value": null}, "48ecc249-aa74-40e2-bf25-870a806c5cf1": {"value": "0"}, "57e77c4c-1987-439e-9460-30c8e5718352": {"value": null}, "8b95c9ee-0e2a-48b7-a321-b2d72e4a286a": {"value": "2020-06-13T00:00:00.000Z"}, "a1360fed-0cd0-4203-b5ad-5fb3d3be94e1": {"value": null}, "a9f7ff25-3109-48d4-a185-13389c829f88": {"value": null}, "ac67dc4c-4033-49a9-91f3-7029bc053ba0": {"value": null}, "b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8": {"value": null}, "be02eebb-fd15-4a14-aed6-a92b4f8e5ebc": {"value": "22"}}', '2020-06-29 10:04:46', '2020-06-29 15:51:56', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (2, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": "7458"}, "13176abe-5142-4876-878f-f1cfe7a71ae3": {"value": null}, "48ecc249-aa74-40e2-bf25-870a806c5cf1": {"value": 4340}, "57e77c4c-1987-439e-9460-30c8e5718352": {"value": null}, "8b95c9ee-0e2a-48b7-a321-b2d72e4a286a": {"value": "2020-06-13T00:00:00.000Z"}, "a1360fed-0cd0-4203-b5ad-5fb3d3be94e1": {"value": null}, "a9f7ff25-3109-48d4-a185-13389c829f88": {"value": null}, "ac67dc4c-4033-49a9-91f3-7029bc053ba0": {"value": null}, "b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8": {"value": null}, "be02eebb-fd15-4a14-aed6-a92b4f8e5ebc": {"value": "glass45"}}', '2020-06-29 08:40:05', '2020-06-30 10:35:04', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (3, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": 7457}, "13176abe-5142-4876-878f-f1cfe7a71ae3": {"value": null}, "48ecc249-aa74-40e2-bf25-870a806c5cf1": {"value": 363}, "57e77c4c-1987-439e-9460-30c8e5718352": {"value": null}, "8b95c9ee-0e2a-48b7-a321-b2d72e4a286a": {"value": "2020-06-13T19:03:00.000Z"}, "a1360fed-0cd0-4203-b5ad-5fb3d3be94e1": {"value": null}, "a9f7ff25-3109-48d4-a185-13389c829f88": {"value": null}, "ac67dc4c-4033-49a9-91f3-7029bc053ba0": {"value": null}, "b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8": {"value": null}, "be02eebb-fd15-4a14-aed6-a92b4f8e5ebc": {"value": "param pam pam"}}', '2020-06-29 09:14:34', '2020-06-30 16:45:24', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (4, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": "7454"}, "13176abe-5142-4876-878f-f1cfe7a71ae3": {"value": null}, "48ecc249-aa74-40e2-bf25-870a806c5cf1": {"value": "0"}, "57e77c4c-1987-439e-9460-30c8e5718352": {"value": null}, "8b95c9ee-0e2a-48b7-a321-b2d72e4a286a": {"value": "2020-06-13T08:00:00.000Z"}, "a1360fed-0cd0-4203-b5ad-5fb3d3be94e1": {"value": null}, "a9f7ff25-3109-48d4-a185-13389c829f88": {"value": null}, "ac67dc4c-4033-49a9-91f3-7029bc053ba0": {"value": null}, "b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8": {"value": null}, "be02eebb-fd15-4a14-aed6-a92b4f8e5ebc": {"value": "catish4"}}', '2020-06-29 08:40:25', '2020-06-30 16:45:43', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (5, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": 7458}, "13176abe-5142-4876-878f-f1cfe7a71ae3": {"value": null}, "48ecc249-aa74-40e2-bf25-870a806c5cf1": {"value": "0"}, "57e77c4c-1987-439e-9460-30c8e5718352": {"value": null}, "8b95c9ee-0e2a-48b7-a321-b2d72e4a286a": {"value": "2020-06-30T12:22:00.000Z"}, "a1360fed-0cd0-4203-b5ad-5fb3d3be94e1": {"value": null}, "a9f7ff25-3109-48d4-a185-13389c829f88": {"value": null}, "ac67dc4c-4033-49a9-91f3-7029bc053ba0": {"value": null}, "b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8": {"value": null}, "be02eebb-fd15-4a14-aed6-a92b4f8e5ebc": {"value": "driftwood"}}', '2020-06-29 08:53:44', '2020-07-02 14:12:33', null);
        RAW;
    }

    /**
     * @throws Throwable
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\AttributeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\InputTypeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    public function testTheMostMinimalSelectReturnsOnlyRowId(): void
    {
        // arrange
        $select = new Select(155);

        // act
        $query = $select->getSqlQuery();

        // assert
        $expected = <<<RAW
        [
            {"row_id":1},
            {"row_id":2},
            {"row_id":3},
            {"row_id":4},
            {"row_id":5}
        ]
        RAW;
        $this->assertJsonStringEqualsJsonString($expected, $this->jsonResult($query));
    }

    public function testSelectRespectsAttributesToReturn(): void
    {
        // arrange
        $select = new Select(155);
        $select->addAttributeToReturn(
            new EntityAttribute(155, '02180769-544e-4a8e-84ef-5edbaadf8f69', Type::INTEGER)
        );

        // act
        $query = $select->getSqlQuery();

        // assert
        $expected = <<<RAW
        [
            {"row_id":1, "ent_155_attr_02180769_544e_4a8e_84ef_5edbaadf8f69_1": 7456},
            {"row_id":2, "ent_155_attr_02180769_544e_4a8e_84ef_5edbaadf8f69_1": 7458},
            {"row_id":3, "ent_155_attr_02180769_544e_4a8e_84ef_5edbaadf8f69_1": 7457},
            {"row_id":4, "ent_155_attr_02180769_544e_4a8e_84ef_5edbaadf8f69_1": 7454},
            {"row_id":5, "ent_155_attr_02180769_544e_4a8e_84ef_5edbaadf8f69_1": 7458}
        ]
        RAW;
        $this->assertJsonStringEqualsJsonString($expected, $this->jsonResult($query));
    }
}