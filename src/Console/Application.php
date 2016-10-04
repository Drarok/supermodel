<?php

namespace Zerifas\Supermodel\Console;

use Symfony\Component\Console\Application as BaseApplication;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\Console\Command\GenerateCommand;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('Supermodel', AbstractModel::VERSION);

        $this->addCommands([
            new GenerateCommand(),
        ]);
    }
}
