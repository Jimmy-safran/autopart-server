<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Foo2Command extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('foo1:bar')
            ->setDescription('The foo1:bar command')
            ->setAliases(['afoobar2'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}
