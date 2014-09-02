<?php
namespace Dployer\Services;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProjectPacker
 *
 * This service will pack the latest state of current repository into a zip file
 * Also a copy the actual `vendor` directory will be present inside the created
 * zip.
 *
 * @package  Dployer\Services
 */
class ProjectPacker
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Sets the output interface
     *
     * @param  $OutputInterface $output
     */
    public function setOutput(OutputInterface $output = null)
    {
        $this->output = $output;
    }

    /**
     * Packs the current repository + the vendor directory. Returns the filename
     * or null if the file was not generated.
     *
     * @return string Filename
     */
    public function pack()
    {
        // Dumping autoload
        $this->output->writeln("Dumping autoload...");
        exec('composer dump-autoload');

        // Clone the repo into a tmp folder
        $this->output->writeln("Clonning clean repository...");
        exec('git clone . ../.deployment > /dev/null');

        // Copy current composer dependencies
        $this->output->writeln("Copying vendor directory...");
        exec('cp -rf vendor ../.deployment/vendor');
        exec('cp -rf composer.lock ../.deployment/composer.lock');

        // Create the zip the file
        $this->output->writeln("Creating zip file...");
        $currentDir  = getcwd();
        $zipFilename = exec('echo ver_$(git log --format="%H" -n 1).zip');
        chdir("../.deployment");
        exec('zip -r '.$zipFilename.' * > /dev/null');
        exec('mv '.$zipFilename.' "'.$currentDir.'/'.$zipFilename.'"');
        chdir($currentDir);

        // Remove tmp folder
        $this->output->writeln("Removing temporary files...");
        exec('rm -rf ../.deployment');

        return $zipFilename;
    }
}
