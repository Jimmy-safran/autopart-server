<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FooSubnamespaced2Command extends Command
{
    public InputInterface $input;
    public OutputInterface $output;

    protected function configure(): void
    {
        $this
            ->setName('foo:go:bret')
            ->setDescription('The foo:bar:go command')
            ->setAliases(['foobargo'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return 0;
    }
}
