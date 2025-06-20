<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('namespace:name')
            ->setAliases(['name'])
            ->setDescription('description')
            ->setHelp('help')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('execute called');

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('interact called');
    }
}
