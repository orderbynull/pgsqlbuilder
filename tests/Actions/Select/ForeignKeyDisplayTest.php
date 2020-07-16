<?php

declare(strict_types=1);

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Select;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class ForeignKeyDisplayTest
 */
class ForeignKeyDisplayTest extends BaseTest
{
    protected function up(): string
    {
        return <<<RAW
            CREATE OR REPLACE FUNCTION get_row_fk_attribute_as_string(row_id bigint, attribute_id text) RETURNS text AS $$
            DECLARE
                result text;
            BEGIN
                WITH source_row as (SELECT entity_id, (attributes -> attribute_id ->> 'value')::int AS ref_id
                                    FROM entity_values
                                    WHERE id = row_id),
                     row_attributes_values AS (SELECT entity_id, key AS id, value ->> 'value' AS value
                                               FROM entity_values, jsonb_each(attributes)
                                               WHERE id = (SELECT ref_id FROM source_row)),
                     row_attributes_meta AS (SELECT value ->> 'id' AS id, value ->> 'type' AS type
                                             FROM entities, jsonb_array_elements(attributes)
                                             WHERE id IN (SELECT entity_id
                                                          FROM row_attributes_values)),
                     row_attributes_full AS (SELECT id,
                                                    type,
                                                    CASE
                                                        WHEN type = 'date_time'
                                                            THEN to_char(value::timestamptz, 'DD Mon YYYY HH24:MI')
                                                        WHEN type IN ('enum', 'file')
                                                            THEN (SELECT string_agg(source, ', ') FROM jsonb_array_elements_text(value::jsonb) AS source)
                                                        ELSE value END
                                             FROM row_attributes_meta
                                                      JOIN row_attributes_values USING (id)
                                             WHERE id IN (SELECT jsonb_array_elements_text((value ->> 'attributesIds')::jsonb)
                                                          FROM entities, jsonb_array_elements(attributes)
                                                          WHERE id = (SELECT entity_id FROM source_row)
                                                            AND value ->> 'id' = attribute_id))
                SELECT array_to_string(array_agg(value), ', ', '-')
                FROM row_attributes_full
                INTO result;
            
                RETURN result;
            END
            $$
            LANGUAGE 'plpgsql';
        
            create table entities
            (
                id bigserial not null
                    constraint entities_pkey
                        primary key,
                user_id bigint not null,
                title varchar(255) not null,
                description varchar(255),
                attributes jsonb not null,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                application_id bigint
            );
            INSERT INTO public.entities (id, user_id, title, description, attributes, created_at, updated_at, deleted_at, application_id) VALUES (41, 6, 'Candidates', null, '[{"id": "50169244-bd50-4205-8a75-1d49f0a6e200", "name": "Candidate Name", "size": 255, "type": "string", "label": "Candidate Name", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4", "name": "Contact Number", "size": 255, "type": "string", "label": "Contact Number", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "729c824a-cef3-4f23-bc32-5f15c03efa34", "name": "Email Address", "size": 255, "type": "string", "label": "Email Address", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "5256e5e5-9913-4759-a8a8-80a23dbf5b53", "kind": "date", "name": "Date CV Received", "type": "date_time", "label": "Date CV Received", "default": null, "required": false}, {"id": "3896eed4-53c7-49a4-837c-28ec118639cb", "name": "Right to work", "type": "boolean", "label": "Right to work", "required": false, "trueLabel": null, "falseLabel": null}, {"id": "1568ca16-ac71-4caa-a0ff-edc6949a733f", "name": "Salary", "size": 255, "type": "string", "label": "Salary", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "7bd402db-40b1-4b5c-8bbb-822ab65af356", "name": "Notice Period", "size": 255, "type": "string", "label": "Notice Period", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "27d2e005-3773-411b-8171-7f47c958c8d2", "name": "Progress Application", "type": "boolean", "label": "Progress Application", "required": false, "trueLabel": null, "falseLabel": null}, {"id": "5cb15a70-9780-4f03-b93e-75974caf807f", "name": "Applied for Vacancy", "type": "foreign_key", "label": "Applied for Vacancy", "required": false, "refEntityId": 40, "attributesIds": ["a067d0eb-fa3a-4d7a-8bb7-db295a27b922", "56f8532c-e43d-4182-8e91-997ee9e308c6"]}, {"id": "306b366a-243b-4b35-b274-b6e7fcc6d9fd", "name": "Agency", "type": "foreign_key", "label": "Agency", "required": false, "refEntityId": 42, "attributesIds": ["84eb4f31-bdf1-4224-8517-ffcfeeb15d13"]}, {"id": "1291a5b4-edc5-4d07-8328-65e242676b7d", "name": "About You", "type": "text", "label": "About Me", "required": false}, {"id": "57c21079-2063-4004-9de0-9e91a989eef2", "name": "Where Do You Live", "size": 255, "type": "string", "label": "Where Do You Live", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "1b64c006-cfef-4157-9a25-543fb444e526", "kind": "Single value", "name": "Status", "type": "enum", "label": "Status", "options": ["Inbox", "Processed", "Spam", "Deleted"], "required": false}, {"id": "6f09b51e-a726-48d7-b0aa-e9e9a330e4df", "max": 25000, "name": "CV", "type": "file", "label": "CV", "mimes": ["pdf", "docx"], "required": false, "keepRevisions": true, "multipleUploads": true}]', '2020-07-14 12:56:01', '2020-07-14 12:56:01', null, 18);
            INSERT INTO public.entities (id, user_id, title, description, attributes, created_at, updated_at, deleted_at, application_id) VALUES (40, 6, 'Vacancies', null, '[{"id": "56f8532c-e43d-4182-8e91-997ee9e308c6", "kind": "Single value", "name": "Status", "type": "enum", "label": "Status", "options": ["Open", "Pending Approval", "Rejected", "Closed - Cancelled", "Closed - Fulfilled"], "required": false}, {"id": "a067d0eb-fa3a-4d7a-8bb7-db295a27b922", "name": "Position", "size": 255, "type": "string", "label": "Position", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "a139e5c6-2cd8-4af2-9382-3c27bc78d284", "name": "Salary Range", "size": 255, "type": "string", "label": "Salary Range", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "e0d7052c-e7a5-4e00-8574-da9f521ecaeb", "max": 25000, "name": "Job Description", "type": "file", "label": "Job Description", "mimes": ["pdf"], "required": false, "keepRevisions": true, "multipleUploads": false}]', '2020-07-14 12:50:09', '2020-07-14 12:50:09', null, 18);
        
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
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (75, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Deleted"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": null}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": true}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "Mueez Abdur-Rahman"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": "83"}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:02', '2020-07-15 15:16:19', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (74, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Spam"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": "72"}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": null}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "Arcadi Gonzalez Graells"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": null}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:01', '2020-07-15 15:29:19', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (77, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": "1"}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Deleted"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": "72"}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": false}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "Jonathan Joseph"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": null}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:02', '2020-07-15 15:13:40', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (76, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Spam"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": null}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": null}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "James Laverack"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": "130"}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:02', '2020-07-15 16:47:14', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (78, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Deleted"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": null}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": null}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "Carey Hiles"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": null}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:02', '2020-07-14 14:21:48', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (80, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Deleted"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": null}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": null}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "William Feglister"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": null}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:03', '2020-07-14 14:21:48', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (82, 41, '{"1291a5b4-edc5-4d07-8328-65e242676b7d": {"value": null}, "1568ca16-ac71-4caa-a0ff-edc6949a733f": {"value": null}, "1b64c006-cfef-4157-9a25-543fb444e526": {"value": ["Deleted"]}, "27d2e005-3773-411b-8171-7f47c958c8d2": {"value": null}, "306b366a-243b-4b35-b274-b6e7fcc6d9fd": {"value": null}, "3896eed4-53c7-49a4-837c-28ec118639cb": {"value": null}, "4e53d3b3-0ed9-48de-8797-8b3cb08d65e4": {"value": null}, "50169244-bd50-4205-8a75-1d49f0a6e200": {"value": "Mihaly Sallai"}, "5256e5e5-9913-4759-a8a8-80a23dbf5b53": {"value": null}, "57c21079-2063-4004-9de0-9e91a989eef2": {"value": null}, "5cb15a70-9780-4f03-b93e-75974caf807f": {"value": null}, "6f09b51e-a726-48d7-b0aa-e9e9a330e4df": {"value": null}, "729c824a-cef3-4f23-bc32-5f15c03efa34": {"value": null}, "7bd402db-40b1-4b5c-8bbb-822ab65af356": {"value": null}}', '2020-07-14 13:41:03', '2020-07-14 14:21:48', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (71, 40, '{"56f8532c-e43d-4182-8e91-997ee9e308c6": {"value": ["Pending Approval", "Deleted"]}, "a067d0eb-fa3a-4d7a-8bb7-db295a27b922": {"value": "Software Engineer (.NET)"}, "a139e5c6-2cd8-4af2-9382-3c27bc78d284": {"value": "£24-28k"}, "e0d7052c-e7a5-4e00-8574-da9f521ecaeb": {"value": ["56"]}}', '2020-07-14 12:52:57', '2020-07-15 15:20:28', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (83, 40, '{"56f8532c-e43d-4182-8e91-997ee9e308c6": {"value": ["Pending Approval", "Deleted"]}, "a067d0eb-fa3a-4d7a-8bb7-db295a27b922": {"value": "Front-End Developer"}, "a139e5c6-2cd8-4af2-9382-3c27bc78d284": {"value": "£1"}, "e0d7052c-e7a5-4e00-8574-da9f521ecaeb": {"value": null}}', '2020-07-14 15:32:50', '2020-07-14 15:33:23', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (106, 40, '{"56f8532c-e43d-4182-8e91-997ee9e308c6": {"value": ["Pending Approval", "Deleted"]}, "a067d0eb-fa3a-4d7a-8bb7-db295a27b922": {"value": "Head of Engineering"}, "a139e5c6-2cd8-4af2-9382-3c27bc78d284": {"value": "£30k"}, "e0d7052c-e7a5-4e00-8574-da9f521ecaeb": {"value": null}}', '2020-07-14 18:45:26', '2020-07-14 18:45:26', null);
            INSERT INTO public.entity_values (id, entity_id, attributes, created_at, updated_at, deleted_at) VALUES (130, 40, '{"56f8532c-e43d-4182-8e91-997ee9e308c6": {"value": ["Pending Approval", "Deleted"]}, "a067d0eb-fa3a-4d7a-8bb7-db295a27b922": {"value": "Head of Projects"}, "a139e5c6-2cd8-4af2-9382-3c27bc78d284": {"value": "£10k"}, "e0d7052c-e7a5-4e00-8574-da9f521ecaeb": {"value": null}}', '2020-07-15 15:19:00', '2020-07-15 15:19:00', null);
        RAW;
    }

    /**
     * @throws Throwable
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\AttributeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\InputTypeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    public function testForeignKeyDisplaysCorrectWhenReferencedRowHasArrayAttribute(): void
    {
        // arrange
        $select = new Select(41);
        $select->addAttributeToReturn(
            new EntityAttribute(41, '5cb15a70-9780-4f03-b93e-75974caf807f', Type::FOREIGN_KEY)
        );

        // act
        $query = $select->getSqlQuery();

        // assert
        $expected = <<<RAW
            [
              {
                "row_id": 75,
                "ent_41_attr_5cb15a70_9780_4f03_b93e_75974caf807f_1": "Pending Approval, Deleted, Front-End Developer"
              },
              {
                "row_id": 74,
                "ent_41_attr_5cb15a70_9780_4f03_b93e_75974caf807f_1": "-"
              },
              {
                "row_id": 77,
                "ent_41_attr_5cb15a70_9780_4f03_b93e_75974caf807f_1": "-"
              },
              {
                "row_id": 76,
                "ent_41_attr_5cb15a70_9780_4f03_b93e_75974caf807f_1": "Pending Approval, Deleted, Head of Projects"
              },
              {
                "row_id": 78,
                "ent_41_attr_5cb15a70_9780_4f03_b93e_75974caf807f_1": "-"
              },
              {
                "row_id": 80,
                "ent_41_attr_5cb15a70_9780_4f03_b93e_75974caf807f_1": "-"
              },
              {
                "row_id": 82,
                "ent_41_attr_5cb15a70_9780_4f03_b93e_75974caf807f_1": "-"
              }
            ]
        RAW;
        $this->assertJsonStringEqualsJsonString($expected, $this->jsonResult($query));
    }
}