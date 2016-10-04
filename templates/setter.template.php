    protected function set<?= ucfirst($column->name) ?>($<?= $column->name ?>)
    {
        $this-><?= $column->name ?> = $<?= $column->name ?>;
        return $this;
    }
