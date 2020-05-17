<?php

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Action\Pieces\EntityAttribute;
use Orderbynull\PgSqlBuilder\Action\Pieces\FiltrationRule;
use Orderbynull\PgSqlBuilder\Action\Pieces\Summary;
use Orderbynull\PgSqlBuilder\Action\Select;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;

require __DIR__ . '/../vendor/autoload.php';

$q = (new Select())
    ->setBaseEntityId(71)
    ->addReturningAttribute(new EntityAttribute(1, 'b', 'string'))
    ->openFiltrationGroup()
    ->addFiltrationRule(null, new FiltrationRule(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', 'string', '='))
    ->addFiltrationRule('AND', new FiltrationRule(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', 'integer', '='))
    ->closeFiltrationGroup()
    ->addSummary(new Summary(1, 'c', false, null, 'string'))
    ->setFiltrationAttributeValue(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', new UserInput(63))
    ->setFiltrationAttributeValue(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', new DataInput(10, 'c1'))
    ->limitDataInputTo(10, [1, 2, 3]);

var_dump($q->getQuery(), $q->getQueryBindings());