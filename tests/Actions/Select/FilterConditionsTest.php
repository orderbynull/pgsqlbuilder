<?php

declare(strict_types=1);

use Orderbynull\PgSqlBuilder\Actions\Blocks\Condition;
use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Select;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class FilterConditionsTest
 */
class FilterConditionsTest extends BaseTest
{
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
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (74, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Spam"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": "72"}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": null}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "Arcadi Gonzalez Graells"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": null}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:01', '2020-07-15 15:29:19', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (75, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Deleted"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": null}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": true}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "Mueez Abdur-Rahman"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": "83"}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:02', '2020-07-15 15:16:19', null);
        RAW;
    }

    /**
     * @throws Throwable
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function testSettingDifferentValuesForSameAttributeAllowed(): void
    {
        // arrange
        $enumAttribute = new EntityAttribute(41, '1b64c006-cfef-4157-9a25-543fb444e526', Type::ENUM);
        $select = new Select(41);
        $select->addAttributeToReturn($enumAttribute);
        $select->openConditionsGroup();
        $select->addCondition(null, new Condition($enumAttribute, '=', new UserInput(['Deleted'])));
        $select->addCondition('OR', new Condition($enumAttribute, '=', new UserInput(['Spam'])));
        $select->closeConditionsGroup();

        // act
        $query = strtr($select->getSqlQuery(), $select->getUserInputBindings());

        // assert
        $expected = <<<RAW
            [
              {
                "row_id": 74,
                "ent_41_attr_1b64c006_cfef_4157_9a25_543fb444e526_1": ["Spam"]
              },
              {
                "row_id": 75,
                "ent_41_attr_1b64c006_cfef_4157_9a25_543fb444e526_1": ["Deleted"]
              }
            ]
        RAW;

        $this->assertJsonStringEqualsJsonString($expected, $this->jsonResult($query));
    }
}