<?php
namespace Drocal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PlatformDumpCommand extends Command {
   protected function configure()
    {
      $this
          ->setName('platform.sh:dump')
          ->setAliases(['pd'])
          ->addOption('platform-environment', 'e', InputOption::VALUE_REQUIRED, 'Platform.sh environment ID')
          ->addOption('platform-vervosity', 'p', InputOption::VALUE_NONE, 'Passthrou verbosity level to platform-cli')
          ->setDescription('Dumps remote database without cache data.')
      ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $verbosity = '';
        if ($input->getOption('platform-vervosity')) {
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
        }

        $environment = $input->getOption('platform-environment');
        if ($environment) {
          $environment = '-e ' . $environment;
        }

        $cmd = "platform $verbosity $environment drush 'sqlq \"SHOW TABLES\"'";
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

        $cmdOutput =$process->getOutput();

        $tables = preg_split("/\n/", $cmdOutput);
        $structure_tables = preg_grep('/^cache.*|watchdog|accesslog|sessions/', $tables);

        $cmd = "platform $verbosity $environment db:dump --gzip -o --schema-only";
        foreach ($structure_tables as $t) {
          $cmd .= ' --table=' . $t;
        }
        $errOutput->writeln(
            'Running command: ' . $cmd,
            OutputInterface::VERBOSITY_VERBOSE
        );

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) use ($output) {
            echo $buffer;
        });

        $cmd = "platform $verbosity $environment db:dump --gzip -o";
        foreach ($structure_tables as $t) {
          $cmd .= ' --exclude-table=' . $t;
        }
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
