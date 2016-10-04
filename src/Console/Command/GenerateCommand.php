<?php

namespace Zerifas\Supermodel\Console\Command;

use PDO;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Zerifas\Supermodel\Console\Config;
use Zerifas\Supermodel\Console\Database\Column;
use Zerifas\Supermodel\Console\Template;

class GenerateCommand extends Command
{
    const ARG_MODEL_NAME = 'model_name';
    const ARG_TABLE = 'table';
    const OPT_TINYINT_BOOL = 'tinyint-bool';

    protected function configure()
    {
        parent::configure();

        $this->setName('generate');
        $this->setDescription('Generate a model from a database table');

        $this->addArgument(self::ARG_MODEL_NAME, InputArgument::REQUIRED, 'Model name such as UserModel');
        $this->addArgument(self::ARG_TABLE, InputArgument::REQUIRED, 'Table name, for example users');
        $this->addOption(
            self::OPT_TINYINT_BOOL,
            't',
            InputOption::VALUE_NONE,
            'Infer columns of type TINYINT UNSIGNED NOT NULL to be boolean'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getDb();
        $modelName = $input->getArgument(self::ARG_MODEL_NAME);
        $table = $input->getArgument(self::ARG_TABLE);
        $inferBool = $input->getOption(self::OPT_TINYINT_BOOL);

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
            } elseif ($inferBool && $columnType === 'TINYINT' && $column->isUnsigned() && ! $column->isNull()) {
                $transformerType = 'Boolean';
            } elseif ($columnType === 'BIT' && $column->getLimit() === 1) {
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

        $template = new Template('model');
        $template->set([
            'namespace'          => $config['models']['namespace'],
            'modelName'          => $modelName,
            'table'              => $table,
            'allColumns'         => $columns,
            'columns'            => array_slice($columns, 1),
            'transformers'       => $transformers,
            'transformerClasses' => $transformerClasses,
        ]);
        $template->render();
    }

    protected function getDb()
    {
        $config = Config::get();

        $dsnDefaults = [
            'host'    => 'localhost',
            'dbname'  => null,
            'charset' => 'utf8',
        ];
        $db = array_merge($dsnDefaults, $config['db']);

        $dsn = 'mysql:';
        foreach ($db as $key => $value) {
            if ($key === 'username' || $key === 'password' || empty($value)) {
                continue;
            }

            $dsn .= sprintf('%s=%s;', $key, $value);
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $db['username'], $db['password'], $options);
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
