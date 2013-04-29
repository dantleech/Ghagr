<?php

namespace DTL\GhAggregator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class AggregateCommand extends Command
{
    public function configure()
    {
        $this->setName('dtl:gh:aggregate');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED);
        $this->addOption('as-xml', null, InputOption::VALUE_NONE);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $config = getcwd().'/config.yml';
        $output->writeln('<info>Using config: </info>'.$config);

        if (!file_exists($config)) {
            throw new \Exception('Config file "'.$config.'" does not exist.');
        }

        $config = Yaml::parse($config);
        $issues = array();

        $aggregator = new \DTL\GhAggregator\Aggregator;
        $aggregator->setLoggerClosure(function ($message) use ($output) {
            $output->writeln($message);
        });
        $dom = $aggregator->aggregate($config['repositories']);


        $dom->preserveWhitespace = true;
        $dom->formatOutput = true;
        $string = $dom->saveXml();
        die($string);

    }

    public function outputConsole($output)
    {
        foreach ($issues as $username => $repoIssues) {
            $output->writeln('<comment>'.$username.'</comment>');
            foreach ($repoIssues as $repoName => $issues) {
                $output->writeln('<comment>  '.$repoName.'</comment>');
                foreach ($issues as $issue) {
                    $output->writeln('    <info>> </info>'.$issue['title'].': '.$issue['user']['login']);
                }
            }
        }
    }
}
