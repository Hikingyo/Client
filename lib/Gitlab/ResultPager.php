<?php

declare(strict_types=1);

namespace Gitlab;

use Gitlab\Api\ApiInterface;
use Gitlab\HttpClient\Message\ResponseMediator;

/**
 * Pager class for supporting pagination in Gitlab classes.
 */
class ResultPager implements ResultPagerInterface
{
    /**
     * @var \Gitlab\Client client
     */
    protected $client;

    /**
     * The Gitlab client to use for pagination. This must be the same
     * instance that you got the Api instance from, i.e.:.
     *
     * $client = new \Gitlab\Client();
     * $api = $client->repositories();
     * $pager = new \Gitlab\ResultPager($client);
     *
     * @param \Gitlab\Client $client
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(ApiInterface $api, string $method, array $parameters = [])
    {
        return $api->$method(...$parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(ApiInterface $api, string $method, array $parameters = [])
    {
        $result = $api->$method(...$parameters);
        while ($this->hasNext()) {
            $result = array_merge($result, $this->fetchNext());
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNext()
    {
        return $this->has('next');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNext()
    {
        return $this->get('next');
    }

    /**
     * {@inheritdoc}
     */
    public function hasPrevious()
    {
        return $this->has('prev');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchPrevious()
    {
        return $this->get('prev');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirst()
    {
        return $this->get('first');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchLast()
    {
        return $this->get('last');
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function has($key)
    {
        $lastResponse = $this->client->getLastResponse();
        if (null == $lastResponse) {
            return false;
        }

        $pagination = ResponseMediator::getPagination($lastResponse);
        if (null == $pagination) {
            return false;
        }

        return isset($pagination[$key]);
    }

    /**
     * @param string $key
     *
     * @return array<string,mixed>
     */
    protected function get($key)
    {
        if (!$this->has($key)) {
            return [];
        }

        $pagination = ResponseMediator::getPagination($this->client->getLastResponse());

        /** @var array<string,mixed> */
        return ResponseMediator::getContent($this->client->getHttpClient()->get($pagination[$key]));
    }
}
