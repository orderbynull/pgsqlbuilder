<?php

declare(strict_types=1);

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Select;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class SimplestCasesTest
 */
class SimplestCasesTest extends BaseTest
{
    /**
     * @throws Throwable
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function testTheMostMinimalSelectIsReadyToUse(): void
    {
        // arrange
        $sut = new Select(155);

        // act
        $query = $sut->getSqlQuery();

        // assert
        $this->assertJsonStringEqualsJsonString('[{"row_id":1}]', $this->jsonResult($query));
    }

    /**
     * @throws Throwable
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     *
     * @TODO: вынести тест аттрибута типа "файл" в отдельный метод, по аналогии с FK.
     */
    public function testEachAttributeTypeReturnedInProperFormat(): void
    {
        // arrange
        $sut = new Select(155);
        $sut->addAttributeToReturn(new EntityAttribute(155, '57e77c4c-1987-439e-9460-30c8e5718352', Type::FILE));
        $sut->addAttributeToReturn(new EntityAttribute(155, 'a1360fed-0cd0-4203-b5ad-5fb3d3be94e1', Type::ENUM));
        $sut->addAttributeToReturn(new EntityAttribute(155, '66660fed-0cd0-4203-b5ad-5fb3d3be94e1', Type::SIGN));
        $sut->addAttributeToReturn(new EntityAttribute(155, 'b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8', Type::TEXT));
        $sut->addAttributeToReturn(new EntityAttribute(155, 'be02eebb-fd15-4a14-aed6-a92b4f8e5ebc', Type::STRING));
        $sut->addAttributeToReturn(new EntityAttribute(155, '02180769-544e-4a8e-84ef-5edbaadf8f69', Type::INTEGER));
        $sut->addAttributeToReturn(new EntityAttribute(155, '13176abe-5142-4876-878f-f1cfe7a71ae3', Type::DECIMAL));
        $sut->addAttributeToReturn(new EntityAttribute(155, '48ecc249-aa74-40e2-bf25-870a806c5cf1', Type::BOOLEAN));
        $sut->addAttributeToReturn(new EntityAttribute(155, '8b95c9ee-0e2a-48b7-a321-b2d72e4a286a', Type::DATETIME));
        $sut->addAttributeToReturn(new EntityAttribute(155, 'a9f7ff25-3109-48d4-a185-13389c829f88', Type::DATETIME));

        // act
        $query = $sut->getSqlQuery();

        // assert
        $expected = <<<RAW
        [
            {
                "row_id":1, 
                "ent_155_attr_57e77c4c_1987_439e_9460_30c8e5718352_1": [
                                                                        {
                                                                            "id":144,
                                                                            "size":580667, 
                                                                            "mimetype":"image/png", 
                                                                            "name":"Profile pic.png", 
                                                                            "createdAt":"2020-06-29T07:28:19", 
                                                                            "updatedAt":"2020-06-29T07:28:19"
                                                                        }, 
                                                                        {
                                                                            "id":665, 
                                                                            "size":999, 
                                                                            "name":"lol.png", 
                                                                            "mimetype":"image/png", 
                                                                            "createdAt":"2020-06-29T07:31:40", 
                                                                            "updatedAt":"2020-06-29T07:31:40"
                                                                        }
                                                                       ],
                "ent_155_attr_a1360fed_0cd0_4203_b5ad_5fb3d3be94e1_1": ["Value1", "Value2"],
                "ent_155_attr_66660fed_0cd0_4203_b5ad_5fb3d3be94e1_1": [
                                                                        {
                                                                            "id":665, 
                                                                            "size":999, 
                                                                            "name":"lol.png", 
                                                                            "mimetype":"image/png", 
                                                                            "createdAt":"2020-06-29T07:31:40", 
                                                                            "updatedAt":"2020-06-29T07:31:40"
                                                                        }
                                                                       ],
                "ent_155_attr_b8a758d1_0ee4_4d02_a8a5_b306b5b0e3b8_1": "text-value",
                "ent_155_attr_be02eebb_fd15_4a14_aed6_a92b4f8e5ebc_1": "string-value",
                "ent_155_attr_02180769_544e_4a8e_84ef_5edbaadf8f69_1": 7456,
                "ent_155_attr_13176abe_5142_4876_878f_f1cfe7a71ae3_1": 99.111,
                "ent_155_attr_48ecc249_aa74_40e2_bf25_870a806c5cf1_1": false,
                "ent_155_attr_8b95c9ee_0e2a_48b7_a321_b2d72e4a286a_1": "2020-06-13T00:00:00.000Z",
                "ent_155_attr_a9f7ff25_3109_48d4_a185_13389c829f88_1": null
            }
        ]
        RAW;
        $this->assertJsonStringEqualsJsonString($expected, $this->jsonResult($query));
    }

    protected function up(): string
    {
        return <<<RAW
            CREATE TABLE files
            (
                id bigserial not null constraint files_pkey primary key,
                fileable_type varchar(255),
                fileable_id bigint,
                name varchar(255) not null,
                path varchar(255) not null,
                mimetype varchar(255) not null,
                size integer not null,
                hash varchar(255) not null,
                created_at timestamp(0),
                updated_at timestamp(0),
                entity_attribute_id varchar(255),
                deleted_at timestamp(0)
            );
            INSERT INTO public.files (id, fileable_type, fileable_id, name, path, mimetype, size, hash, created_at, updated_at, entity_attribute_id, deleted_at) VALUES (144, 'user', 88, 'Profile pic.png', 'original/42_88_961086c3-a82d-49e7-9c85-b40d2343b800.png', 'image/png', 580667, '"7be9941dc7e545e468062275f65d7b5f"', '2020-06-29 07:28:19', '2020-06-29 07:28:19', null, null);
            INSERT INTO public.files (id, fileable_type, fileable_id, name, path, mimetype, size, hash, created_at, updated_at, entity_attribute_id, deleted_at) VALUES (665, 'user', 35, 'lol.png', 'original/36_35_8eb70f1f-ece8-483a-8d83-b1c2ed8390fe.png', 'image/png', 999, '"7be9941dc7e545e468062275f65d7b5f"', '2020-06-29 07:31:40', '2020-06-29 07:31:40', null, null);

            CREATE TABLE entity_values
            (
                id bigserial not null constraint entity_values_pkey primary key,
                entity_id bigint not null,
                attributes jsonb not null,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0)
            );
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (1, 155, '{"02180769-544e-4a8e-84ef-5edbaadf8f69": {"value": 7456}, "13176abe-5142-4876-878f-f1cfe7a71ae3": {"value": 99.111}, "48ecc249-aa74-40e2-bf25-870a806c5cf1": {"value": false}, "57e77c4c-1987-439e-9460-30c8e5718352": {"value": [144, 665]}, "8b95c9ee-0e2a-48b7-a321-b2d72e4a286a": {"value": "2020-06-13T00:00:00.000Z"}, "a1360fed-0cd0-4203-b5ad-5fb3d3be94e1": {"value": ["Value1", "Value2"]}, "66660fed-0cd0-4203-b5ad-5fb3d3be94e1": {"value": [665]}, "a9f7ff25-3109-48d4-a185-13389c829f88": {"value": null}, "ac67dc4c-4033-49a9-91f3-7029bc053ba0": {"value": null}, "b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8": {"value": "text-value"}, "be02eebb-fd15-4a14-aed6-a92b4f8e5ebc": {"value": "string-value"}}', '2020-06-29 10:04:46', '2020-06-29 15:51:56', null);
        RAW;
    }
}