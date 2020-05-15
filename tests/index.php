<?php

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\UserInput;

require __DIR__ . '/../vendor/autoload.php';

//$query = new Select()
//        ->from(entityId)
//    ->attributes([new Attribute(1, 'abc'), new Attribute(2, 'cdt')])
//    ->withSummarization()
//    ->where([new FiltrationBlock(), 'OR', new FiltrationBlock()])
//    ->with(new InputData(nodeId, 'some_node'))
//    ->query();

$q = (new Select())
    ->fromEntity(71)
    ->newFiltrationGroup()
    ->addFiltrationRule(null, new FiltrationRule(71, '06f5adce-fe92-4e60-8bce-9fd7197b3ef7', 'string', '=', new DataInput(17, 'c1')))
    ->addFiltrationRule('AND', new FiltrationRule(71, '0eb92991-d39b-4824-a258-cdc6d21568bb', 'integer', '=', new UserInput(63)));

var_dump($q->query(), $q->bindings());