<?php

namespace Laracasts\Behat\Context\Services;

use GuzzleHttp\Client;
use Config;
use Exception;

trait MailCatcher
{

    /**
     * The Guzzle client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Get the configuration for MailCatcher.
     *
     * @param integer|null $inboxId
     * @throws Exception
     */
    protected function applyMailCatcherConfiguration($inboxId = null)
    {
    }

    /**
     * Fetch a MailCatcher inbox.
     *
     * @param  integer|null $inboxId
     * @return mixed
     * @throws RuntimeException
     */
    protected function fetchInbox($inboxId = null)
    {
        if ( ! $this->alreadyConfigured()) {
            $this->applyMailCatcherConfiguration($inboxId);
        }

        $body = $this->requestClient()
            ->get($this->getMailCatcherMessagesUrl())
            ->getBody();

        $inbox = $this->parseJson($body);

        foreach($inbox as $key=>$message) {
            $inbox[$key]['html_body'] = (string) $this->requestClient()
                ->get($this->getMailCatcherMessageUrl($message['id']))
                ->getBody();
        }

        return $inbox;
    }

    /**
     *
     * Empty the MailCatcher inbox.
     *
     * @AfterScenario @mail
     */
    public function emptyInbox()
    {
        //$this->requestClient()->patch($this->getMailCatcherCleanUrl());
    }

    /**
     * Get the MailCatcher messages endpoint.
     *
     * @return string
     */
    protected function getMailCatcherMessagesUrl()
    {
        return "/messages";
    }

    protected function getMailCatcherMessageUrl($id)
    {
        return "/messages/$id.html";
    }

    /**
     * Get the MailCatcher "empty inbox" endpoint.
     *
     * @return string
     */
    protected function getMailCatcherCleanUrl()
    {
        return "/api/v1/inboxes/{$this->MailCatcherInboxId}/clean";
    }

    /**
     * Determine if MailCatcher config has been retrieved yet.
     *
     * @return boolean
     */
    protected function alreadyConfigured()
    {
        return false;
    }

    /**
     * Request a new Guzzle client.
     *
     * @return Client
     */
    protected function requestClient()
    {
        if ( ! $this->client) {
            $this->client = new Client([
                'base_uri' => 'http://smtp:80',
            ]);
        }

        return $this->client;
    }

    /**
     * @param $body
     * @return array|mixed
     * @throws RuntimeException
     */
    protected function parseJson($body)
    {
        $data = json_decode((string)$body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException('Unable to parse response body into JSON: '.json_last_error());
        }

        return $data === null ? array() : $data;
    }
}
