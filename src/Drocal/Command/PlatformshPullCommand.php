<?php
namespace Drocal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PlatformshPullCommand extends Command {
   protected function configure()
    {
      $this
          ->setName('platform.sh:pull')
          ->setAliases(['ppull'])
          ->setDescription('git pull, platform build and drush cim.')
      ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $cmd = "platform dir";
        $errOutput->writeln(
            'Running command: ' . $cmd,
            OutputInterface::VERBOSITY_VERBOSE
        );
        $process = new Process($cmd);
        $process->run(function ($type, $buffer) use ($output) {
          if ($output->isVerbose()) {
            echo $buffer;
          }
        });
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $webdir  = trim($process->getOutput()) . '/_www' ;

        $cmds = array(
          'git pull',
          'platform build',
          'drush -y cache-rebuild',
          'drush -y updatedb',
          'drush -y config-import',
          'drush -y entup',
        );

        foreach ($cmds as $cmd) {
          $errOutput->writeln(
              'Running command: ' . $cmd,
              OutputInterface::VERBOSITY_VERBOSE
          );
          $process = new Process($cmd);
          $process->setWorkingDirectory($webdir);
          $process->run(function ($type, $buffer) use ($output) {
            echo $buffer;
          });
          // executes after the command finishes
          if (!$process->isSuccessful()) {
              throw new ProcessFailedException($process);
          }
        }
    }
}
