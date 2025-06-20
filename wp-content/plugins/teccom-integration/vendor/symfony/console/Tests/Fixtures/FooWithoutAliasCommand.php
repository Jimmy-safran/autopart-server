<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FooWithoutAliasCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('foo')
            ->setDescription('The foo command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('called');

        return 0;
    }
}
