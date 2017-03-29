<?php
namespace Drocal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class D7LocalCommand extends Command {
   protected function configure()
    {
      $this
          ->setName('d7:local')
          ->setAliases(['local7'])
          ->setDescription('Normalize users and enable devel for auth and anon.')
      ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $cmds = array(
          "drush sqlq \"UPDATE {users} set name='adminlocaljustincase' where name='admin'\" --db-prefix",
          "drush sqlq \"UPDATE {users} set name='admin' where uid=1\" --db-prefix",
          "drush upwd admin --password=1",
          "drush sqlq \"UPDATE {users} u1 LEFT JOIN users u2 ON u2.uid = 1 set u1.pass=u2.pass\" --db-prefix",
          "drush en -y devel",
          "drush rap 1 'access devel information'",
          "drush rap 'authenticated user' 'access devel information'",
        );

        foreach ($cmds as $cmd) {
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
}
