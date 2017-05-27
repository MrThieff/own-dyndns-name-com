<?php

class DnsRecord
{
    /** @var string */
    private $hostname = '';

    /** @var string */
    private $type = 'A';

    /**
     * IP address
     *
     * @var string
     */
    private $content = '';

    /** @var int */
    private $ttl = 300;

    /** @var int */
    private $priority = 0;

    public function __construct(string $domainNamePrefix)
    {
        $this->hostname = $domainNamePrefix;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content)
    {
        $this->content = $content;
    }

    public function getRecord(): array
    {
        if (empty($this->content)) {
            throw new MyException('Fill Content property before use it.');
        }

        return get_object_vars($this);
    }
}