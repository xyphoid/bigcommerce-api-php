<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\Resource;
use Bigcommerce\Api\Client;

class Script extends Resource
{
    protected $ignoreOnCreate = array(
        'id',
    );

    protected $ignoreOnUpdate = array(
        'id',
    );

    public function create()
    {
        return Client::createScript($this->getCreateFields());
    }

    public function update()
    {
        return Client::updateScript($this->id, $this->getUpdateFields());
    }

    public function delete()
    {
        return Client::deleteScript($this->id);
    }

    public function getAll()
    {
        return Client::getScripts();
    }

    public function get()
    {
        return Client::getScript($this->id);
    }
}
