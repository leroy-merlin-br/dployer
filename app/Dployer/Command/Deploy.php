<?php
namespace Dployer\Command;

use Dployer\Config\BadFormattedFileException;
use Dployer\Config\Config;
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

        try {
            $this->config = new Config(getcwd().'/.dployer');
        } catch (\InvalidArgumentException $error) {
            $this->config = null;
        } catch (BadFormattedFileException $error) {
            die($error->getMessage());
        }

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
                'application',
                InputArgument::OPTIONAL,
                'Name of the application within Elastic Beanstalk'
            )
            ->addArgument(
                'environment',
                InputArgument::OPTIONAL,
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
        $app = $input->getArgument('application')
            ?: $this->getConfigValue('application');
        $env = $input->getArgument('environment')
            ?: $this->getConfigValue('environment');

        if (! $app) {
            return $this->variableNotDefined('application', $output);
        }

        if (! $env) {
            return $this->variableNotDefined('environment', $output);
        }

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
            $this->removeZipFile($filename, $output);
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
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function removeZipFile($filename, OutputInterface $output)
    {
        $output->writeln("Removing $filename...");
        if (false === unlink($filename)) {
            $output->writeln("Unable to remove zip file. Run manually:");
            $output->writeln("rm $filename");
        } else {
            $output->writeln("Removed");
        }
    }

    /**
     * Retrieves value from config file
     *
     * @param string $key
     *
     * @return array|integer|string|null
     */
    protected function getConfigValue($key)
    {
        if (! $this->config) {
            return null;
        }

        return $this->config->get($key);
    }

    /**
     * Abort application due to missing required variable
     *
     * @param string $var
     * @param OutputInterface $output
     *
     * @return 0
     */
    protected function variableNotDefined($var, OutputInterface $output)
    {
        $output->writeln(sprintf("<error>%s is not defined</error>", $var));
        $output->writeln(sprintf(
            "Add '%s' key in the .dployer file or pass it as ".
            "command parameter",
            $var
        ));

        return 0;
    }
}
