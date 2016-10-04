    protected function set<?= ucfirst($column->getName()) ?>($<?= $column->getName() ?>)
    {
        $this-><?= $column->getName() ?> = $<?= $column->getName() ?>;
        return $this;
    }
