<?php

namespace App\Libraries\CrawlerDetect;

class AbstractProvider
{
    protected $data;

    public function getAll()
    {
        return $this->data;
    }
}
