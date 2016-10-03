<?php

namespace Zerifas\Supermodel\Console;

use Symfony\Component\Console\Application as BaseApplication;

use Zerifas\Supermodel\Console\Command\GenerateCommand;

class Application extends BaseApplication
{
    const NAME = 'Supermodel';
    const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);

        $this->addCommands([
            new GenerateCommand(),
        ]);
    }
}
