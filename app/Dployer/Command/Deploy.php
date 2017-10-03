<?php

namespace Dployer\Command;

use Dployer\Config\BadFormattedFileException;
use Dployer\Config\Config;
use Dployer\Event\ScriptRunner;
use Dployer\Event\ScriptErrorException;
use Dployer\Services\EBSVersionManager;
use Dployer\Services\ProjectPacker;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Deploy extends Command
{
    /**
     * Application.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ScriptRunner
     */
    protected $scriptRunner;

    /**
     * Set the app attribute using the global $app variable.
     */
    public function __construct()
    {
        parent::__construct();
        $this->app = app();

        try {
            $this->config = new Config(getcwd().'/.dployer');
        } catch (InvalidArgumentException $error) {
            $this->config = null;
        } catch (BadFormattedFileException $error) {
            die($error->getMessage());
        }

        $this->scriptRunner = new ScriptRunner();

        file_put_contents(
            sys_get_temp_dir().'/guzzle-cacert.pem',
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
            ->addOption(
                'interactive',
                'i',
                InputOption::VALUE_NONE,
                'Asks before run every script in .dployer file'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Continue deploy process even if it throws an error'
            )
        ;
    }

    /**
     * Runs the command!
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $input->getArgument('application') ?: $this->getConfigValue('application');
        $env = $input->getArgument('environment') ?: $this->getConfigValue('environment');

        if (!$app) {
            return $this->variableNotDefined('application', $output);
        }

        if (!$env) {
            return $this->variableNotDefined('environment', $output);
        }

        if ($input->getOption('interactive')) {
            $this->scriptRunner->enableInteractivity(
                $input,
                $this->getHelper('question')
            );
        }

        if ($input->getOption('force')) {
            $this->scriptRunner->continueOnError();
        }

        $this->dispatchEvent('init', $output);

        $branch = exec('echo $(git branch | sed -n -e \'s/^\* \(.*\)/\1/p\')');
        $commitMsg = exec('echo $(git log --format="%s" -n 1)');

        $this->exportDeployInfoToEnv($app, $env, $branch, $commit);

        $output->writeln('<info>APP:</info>'.$app);
        $output->writeln('<info>ENV:</info>'.$env);

        $this->dispatchEvent('before-pack', $output);

        $packer = $this->app->make(ProjectPacker::class);
        $packer->setOutput($output);
        $filename = $packer->pack(
            (array) $this->getConfigValue('exclude-paths'),
            (array) $this->getConfigValue('copy-paths')
        );

        $this->dispatchEvent('before-deploy', $output);

        $ebsManager = $this->app->make(EBSVersionManager::class);
        $ebsManager->init($app, $env, $output);
        $versionLabel = $ebsManager->createVersion($filename, "[$branch] $commitMsg");

        if ($versionLabel && $ebsManager->deployVersion($versionLabel)) {
            $this->removeZipFile($filename, $output);
            $output->writeln('<info>done</info>');

            $this->dispatchEvent('finish', $output);

            return 0;
        }

        $this->dispatchEvent('finish', $output);

        $output->writeln('<error>failed</error>');

        return 1;
    }

    /**
     * Removes the deployed .zip file.
     *
     * @param string          $filename
     * @param OutputInterface $output   An OutputInterface instance
     */
    protected function removeZipFile($filename, OutputInterface $output)
    {
        $output->writeln("Removing $filename...");
        if (false === unlink($filename)) {
            $output->writeln('Unable to remove zip file. Run manually:');
            $output->writeln("rm $filename");
        } else {
            $output->writeln('Removed');
        }
    }

    /**
     * Retrieves value from config file.
     *
     * @param string $key
     *
     * @return array|int|string|null
     */
    protected function getConfigValue($key)
    {
        return $this->config ? $this->config->get($key) : null;
    }

    /**
     * Abort application due to missing required variable.
     *
     * @param string          $var
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function variableNotDefined($var, OutputInterface $output)
    {
        $output->writeln(sprintf('<error>%s is not defined</error>', $var));
        $output->writeln(sprintf(
            "Add '%s' key in the .dployer file or pass it as ".
            'command parameter',
            $var
        ));

        return 0;
    }

    /**
     * Dispatch an event and execute the commands in 'script' key on config
     * file.
     *
     * @param string          $eventName
     * @param OutputInterface $output
     */
    protected function dispatchEvent($eventName, OutputInterface $output)
    {
        $scripts = $this->getConfigValue('scripts.'.$eventName);

        if (is_null($scripts)) {
            return;
        }

        $output->writeln('Event: '.$eventName);

        try {
            $this->scriptRunner->run((array) $scripts, $output);
        } catch (ScriptErrorException $error) {
            exit('Aborting...');
        }
    }

    /**
     * Export deploy configuration to env vars so they can be accessed
     * inside .dployer file.
     *
     * @param string $app    EBS Aplication name.
     * @param string $env    EBS Environment name.
     * @param string $branch Current git branch.
     * @param string $commit Current git message.
     *
     */
    protected function exportDeployInfoToEnv($app, $env, $branch, $commit)
    {
        exec('export EBS_APP="'.$app.'"');
        exec('export EBS_ENV="'.$env.'"');
        exec('export GIT_BRANCH="'.$branch.'"');
        exec('export GIT_COMMIT="'.$commit.'"');
    }
}
