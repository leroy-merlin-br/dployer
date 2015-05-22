<?php
namespace Dployer\Event;

use Symfony\Component\Console\Output\OutputInterface;

/**
* Class to execute scripts that are configured in .dployer file
*/
class ScriptRunner
{
    /**
     * Run script set
     *
     * @param array $scripts
     * @param OutputInterface $output
     */
    public function run($scripts, OutputInterface $output)
    {
        foreach ($scripts as $script) {
            $output->write(sprintf(' * %s: ', $script));
            $response = exec($script, $execOutput, $error);
            $output->writeln(
                ($error)
                ? '<error>Error!</error>'
                : '<info>Success!</info>'
            );
        }
    }
}
