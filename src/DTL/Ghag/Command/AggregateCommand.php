<?php

namespace DTL\Ghag\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class AggregateCommand extends Command
{
    public function configure()
    {
        $this->setName('ag');
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

        $aggregator = new \DTL\Ghag\Aggregator;
        $aggregator->setLoggerClosure(function ($message) use ($output) {
            $output->writeln($message);
        });

        $root = $aggregator->aggregate($config['repositories']);
        $dom = $root->ownerDocument;

        if (isset($config['groups'])) {
            foreach ($config['groups'] as $groupName => $groupData) {
                $groupEl = $dom->createElement('group');
                $groupEl->setAttribute('name', $groupName);
                $groupEl->setAttribute('description', $groupData['description']);
                $root->appendChild($groupEl);
            }
        }

        $dom->preserveWhitespace = true;
        $dom->formatOutput = true;
        $string = $dom->save('report/report.xml');
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
