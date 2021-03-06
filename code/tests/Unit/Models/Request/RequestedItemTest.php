<?php
declare(strict_types=1);

namespace Tests\Unit\Models\Request;

use App\Models\Request\RequestedItem;
use Tests\TestCase;

/**
 * Class RequestedItemTest
 * @package Tests\Unit\Models\Request
 */
class RequestedItemTest extends TestCase
{
    public function testAsset()
    {
        $model = new RequestedItem();
        $relation = $model->asset();

        $this->assertEquals('assets.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('requested_items.asset_id', $relation->getQualifiedForeignKeyName());
    }

    public function testLocation()
    {
        $model = new RequestedItem();
        $relation = $model->location();

        $this->assertEquals('locations.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('requested_items.location_id', $relation->getQualifiedForeignKeyName());
    }

    public function testParentRequestedItem()
    {
        $model = new RequestedItem();
        $relation = $model->parentRequestedItem();

        $this->assertEquals('requested_items.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('requested_items.parent_requested_item_id', $relation->getQualifiedForeignKeyName());
    }

    public function testRequest()
    {
        $model = new RequestedItem();
        $relation = $model->request();

        $this->assertEquals('requests.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('requested_items.request_id', $relation->getQualifiedForeignKeyName());
    }
}