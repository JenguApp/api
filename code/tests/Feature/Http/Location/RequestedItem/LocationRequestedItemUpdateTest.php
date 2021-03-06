<?php
declare(strict_types=1);

namespace Tests\Feature\Http\Location\RequestedItem;

use App\Models\Asset;
use App\Models\Organization\Organization;
use App\Models\Organization\Location;
use App\Models\Organization\OrganizationManager;
use App\Models\Request\RequestedItem;
use App\Models\Role;
use Tests\DatabaseSetupTrait;
use Tests\TestCase;
use Tests\Traits\MocksApplicationLog;
use Tests\Traits\RolesTesting;

/**
 * Class OrganizationUpdateTest
 * @package Tests\Feature\Http\Location\RequestedItem
 */
class LocationRequestedItemUpdateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    /**
     * @var string
     */
    private $route;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    /**
     * Sets up the proper route for the request
     *
     * @param int $locationId
     * @param int $requestedItemId
     */
    private function setupRoute(int $locationId, $requestedItemId)
    {
        $this->route = '/v1/locations/' . $locationId . '/requested-items/' . $requestedItemId;
    }

    public function testOrganizationNotFound()
    {
        $this->setupRoute(4523, 345);
        $response = $this->json('PUT', $this->route);
        $response->assertStatus(404);
    }

    public function testNotLoggedInUserBlocked()
    {
        $model = factory(RequestedItem::class)->create([
            'location_id' => factory(Location::class)->create()->id,
        ]);
        $this->setupRoute($model->location_id, $model->id);
        $response = $this->json('PUT', $this->route);
        $response->assertStatus(403);
    }

    public function testNonAdminUsersBlocked()
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = factory(RequestedItem::class)->create([
                'location_id' => factory(Location::class)->create()->id,
            ]);
            $this->setupRoute($model->location_id, $model->id);
            $response = $this->json('PUT', $this->route);

            $response->assertStatus(403);
        }
    }

    public function testNotUserNotOrganizationAdminBlocked()
    {
        $this->actAs(Role::MANAGER);
        $organization = factory(Organization::class)->create();
        factory(OrganizationManager::class)->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::MANAGER,
        ]);
        $model = factory(RequestedItem::class)->create([
            'location_id' => factory(Location::class)->create()->id,
        ]);
        $this->setupRoute($model->location_id, $model->id);
        $response = $this->json('PUT', $this->route);
        $response->assertStatus(403);
    }

    public function testUpdateSuccessful()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = factory(Organization::class)->create();
        factory(OrganizationManager::class)->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $model = factory(RequestedItem::class)->create([
            'name' => 'A Item',
            'location_id' => factory(Location::class)->create([
                'organization_id' => $organization->id,
            ])->id,
        ]);
        $this->setupRoute($model->location_id, $model->id);

        $properties = [
            'name' => 'An Item',
        ];

        $response = $this->json('PUT', $this->route, $properties);

        $response->assertStatus(200);

        /** @var RequestedItem $updated */
        $updated = RequestedItem::find($model->id);

        $this->assertEquals( 'An Item', $updated->name);
    }

    public function testUpdateSuccessfulSetsFieldsNull()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = factory(Organization::class)->create();
        factory(OrganizationManager::class)->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $model = factory(RequestedItem::class)->create([
            'asset_id' => factory(Asset::class)->create()->id,
            'quantity' => 12,
            'max_quantity_per_request' => 12,
            'location_id' => factory(Location::class)->create([
                'organization_id' => $organization->id,
            ])->id,
        ]);
        $this->setupRoute($model->location_id, $model->id);

        $properties = [
            'asset_id' => null,
            'quantity' => null,
            'max_quantity_per_request' => null,
        ];

        $response = $this->json('PUT', $this->route, $properties);

        $response->assertStatus(200);

        /** @var RequestedItem $updated */
        $updated = RequestedItem::find($model->id);

        $this->assertNull($updated->quantity);
        $this->assertNull($updated->max_quantity_per_request);
    }

    public function testUpdateFailsInvalidStringFields()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $model = factory(RequestedItem::class)->create([
            'name' => 'A Item',
            'location_id' => factory(Location::class)->create()->id,
        ]);
        $this->setupRoute($model->location_id, $model->id);
        factory(OrganizationManager::class)->create([
            'organization_id' => $model->location->organization_id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $data = [
            'name' => 3124,
        ];

        $response = $this->json('PUT', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message'   => 'Sorry, something went wrong.',
            'errors'    =>  [
                'name' => ['The name must be a string.'],
            ]
        ]);
    }

    public function testUpdateFailsInvalidNumericFields()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $model = factory(RequestedItem::class)->create([
            'name' => 'A Item',
            'location_id' => factory(Location::class)->create()->id,
        ]);
        $this->setupRoute($model->location_id, $model->id);
        factory(OrganizationManager::class)->create([
            'organization_id' => $model->location->organization_id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $data = [
            'asset_id' => 'hi',
            'quantity' => 'hi',
            'max_quantity_per_request' => 'hi',
        ];

        $response = $this->json('PUT', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message'   => 'Sorry, something went wrong.',
            'errors'    =>  [
                'asset_id' => ['The asset id must be a number.'],
                'quantity' => ['The quantity must be a number.'],
                'max_quantity_per_request' => ['The max quantity per request must be a number.'],
            ]
        ]);
    }

    public function testUpdateFailsModelFieldInvalid()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $model = factory(RequestedItem::class)->create([
            'name' => 'A Item',
            'location_id' => factory(Location::class)->create()->id,
        ]);
        $this->setupRoute($model->location_id, $model->id);
        factory(OrganizationManager::class)->create([
            'organization_id' => $model->location->organization_id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $data = [
            'asset_id' => 45352,
        ];

        $response = $this->json('PUT', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message'   => 'Sorry, something went wrong.',
            'errors'    =>  [
                'asset_id' => ['The selected asset id is invalid.'],
            ]
        ]);
    }
}