<?php
namespace Dployer\Services;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EBSVersionManager
 *
 * Manages project versions in AWS ElasticBeanstalk.
 *
 * @package  Dployer\Services
 */
class EBSVersionManager
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * EBS Application name
     * @var string
     */
    protected $app;

    /**
     * EBS Environment name
     * @var string
     */
    protected $env;

    /**
     * Base class for interacting with web service clients
     * @var Aws\Common\Aws
     */
    protected $aws;

    /**
     * All env vars required to deploy something.
     * @var [type]
     */
    protected static $envVarsRequired = [
        'DPLOYER_PROFILE',
        'DPLOYER_REGION',
        'DPLOYER_AWS_KEY',
        'DPLOYER_AWS_SECRET'
    ];

    /**
     * Set the app attribute using the global $app variable
     */
    public function __construct()
    {
        $this->app = app();

        $aws = $this->app->make('Aws\Common\Aws');

        $config = $this->buildConfig();

        $this->aws = $aws->factory($config);
    }

    /**
     * Initializes the EBSVersionManager
     *
     * @param  string $app     Application name
     * @param  string $env     Application environment name
     * @param  OutputInterface $output
     */
    public function init($app, $env, OutputInterface $output)
    {
        $this->app    = $app;
        $this->env    = $env;
        $this->output = $output;
    }

    /**
     * Creates a new application version with the given local file
     *
     * @param  string $filename    Local zip file to be used as the application version
     * @param  string $description
     *
     * @return string VersionLabel
     */
    public function createVersion($filename, $description = "")
    {
        if (! getenv('DPLOYER_BUCKET')) {
            $this->output->writeln("<error>DPLOYER_BUCKET environment variable not set</error>");
        }

        $bucket       = getenv('DPLOYER_BUCKET') ?: 'dployer-versions';
        $key          = str_replace('.zip', '-'.date('U').'.zip', $filename);
        $versionLabel = $key;

        $s3 = $this->aws->get('S3');
        $s3->registerStreamWrapper();

        $this->output->writeln("Uploading <info>$filename</info>...");
        copy(
            getcwd().'/'.$filename,
            's3://'.$bucket.'/'.$key
        );

        $ebs = $this->aws->get('ElasticBeanstalk');

        $this->output->writeln("Creating version <info>$versionLabel</info> in EBS...");
        $ver = $ebs->createApplicationVersion([
            'ApplicationName' => $this->app,
            'VersionLabel'    => $versionLabel,
            'Description'     => $description ?: "No description",
            'SourceBundle'    => [
                'S3Bucket' => $bucket,
                'S3Key'    => $key,
            ],
            'AutoCreateApplication' => false,
        ]);

        if ($ver->get('ApplicationVersion')) {
            return $versionLabel;
        }

        return false;
    }

    /**
     * Deploy the version of the given $versionLabel of the application
     *
     * @param  string $versionLabel Identifier of the version that is going to be used
     *
     * @return boolean Success
     */
    public function deployVersion($versionLabel)
    {
        $ebs = $this->aws->get('ElasticBeanstalk');

        $this->output->writeln(
            "Deploying version <info>$versionLabel</info> to <info>".
            $this->app." ".$this->env."</info>..."
        );

        $env = $ebs->updateEnvironment([
            "ApplicationName" => $this->app,
            "EnvironmentName" => $this->env,
            "VersionLabel" => $versionLabel,
        ]);

        if ($env->get('VersionLabel') == $versionLabel) {
            return true;
        }

        return false;
    }

    /**
     * Builds the aws configuration.
     *
     * @return array
     */
    protected function buildConfig()
    {
        if ($this->allEnvironmentVariablesOk()) {
            $defaultConfig = [
                "region"  => getenv('DPLOYER_REGION'),
                "key"     => getenv('DPLOYER_AWS_KEY'),
                "secret"  => getenv('DPLOYER_AWS_SECRET')
            ];

            return $defaultConfig;
        }

        $fileConfig = getenv("HOME").'/.aws/config.json';

        return $fileConfig;
    }

    /**
     * Verifies if all env vars are avaliable to fill the configuration.
     *
     * @return boolean
     */
    protected function allEnvironmentVariablesOk()
    {
        foreach (static::$envVarsRequired as $envVar) {
            if (! getenv($envVar)) {
                return false;
            }
        }

        return true;
    }
}
