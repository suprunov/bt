<?php

namespace App\Libraries\CrawlerDetect;

class CrawlerDetect
{

    protected $userAgent;
    protected $httpHeaders = [];
    protected $matches = [];
    protected $crawlers;
    protected $exclusions;
    protected $uaHttpHeaders;
    protected $compiledRegex;
    protected $compiledExclusions;
    protected $inited = false;

    public function init(array $headers = null, $userAgent = null)
    {
        if (!$this->inited) {
            $this->crawlers = new Crawlers();
            $this->exclusions = new Exclusions();
            $this->uaHttpHeaders = new Headers();

            $this->compiledRegex = $this->compileRegex($this->crawlers->getAll());
            $this->compiledExclusions = $this->compileRegex($this->exclusions->getAll());

            $this->setHttpHeaders($headers);
            $this->setUserAgent($userAgent);

            $this->inited = true;
        }
        return $this;
    }

    public function compileRegex($patterns)
    {
        return '(' . implode('|', $patterns) . ')';
    }

    public function setHttpHeaders($httpHeaders)
    {
        // Use global _SERVER if $httpHeaders aren't defined.
        if (!is_array($httpHeaders) || !count($httpHeaders)) {
            $httpHeaders = $_SERVER;
        }

        // Clear existing headers.
        $this->httpHeaders = array();

        // Only save HTTP headers. In PHP land, that means
        // only _SERVER vars that start with HTTP_.
        foreach ($httpHeaders as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $this->httpHeaders[$key] = $value;
            }
        }
    }

    public function setUserAgent($userAgent)
    {
        if (is_null($userAgent)) {
            foreach ($this->getUaHttpHeaders() as $altHeader) {
                if (isset($this->httpHeaders[$altHeader])) {
                    $userAgent .= $this->httpHeaders[$altHeader] . ' ';
                }
            }
        }

        return $this->userAgent = $userAgent;
    }

    public function getUaHttpHeaders()
    {
        return $this->uaHttpHeaders->getAll();
    }

    public function isCrawler($userAgent = null)
    {
        $this->init();

        $agent = trim(preg_replace(
            "/{$this->compiledExclusions}/i",
            '',
            $userAgent ?: $this->userAgent
        ));

        if ($agent === '') {
            return false;
        }

        return (bool)preg_match("/{$this->compiledRegex}/i", $agent, $this->matches);
    }

    public function getMatches()
    {
        return isset($this->matches[0]) ? $this->matches[0] : null;
    }
}
