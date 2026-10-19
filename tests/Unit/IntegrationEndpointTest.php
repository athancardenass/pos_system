<?php

namespace Tests\Unit;

use App\Services\Integration\Endpoint;
use Tests\TestCase;

class IntegrationEndpointTest extends TestCase
{
    public function test_crm_http_opt_in_is_restricted_to_its_configured_server(): void
    {
        $url = 'http://10.87.175.166:8000';
        config(['services.crm.base_url' => $url, 'services.crm.allow_http' => false]);
        $this->assertFalse(Endpoint::allowed($url, 'crm'));
        config(['services.crm.allow_http' => true]);
        $this->assertTrue(Endpoint::allowed($url, 'crm'));
        $this->assertFalse(Endpoint::allowed($url));
        $this->assertFalse(Endpoint::allowed($url, 'inventory'));
        $this->assertFalse(Endpoint::allowed($url, 'hrms'));
        $this->assertFalse(Endpoint::allowed('http://another-host.test', 'crm'));
        $this->assertFalse(Endpoint::allowed('http://10.87.175.166:8001', 'crm'));
        $this->assertFalse(Endpoint::allowed('http://user:secret@10.87.175.166:8000', 'crm'));
    }

    public function test_http_inventory_opt_in_applies_only_to_the_configured_inventory_url(): void
    {
        $url = 'http://10.36.189.247/inventory-laravel/public';
        config(['services.inventory.base_url' => $url, 'services.inventory.allow_http' => false]);
        $this->assertFalse(Endpoint::allowed($url, 'inventory'));
        config(['services.inventory.allow_http' => true]);
        $this->assertTrue(Endpoint::allowed($url, 'inventory'));
        $this->assertFalse(Endpoint::allowed($url, 'crm'));
        $this->assertFalse(Endpoint::allowed('http://another-host.test', 'inventory'));
        $this->assertFalse(Endpoint::allowed('http://user:secret@10.36.189.247/inventory-laravel/public', 'inventory'));
    }

    public function test_hr_http_opt_in_does_not_enable_other_receivers(): void
    {
        $url = 'http://10.36.189.221/harvest-hr-next/public';
        config(['services.hrms.base_url' => $url, 'services.hrms.allow_http' => false]);
        $this->assertFalse(Endpoint::allowed($url, 'hrms'));
        config(['services.hrms.allow_http' => true]);
        $this->assertTrue(Endpoint::allowed($url, 'hrms'));
        $this->assertFalse(Endpoint::allowed($url, 'crm'));
        $this->assertFalse(Endpoint::allowed('http://another-host.test', 'hrms'));
    }
}
