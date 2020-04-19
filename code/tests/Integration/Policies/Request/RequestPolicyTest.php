<?php
declare(strict_types=1);

namespace Tests\Integration\Policies\Request;

use App\Models\Request\Request;
use App\Models\User\User;
use App\Policies\Request\RequestPolicy;
use Tests\TestCase;

/**
 * Class RequestPolicyTest
 * @package Tests\Integration\Policies\Request
 */
class RequestPolicyTest extends TestCase
{
    public function testAll()
    {
        $policy = new RequestPolicy();

        $this->assertTrue($policy->all(new User()));
    }

    public function testCreate()
    {
        $policy = new RequestPolicy();

        $this->assertTrue($policy->create(new User()));
    }

    public function  testUpdatePassesWhenNoOneIsCompletingRequest()
    {
        $policy = new RequestPolicy();

        $this->assertTrue($policy->update(new User(), new Request()));
    }

    public function testViewFails()
    {
        $policy = new RequestPolicy();

        $user = new User();
        $user->id = 24354;

        $request = new Request([
            'completed_by_id' => 314,
            'requested_by_id' => 235,
        ]);

        $this->assertFalse($policy->view($user, $request));
    }

    public function testViewPasses()
    {
        $policy = new RequestPolicy();

        $request = new Request([
            'completed_by_id' => 314,
            'requested_by_id' => 235,
        ]);

        $user = new User();
        $user->id = 314;

        $this->assertTrue($policy->view($user, $request));

        $user->id = 235;
        $this->assertTrue($policy->view($user, $request));
    }

    public function  testUpdateFailsWhenUserIsNotCompletingTheRequest()
    {
        $policy = new RequestPolicy();

        $request = new Request([
            'completed_by_id' => 314,
        ]);

        $this->assertFalse($policy->update(new User(), $request));
    }

    public function  testUpdatePasses()
    {
        $policy = new RequestPolicy();

        $request = new Request([
            'completed_by_id' => 314,
        ]);

        $user = new User();
        $user->id = 314;

        $this->assertTrue($policy->update($user, $request));
    }
}