<?php
namespace Dployer\Event;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
* Class to execute scripts that are configured in .dployer file
*/
class ScriptRunner
{
    /**
     * @var integer
     */
    protected $interactive = false;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var QuestionHelper
     */
    protected $helper;

    /**
     * Run script set
     *
     * @param array $scripts
     * @param OutputInterface $output
     */
    public function run($scripts, OutputInterface $output)
    {
        if ($this->interactive) {
            return $this->runInteractively($scripts, $output);
        }

        foreach ($scripts as $script) {
            $this->execute($script, $output);
        }
    }

    /**
     * Enables script interactivity.
     *
     * @return self
     */
    public function enableInteractivity(
        InputInterface $input,
        QuestionHelper $helper
    ) {
        $this->interactive = true;
        $this->input  = $input;
        $this->helper = $helper;

        return $this;
    }

    /**
     * Asks before run every single command
     *
     * @param  array $script
     * @param  OutputInterface $output
     */
    protected function runInteractively($scripts, OutputInterface $output)
    {
        foreach ($scripts as $script) {
            $question = new ConfirmationQuestion(
                sprintf(
                    'Do you want to run "<info>%s</info>" command (Y/n)? ',
                    $script
                )
            );

            if (false === $this->helper->ask($this->input, $output, $question)) {
                $output->writeln('Skipped!');
                continue;
            }

            $this->execute($script, $output);
        }
    }

    /**
     * Executes a single command
     *
     * @param  string $script
     * @param  OutputInterface $output
     */
    protected function execute($script, OutputInterface $output)
    {
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
