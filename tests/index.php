<?php

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Update;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;

require __DIR__ . '/../vendor/autoload.php';

$attr1 = new EntityAttribute(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', 'string');
$attr2 = new EntityAttribute(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', 'integer');

//$select = new Select(71);
//$select->addAttributeToSelect(new EntityAttribute(1, 'b', 'string'));
//$select->openConditionsGroup();
//$select->addCondition(new Condition($attr1, '='));
//$select->addCondition(new Condition($attr2, '='), 'AND');
//$select->closeConditionsGroup();
//$select->addSummary(new Summary(new EntityAttribute(1, 'c', 'string'), false, null));
//$select->setConditionAttributeValue($attr1, new UserInput(63));
//$select->setConditionAttributeValue($attr2, new DataInput(10, 'c1'));
//$select->limitDataInputTo(10, [1, 2, 3]);
//var_dump($select->getQuery(), $select->getUserInputBindings());


//$create = new Create(17);
//$create->setAttributeValue($attr1, new DataInput(1, 'col1'));
//$create->setAttributeValue($attr2, new UserInput(555));
//$create->addAttributeToReturn($attr1);
//$create->addAttributeToReturn($attr2);
//var_dump($create->getQuery(), $create->getUserInputBindings());


//$delete = new Delete(10);
//$delete->openConditionsGroup();
//$delete->addCondition(new Condition($attr1, '='));
//$delete->addCondition(new Condition($attr2, '='), 'AND');
//$delete->closeConditionsGroup();
//$delete->setConditionAttributeValue($attr1, new UserInput(63));
//$delete->setConditionAttributeValue($attr2, new DataInput(2, 'col1'));
//$delete->addAttributeToReturn($attr1);
//$delete->addAttributeToReturn($attr2);
//var_dump($delete->getQuery(), $delete->getUserInputBindings());

$update = new Update(71);
$update->setAttributeToUpdate($attr1, new UserInput(63));
$update->setAttributeToUpdate($attr2, new DataInput(2, 'col1'));
$update->addAttributeToReturn($attr1);
$update->addAttributeToReturn($attr2);
var_dump($update->getQuery(), $update->getUserInputBindings());