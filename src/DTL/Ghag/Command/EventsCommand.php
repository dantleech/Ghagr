<?php

namespace DTL\Ghag\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use Github\Exception\RuntimeException;


class EventsCommand extends Command
{
    public function configure()
    {
        $this->setName('ghag:events');
        $this->addArgument('org', InputArgument::REQUIRED);
        $this->addOption('type', null, InputOption::VALUE_REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getArgument('org');
        $type = $input->getOption('type');
        $this->client = new Client(
            new CachedHttpClient(array('cache_dir' => '/tmp'))
        );

        $events = $this->client->api('organization')->events()->all($org);

        foreach ($events as $event) {
            if ($type && !preg_match('&'.$type.'&', $event['type'])) {
                continue;
            }

            $output->writeln(sprintf('%s <comment>%s</comment> - <info>[%s]</info> %s',
                $event['created_at'],
                $event['repo']['name'],
                $event['type'],
                $event['actor']['login']
            ));

            $action= $event['payload']['action'];

            if (isset($event['payload']['issue'])) {
                $issue = $event['payload']['issue'];
                $output->writeln(sprintf(' >> %s %s %s',
                    $action,
                    $issue['number'],
                    $issue['title']
                ));
            }

            $output->writeln('');
        }
    }
}

