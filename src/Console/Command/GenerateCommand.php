<?php

namespace Zerifas\Supermodel\Console\Command;

use PDO;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Zerifas\Supermodel\Console\Config;
use Zerifas\Supermodel\Console\Database\Column;
use Zerifas\Supermodel\Console\Template;

class GenerateCommand extends Command
{
    protected function configure()
    {
        parent::configure();
        $this->setName('generate');
        $this->setDescription('Generate a model from a database table');
        $this->addArgument('model_name', InputArgument::REQUIRED, 'Model name such as UserModel');
        $this->addArgument('table', InputArgument::REQUIRED, 'Table name, for example users');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getDb();
        $modelName = $input->getArgument('model_name');
        $table = $input->getArgument('table');

        $this->validateTable($db, $table);
        $columns = $this->getColumns($db, $table);

        $transformers = [];
        $transformerClasses = [];
        foreach ($columns as $column) {
            $columnType = $column->getType();
            $prefix = substr($columnType, 0, 4);

            if ($prefix == 'DATE' || $prefix == 'TIME') {
                if ($columnType === 'DATETIME') {
                    $transformerType = 'DateTime';
                } else {
                    $transformerType = ucfirst(strtolower($columnType));
                }
            } elseif ($columnType === 'TINYINT') {
                $transformerType = 'Boolean';
            } else {
                continue;
            }

            $transformers[$column->getName()] = $transformerType;
            $transformerClasses[$transformerType] = true;
        }

        $transformerClasses = array_keys($transformerClasses);
        sort($transformerClasses);

        $config = Config::get();

        $view = new Template('model');
        $view->set([
            'namespace'          => $config['models']['namespace'],
            'modelName'          => $modelName,
            'table'              => $table,
            'allColumns'         => $columns,
            'columns'            => array_slice($columns, 1),
            'transformers'       => $transformers,
            'transformerClasses' => $transformerClasses,
        ]);
        $view->render();
    }

    protected function getDb()
    {
        $config = Config::get();

        $dsn = sprintf(
            'mysql:hostname=%s;dbname=%s;charset=%s',
            $config['db']['hostname'],
            $config['db']['dbname'],
            empty($config['db']['charset']) ? 'utf8' : $config['db']['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $config['db']['username'], $config['db']['password'], $options);
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
            $columns[] = new Column($row);
        }

        return $columns;
    }
}
