<?php

use Symfony\Component\Console\Command\Command;

class FooSameCaseLowercaseCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('foo:bar')->setDescription('foo:bar command');
    }
}
