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
            exec($script, $response, $error);
            $output->writeln(
                ($error)
                ? '<error>Error!</error>'
                : '<info>Success!</info>'
            );

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $this->writeResponse($response, $output);
            }
        }
    }

    /**
     * Write all lines of script response
     *
     * @param  array           $response
     * @param  OutputInterface $output
     */
    private function writeResponse(array $response, OutputInterface $output)
    {
        foreach ($response as $line) {
            $output->writeln('    '.$line);
        }
    }
}
