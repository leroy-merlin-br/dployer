<?php namespace Dployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Deploy extends Command {

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploys the current application on elastic beanstalk')
            ->setHelp('Deploys the current application on elastic beanstalk. You must provide the application name and the environment where the deploy is going to be made.')
            ->addArgument(
                'app',
                InputArgument::REQUIRED,
                'Name of the application within Elastic Beanstalk'
            )
            ->addArgument(
               'environment',
               InputArgument::REQUIRED,
               'Environment name within the Application'
            )
        ;
    }

    /**
     * Runs the command!
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return null|int     null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text  = "APP:".$input->getArgument('app');
        $text .= "\nENV:".$input->getArgument('environment');

        $output->writeln($text);
    }
}
