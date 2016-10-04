    public function get<?= ucfirst($column->name) ?>()
    {
        return $this-><?= $column->name ?>;
    }
