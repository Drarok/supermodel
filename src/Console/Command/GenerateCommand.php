<?php

namespace Zerifas\Supermodel\Console\Command;

use PDO;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    protected function configure()
    {
        parent::configure();
        $this->setName('generate');
        $this->setDescription('Generate a model from a database table');
        $this->addArgument('table', InputArgument::REQUIRED, 'Table name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getDb();
        $table = $input->getArgument('table');

        $this->validateTable($db, $table);
        $columns = $this->getColumns($db, $table);

        $transformers = [];
        $transformerClasses = [];
        foreach ($columns as $column) {
            $prefix = strtoupper(substr($column->type, 0, 4));
            if ($prefix == 'DATE' || $prefix == 'TIME') {
                if (strtoupper($column->type) === 'DATETIME') {
                    $type = 'DateTime';
                } else {
                    $type = ucfirst(strtolower($column->type));
                }

                $transformers[$column->name] = $type;
                $transformerClasses[$type] = true;
            }
        }

        $view = new Template('model');
        $view->set([
            'namespace'          => 'YourApp\\Model',
            'table'              => $table,
            'hasDate'            => true,
            'columns'            => $columns,
            'transformers'       => $transformers,
            'transformerClasses' => array_keys($transformerClasses),
        ]);
        $view->render();
    }

    protected function getDb()
    {
        $config = $this->getConfig();

        $dsn = sprintf(
            'mysql:hostname=%s;dbname=%s;charset=%s',
            $config['hostname'],
            $config['dbname'],
            empty($config['charset']) ? 'utf8' : $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $config['username'], $config['password'], $options);
    }

    protected function getConfig()
    {
        $configPath = SUPERMODEL_PROJECT_ROOT . '/supermodel.json';

        if (! file_exists($configPath)) {
            throw new \Exception('No such file: ' . $configPath);
        }

        return json_decode(file_get_contents($configPath), true);
    }

    protected function validateTable(PDO $db, $table)
    {
        $stmt = $db->query(sprintf('SHOW TABLES LIKE \'%s\'', $table));

        if (!$stmt->fetch()) {
            throw new \InvalidArgumentException(sprintf(
                'Table %s does not exist.',
                $table
            ));
        }
    }

    protected function getColumns(PDO $db, $table)
    {
        $stmt = $db->query(sprintf('SHOW COLUMNS FROM `%s`', $table));

        $columns = [];
        while (($row = $stmt->fetch())) {
            $columns[] = Column::parse($row);
        }

        return $columns;
    }
}

class Column
{
    public $name;
    public $type;
    public $null;

    public static function parse($row)
    {
        if (! preg_match('/^(\w+)/', $row['Type'], $matches)) {
            throw new \InvalidArgumentException('Failed to parse column type: ' . $type);
        }

        $instance = new static();

        $instance->name = $row['Field'];
        $instance->type = $matches[1];
        $instance->null = $row['Null'] === 'YES';

        return $instance;
    }
}

class Template
{
    protected $name;
    protected $data = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function set($key, $value = null)
    {
        if ($value === null && is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    public function render()
    {
        extract($this->data);
        require SUPERMODEL_TEMPLATES_ROOT . '/' . $this->name . '.template.php';
    }
}
