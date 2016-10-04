    public function get<?= ucfirst($column->getName()) ?>()
    {
        return $this-><?= $column->getName() ?>;
    }
