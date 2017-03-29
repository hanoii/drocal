<?php
namespace Drocal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PlatformshSyncCommand extends Command {
   protected function configure()
    {
      $this
          ->setName('platform.sh:sync')
          ->setAliases(['ps'])
          ->addOption('environment', 'e', InputOption::VALUE_REQUIRED, 'Platform.sh environment')
          ->setDescription('Syncs remote database without cache data into local. Must be run within a drupal root.')
      ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $verbosity = '';
        if ($output->isQuiet()) {
            $verbosity = '-q';
        }

        if ($output->isVerbose()) {
            $verbosity = '-v';
        }

        if ($output->isVeryVerbose()) {
            $verbosity = '-vv';
        }

        if ($output->isDebug()) {
            $verbosity = '-vvv';
        }

        $environment = $input->getOption('environment');
        if ($environment) {
          $environment = '-e ' . $environment;
        }


        $cmd = "drush sql-connect";
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

        $mysql_connect = $process->getOutput();

        $cmd = "drocal $verbosity platform.sh:dump $environment -p --gzip | gunzip | $mysql_connect";
        $errOutput->writeln(
            'Running command: ' . $cmd,
            OutputInterface::VERBOSITY_VERBOSE
        );

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) use ($output) {
            echo $buffer;
        });

    }
}
