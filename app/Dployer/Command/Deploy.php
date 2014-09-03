<?php
namespace Dployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Deploy extends Command
{
    /**
     * Application
     * @var Illuminate\Container\Container
     */
    protected $app;

    /**
     * Set the app attribute using the global $app variable
     */
    public function __construct()
    {
        parent::__construct();
        $this->app = app();

        file_put_contents(
            sys_get_temp_dir() . '/guzzle-cacert.pem',
            file_get_contents('vendor/guzzle/guzzle/src/Guzzle/Http/Resources/cacert.pem')
        );
    }

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
     *
     * @return null|int     null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app       = $input->getArgument('app');
        $env       = $input->getArgument('environment');
        $branch    = exec('echo $(git branch | sed -n -e \'s/^\* \(.*\)/\1/p\')');
        $commitMsg = exec('echo $(git log --format="%s" -n 1)');

        $output->writeln("<info>APP:</info>".$app);
        $output->writeln("<info>ENV:</info>".$env);

        $packer = $this->app->make('Dployer\Services\ProjectPacker');
        $packer->setOutput($output);
        $filename = $packer->pack();

        $ebsManager = $this->app->make('Dployer\Services\EBSVersionManager');
        $ebsManager->init($app, $env, $output);
        $versionLabel = $ebsManager->createVersion($filename, "[$branch] $commitMsg");

        if ($versionLabel && $ebsManager->deployVersion($versionLabel)) {
            $fileRemoved = $this->removeZipFile($filename);
            $output->writeln("<info>Removing $filename</info>");
            if (false === $fileRemoved) {
                $output->writeln("<info>Unable to remove zip file. Run manually:</info>");
                $output->writeln("<info>rm $filename</info>");
            }

            $output->writeln("<info>done</info>");

            return 0;
        }

        $output->writeln("<error>failed</error>");

        return 1;
    }

    /**
     * Removes the deployed .zip file
     *
     * @param string $filename
     *
     * @return boolean
     */
    protected function removeZipFile($filename)
    {
        return unlink($filename);
    }
}
