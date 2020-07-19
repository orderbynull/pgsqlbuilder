<?php

declare(strict_types=1);

use Orderbynull\PgSqlBuilder\Actions\Blocks\Condition;
use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Select;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\FiltrationException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class FilterConditionsTest
 */
class FilterConditionsTest extends BaseTest
{
    /**
     * @throws Throwable
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function testSettingDifferentValuesForSameAttributeWithinSameGroupAllowed(): void
    {
        // arrange
        $enumAttribute = new EntityAttribute(41, '1b64c006-cfef-4157-9a25-543fb444e526', Type::ENUM);

        $sut = new Select(41);
        $sut->addAttributeToReturn($enumAttribute);
        $sut->openConditionsGroup();
        $sut->addCondition(null, new Condition($enumAttribute, '=', new UserInput(['Deleted'])));
        $sut->addCondition('OR', new Condition($enumAttribute, '=', new UserInput(['Spam'])));
        $sut->closeConditionsGroup();

        // act
        $query = strtr($sut->getSqlQuery(), $sut->getUserInputBindings());

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

    /**
     * @throws Throwable
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function testSettingDifferentValuesForSameAttributeWithinDifferentGroupsAllowed(): void
    {
        // arrange
        $enumAttribute = new EntityAttribute(41, '1b64c006-cfef-4157-9a25-543fb444e526', Type::ENUM);

        $sut = new Select(41);
        $sut->addAttributeToReturn($enumAttribute);

        $sut->openConditionsGroup();
        $sut->addCondition(null, new Condition($enumAttribute, '=', new UserInput(['Deleted'])));
        $sut->closeConditionsGroup();

        $sut->openConditionsGroup('OR');
        $sut->addCondition(null, new Condition($enumAttribute, '=', new UserInput(['Spam'])));
        $sut->closeConditionsGroup();

        // act
        $query = strtr($sut->getSqlQuery(), $sut->getUserInputBindings());

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

    /**
     * @throws AttributeException
     * @throws InputTypeException
     * @throws Throwable
     * @throws TypeCastException
     * @throws FiltrationException
     */
    public function testFilteringEnumWithNotEqualOperatorSupported(): void
    {
        // arrange
        $enumAttribute = new EntityAttribute(41, '1b64c006-cfef-4157-9a25-543fb444e526', Type::ENUM);

        $sut = new Select(41);
        $sut->addAttributeToReturn($enumAttribute);

        $sut->openConditionsGroup();
        $sut->addCondition(null, new Condition($enumAttribute, '<>', new UserInput(['Pending'])));
        $sut->closeConditionsGroup();

        // act
        $query = strtr($sut->getSqlQuery(), $sut->getUserInputBindings());

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
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (74, 41, '{"1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Spam"]}}', '2020-07-14 13:41:01', '2020-07-15 15:29:19', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (75, 41, '{"1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Deleted"]}}', '2020-07-14 13:41:02', '2020-07-15 15:16:19', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (76, 41, '{"1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Pending", "WIP"]}}', '2020-07-14 13:41:02', '2020-07-15 15:16:19', null);
        RAW;
    }
}