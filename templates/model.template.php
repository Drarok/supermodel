<?php

use Zerifas\Supermodel\Console\Template;

$setter = new Template('setter');
$getter = new Template('getter');

echo '<', '?php', PHP_EOL;

?>

namespace <?= $namespace ?>;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\QueryBuilder;
<?php
foreach ($transformerClasses as $transformer) {
    echo sprintf('use Zerifas\Supermodel\Transformer\%1$s as %1$sTransformer;', $transformer), PHP_EOL;
}
?>

class <?= $modelName ?> extends AbstractModel
{
    protected static $columns = [
<?php
foreach ($allColumns as $c) {
    echo sprintf('        \'%s\',', $c->getName()), PHP_EOL;
}
?>
    ];

<?php
if (count($transformers) === 0) {
    echo '    protected static $valueTransformers = [];', PHP_EOL;
} else {
    echo '    protected static $valueTransformers = [', PHP_EOL;
    foreach ($transformers as $column => $transformer) {
        echo sprintf('        \'%s\' => %sTransformer::class,', $column, $transformer), PHP_EOL;
    }

    echo '    ];', PHP_EOL;
}

echo PHP_EOL;

foreach ($columns as $column) {
    echo sprintf('    protected $%s;', $column->getName()), PHP_EOL;
}

foreach ($columns as $column) {
    echo PHP_EOL;
    $setter->render(['column' => $column]);

    echo PHP_EOL;
    $getter->render(['column' => $column]);
}
?>
}
