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
            create table applications
            (
                id bigserial not null
                    constraint projects_pkey
                        primary key,
                title varchar(255),
                subdomain varchar(255) not null
                    constraint projects_subdomain_unique
                        unique,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0)
            );
            INSERT INTO public.applications (id, title, subdomain, created_at, updated_at, deleted_at) VALUES (48, 'milliseconds_matter', 'milliseconds_matter', '2020-06-29 08:27:39', '2020-06-29 08:27:39', null);

            create table users
            (
                id bigserial not null
                    constraint users_pkey
                        primary key,
                name varchar(255) not null,
                email varchar(255) not null,
                email_verified_at timestamp(0),
                password varchar(255) not null,
                remember_token varchar(100),
                deleted_at timestamp(0),
                created_at timestamp(0),
                updated_at timestamp(0),
                current_application_id bigint,
                provider varchar(255),
                provider_id varchar(255),
                avatar varchar(255),
                status varchar(255) default 'active'::character varying not null
                    constraint users_status_check
                        check ((status)::text = ANY (ARRAY[('invited'::character varying)::text, ('active'::character varying)::text])),
                phone varchar(255),
                about text
            );
            INSERT INTO public.users (id, name, email, email_verified_at, password, remember_token, deleted_at, created_at, updated_at, current_application_id, provider, provider_id, avatar, status, phone, about) VALUES (3, 'denis', 'vasya@pipkin.ru', null, '2y10l63n6xxfBp8oM2eXYVaqg.71kclM.oNK0VSPxHGpZXQVJTZTdbRDO', null, null, '2020-03-11 13:37:52', '2020-07-10 10:57:03', 48, null, null, null, 'active', null, 'bugs lover');
        
            create table entities
            (
                id bigserial not null
                    constraint entities_pkey
                        primary key,
                user_id bigint not null
                    constraint entities_user_id_foreign
                        references users
                            on update cascade on delete restrict,
                title varchar(255) not null,
                description varchar(255),
                attributes jsonb not null,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                application_id bigint
                    constraint entities_application_id_foreign
                        references applications
            );
            INSERT INTO public.entities (id, user_id, title, description, attributes, created_at, updated_at, deleted_at, application_id) VALUES (155, 3, 'Test', null, '[{"id": "be02eebb-fd15-4a14-aed6-a92b4f8e5ebc", "name": "title", "size": 255, "type": "string", "label": "title", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "02180769-544e-4a8e-84ef-5edbaadf8f69", "name": "fk_attribute", "type": "foreign_key", "label": "fk_attribute", "required": false, "refEntityId": 156, "attributesIds": ["a93fcafd-0b42-4783-ac10-e6776589f51f"]}, {"id": "48ecc249-aa74-40e2-bf25-870a806c5cf1", "max": 99999, "min": 0, "name": "wholenumber_field", "type": "integer", "label": "wholenumber_field", "unique": false, "default": 0, "required": false}, {"id": "13176abe-5142-4876-878f-f1cfe7a71ae3", "name": "longtext_field", "type": "text", "label": "longtext_field", "required": false}, {"id": "8b95c9ee-0e2a-48b7-a321-b2d72e4a286a", "kind": "datetime", "name": "datetime_field", "type": "date_time", "label": "datetime_field", "default": "2020-06-13T00:00:00.000Z", "required": false}, {"id": "ac67dc4c-4033-49a9-91f3-7029bc053ba0", "max": 25000, "name": "readonlyfile_field", "type": "file", "label": "readonlyfile_field", "mimes": ["pdf"], "required": false, "keepRevisions": false, "multipleUploads": false}, {"id": "a1360fed-0cd0-4203-b5ad-5fb3d3be94e1", "name": "d", "type": "boolean", "label": "d", "required": false, "trueLabel": null, "falseLabel": null}, {"id": "a9f7ff25-3109-48d4-a185-13389c829f88", "name": "d", "size": 255, "type": "string", "label": "d", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "57e77c4c-1987-439e-9460-30c8e5718352", "name": "w", "size": 255, "type": "string", "label": "w", "unique": false, "default": null, "required": false, "validation": "none"}, {"id": "b8a758d1-0ee4-4d02-a8a5-b306b5b0e3b8", "name": "hi folks!", "size": 255, "type": "string", "label": "hi folks!", "unique": false, "default": null, "required": false, "validation": "none"}]', '2020-06-29 08:28:01', '2020-07-02 12:57:12', null, 48);
        
            create table entity_values
            (
                id bigserial not null
                    constraint entity_values_pkey
                        primary key,
                entity_id bigint not null
                    constraint entity_values_entity_id_foreign
                        references entities
                            on update cascade on delete restrict,
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