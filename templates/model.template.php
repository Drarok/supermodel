<?= '<', '?php', PHP_EOL ?>

namespace <?= $namespace ?>;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\QueryBuilder;
<?php foreach ($transformerClasses as $transformer): ?>
use Zerifas\Supermodel\Transformer\<?= $transformer ?> as <?= $transformer ?>Transformer;
<?php endforeach; ?>

class <?= ucfirst($table) ?>Model extends AbstractModel
{
    protected static $columns = [
<?php foreach ($columns as $column): ?>
        '<?= $column->name ?>',
<?php endforeach; ?>
    ];

<?php if (count($transformers) === 0): ?>
    protected static $valueTransformers = [];
<?php else: ?>
    protected static $valueTransformers = [
<?php foreach ($transformers as $column => $transformer): ?>
        '<?= $column ?>' => <?= $transformer ?>Transformer::class,
<?php endforeach; ?>
    ];
<?php endif; ?>
}
