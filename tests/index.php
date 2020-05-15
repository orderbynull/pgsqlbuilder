<?php

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Action\Pieces\FiltrationRule;
use Orderbynull\PgSqlBuilder\Action\Select;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;

require __DIR__ . '/../vendor/autoload.php';

$q = (new Select())
    ->setBaseEntityId(71)
    ->openFiltrationGroup()
    ->addFiltrationRule(null, new FiltrationRule(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', 'string', '=', new DataInput(17, 'c1')))
    ->addFiltrationRule('AND', new FiltrationRule(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', 'integer', '=', new UserInput(63)))
    ->closeFiltrationGroup()
    ->limitDataInputTo(17, [1, 2, 3]);

var_dump($q->getQuery(), $q->getQueryBindings());