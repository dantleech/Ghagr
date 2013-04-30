<?php

namespace DTL\Ghag;

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use Github\Exception\RuntimeException;

class Aggregator
{
    protected $logger;
    protected $client;

    public function setLoggerClosure(\Closure $closure)
    {
        $this->logger = $closure;
    }

    public function log($message)
    {
        if ($this->logger) {
            $logger = $this->logger;
            $logger($message);
        }
    }

    public function aggregate($repos)
    {
        $this->client = new Client(
            new CachedHttpClient(array('cache_dir' => '/tmp'))
        );

        $dom = new \DOMDocument("1.0");
        $root = $dom->createElement('ghag');
        $root->setAttribute('date', date('c'));
        $dom->appendChild($root);

        foreach ($repos as $username => $repoConfig) {
            foreach ($repoConfig as $repoName => $repoData) {
                $this->log('Getting issues for '.$username.'/'.$repoName);

                $repoEl = $dom->createElement('repository');
                $repoEl->setAttribute('username', $username);
                $repoEl->setAttribute('name', $repoName);
                $root->appendChild($repoEl);

                try {
                    $repoIssues = $this->gitIssues($username, $repoName);
                } catch (RuntimeException $e) {
                    $this->log('<error>Could locate repo "'.$username.'/'.$repoName.'": '.$e->getMessage());
                    continue;
                }

                foreach ($repoIssues as $key => $repoIssue) {
                    $issueEl = $dom->createElement('issue');
                    $this->populateEl($issueEl, $repoIssue);
                    $repoEl->appendChild($issueEl);

                    try {
                        $comments = $this->client->api('issue')->comments()->all($username, $repoName, $repoIssue['number']);
                    } catch (RuntimeException $e) {
                        $this->log('<error>Could not fetch comments: '.$e->getMessage());
                    }

                    foreach ($comments as $comment) {
                        $commentEl = $dom->createElement('comment');
                        $this->populateEl($commentEl, $comment);
                        $issueEl->appendChild($commentEl);
                    }
                }
            }
        }

        return $dom;
    }

    public function gitIssues($username, $repoName)
    {
        $issues = $this->client->api('issue')->all($username, $repoName, array('state' => 'open'));
        return $issues;
    }

    public function populateEl(\DOMElement $el, $array)
    {
        foreach ($array as $key => $value) {
            if (is_scalar($value)) {
                $el->setAttribute($key, $value);
            } elseif (is_array($value) && in_array(
                $key,
                array(
                    'user', 
                    'assignee',
                    'milestone',
                    'comments',
                )
            ) && is_string($key)) {
                $childEl = $el->ownerDocument->createElement($key);
                $this->populateEl($childEl, $value);
                $el->appendChild($childEl);
            } else {
                $this->log(' > Ignoring ['.gettype($value).'] field "'.$key.'"');
            }
        }
    }
}
