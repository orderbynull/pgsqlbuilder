<?php

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Action\Condition;
use Orderbynull\PgSqlBuilder\Action\EntityAttribute;
use Orderbynull\PgSqlBuilder\Action\Select\Select;
use Orderbynull\PgSqlBuilder\Action\Select\Summary;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;

require __DIR__ . '/../vendor/autoload.php';

$attr1 = new EntityAttribute(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', 'string');
$attr2 = new EntityAttribute(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', 'integer');

$select = new Select(71);
$select->addReturningAttribute(new EntityAttribute(1, 'b', 'string'));
$select->openConditionsGroup();
$select->addCondition(new Condition($attr1, '='));
$select->addCondition(new Condition($attr2, '='), 'AND');
$select->closeConditionsGroup();
$select->addSummary(new Summary(new EntityAttribute(1, 'c', 'string'), false, null));
$select->setConditionsAttributeValue($attr1, new UserInput(63));
//$select->setConditionsAttributeValue($attr2, new DataInput(10, 'c1'));
$select->limitDataInputTo(10, [1, 2, 3]);
var_dump($select->getQuery(), $select->getQueryBindings());

$create = new Action\Create\Create(17);
$create->setAttributeValue(new EntityAttribute(1,'a-b-c', 'string'), new DataInput(1, 'col1'));
$create->setAttributeValue(new EntityAttribute(1,'c-d-e', 'integer'), new UserInput(555));
var_dump($create->getQuery(), $create->getQueryBindings());