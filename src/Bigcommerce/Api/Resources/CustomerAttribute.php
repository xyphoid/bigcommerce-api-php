<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\Resource;
use Bigcommerce\Api\Client;

class CustomerAttribute extends Resource
{
    protected $ignoreOnCreate = array(
        'id',
    );

    protected $ignoreOnUpdate = array(
        'id',
    );

    public function create()
    {
        return Client::createCustomerAttribute($this->getCreateFields());
    }

    public function update()
    {
        return Client::updateCustomerAttribute($this->id, $this->getUpdateFields());
    }

    public function delete()
    {
        return Client::deleteCustomerAttribute($this->id);
    }

    public function getAll()
    {
        return Client::getCustomerAttributes();
    }

    public function get()
    {
        return Client::getCustomerAttribute($this->id);
    }
}
