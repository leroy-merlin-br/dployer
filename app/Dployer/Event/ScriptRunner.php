<?php

namespace Dployer\Event;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class to execute scripts that are configured in .dployer file.
 */
class ScriptRunner
{
    /**
     * @var int
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
     * @var bool
     */
    protected $stopOnError = true;

    /**
     * Run script set.
     *
     * @param array           $scripts
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
     * @param InputInterface $input
     * @param QuestionHelper $helper
     *
     * @return ScriptRunner
     */
    public function enableInteractivity(
        InputInterface $input,
        QuestionHelper $helper
    ) {
        $input->setInteractive(true);

        $this->interactive = true;
        $this->input = $input;
        $this->helper = $helper;

        return $this;
    }

    /**
     * Continue running scripts even if it returns with errors.
     *
     * @return self
     */
    public function continueOnError()
    {
        $this->stopOnError = false;

        return $this;
    }

    /**
     * Asks before run every single command.
     *
     * @param array           $scripts
     * @param OutputInterface $output
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
     * Executes a single command.
     *
     * @param string          $script
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute($script, OutputInterface $output)
    {
        $output->write(sprintf(' * %s: ', $script));
        exec($script, $response, $error);
        $output->writeln(
            $error
            ? '<error>Error!</error>'
            : '<info>Success!</info>'
        );

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->writeResponse($response, $output);
        }

        $question = new ConfirmationQuestion(
            'An error ocurred. Do you want to continue with deploy (y/N)?',
            false
        );

        if ($error) {
            $stopOnError = ($this->stopOnError && false === $this->interactive);
            $confirmAbort = ($this->interactive &&
                false === $this->helper->ask($this->input, $output, $question)
            );

            if ($stopOnError || $confirmAbort) {
                throw new ScriptErrorException();
            }
        }

        return true;
    }

    /**
     * Write all lines of script response.
     *
     * @param array           $response
     * @param OutputInterface $output
     */
    private function writeResponse(array $response, OutputInterface $output)
    {
        foreach ($response as $line) {
            $output->writeln('    '.$line);
        }
    }
}
