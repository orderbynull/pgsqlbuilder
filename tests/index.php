<?php

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Action\Condition;
use Orderbynull\PgSqlBuilder\Action\Pieces\EntityAttribute;
use Orderbynull\PgSqlBuilder\Action\Select\Select;
use Orderbynull\PgSqlBuilder\Action\Select\Summary;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;

require __DIR__ . '/../vendor/autoload.php';

$select = new Select();
$select->setBaseEntityId(71);
$select->addReturningAttribute(new EntityAttribute(1, 'b', 'string'));
$select->openConditionsGroup();
$select->addCondition(new Condition(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', 'string', '='));
$select->addCondition(new Condition(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', 'integer', '='), 'AND');
$select->closeConditionsGroup();
$select->addSummary(new Summary(1, 'c', false, null, 'string'));
$select->setConditionsAttributeValue(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', new UserInput(63));
$select->setConditionsAttributeValue(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', new DataInput(10, 'c1'));
$select->limitDataInputTo(10, [1, 2, 3]);

var_dump($select->getQuery(), $select->getQueryBindings());