<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories\Request;

use App\Models\Asset;
use App\Models\Request\RequestedItem;
use App\Models\User\User;
use App\Repositories\Request\RequestedItemRepository;
use App\Repositories\Request\RequestRepository;
use App\Models\Request\Request;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\DatabaseSetupTrait;
use Tests\TestCase;
use Tests\Traits\MocksApplicationLog;

/**
 * Class RequestRepositoryTest
 * @package Tests\Integration\Repositories\Request
 */
class RequestRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var RequestRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new RequestRepository(
            new Request(),
            $this->getGenericLogMock(),
            new RequestedItemRepository(
                new RequestedItem(),
                $this->getGenericLogMock(),
            ),
        );
    }

    public function testFindAllSuccess()
    {
        factory(Request::class, 5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function testFindAllEmpty()
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function testFindOrFailSuccess()
    {
        $model = factory(Request::class)->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function testFindOrFailFails()
    {
        factory(Request::class)->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function testCreateSuccess()
    {
        /** @var Request $model */
        $user = factory(User::class)->create();
        $model = $this->repository->create([
            'requested_by_id' => $user->id,
            'latitude' => 846,
            'longitude' => 235,
            'requested_items' => [
                [
                    'name' => 'An Item',
                ],
                [
                    'name' => 'An Item',
                    'asset_id' => factory(Asset::class)->create()->id,
                ],
            ]
        ]);

        $this->assertEquals($model->requested_by_id, $user->id);
        $this->assertEquals(846, $model->latitude);
        $this->assertEquals(235, $model->longitude);
        $this->assertCount(2, $model->requestedItems);
    }

    public function testUpdateSuccess()
    {
        $model = factory(Request::class)->create();
        $requestedItems = factory(RequestedItem::class, 3)->create([
            'request_id' => $model->id,
        ]);
        $this->repository->update($model, [
            'completed_at' => Carbon::now(),
            'requested_items' => [
                [
                    'id' => $requestedItems[2]->id,
                ],
                [
                    'name' => 'An Item',
                    'asset_id' => factory(Asset::class)->create()->id,
                ]
            ]
        ]);

        /** @var Request $updated */
        $updated = Request::find($model->id);
        $this->assertNotNull($updated->completed_at);
        $this->assertCount(2, $updated->requestedItems);
    }

    public function testDeleteSuccess()
    {
        $model = factory(Request::class)->create();

        $this->repository->delete($model);

        $this->assertNull(Request::find($model->id));
    }

    public function testFindAllAroundLocationEmpty()
    {
        $items = $this->repository->findAllAroundLocation(0, 0, 50);
        $this->assertEmpty($items);
    }

    public function testFindAllAroundLocationRespectsRadius()
    {
        // 100 km at the equator is between 1 and 2 unites of latitude
        $center = factory(Request::class)->create([
            'latitude' => 0,
            'longitude' => 0,
        ]);
        $offCenter = factory(Request::class)->create([
            'latitude' => 0.5,
            'longitude' => 0,
        ]);
        factory(Request::class)->create([
            'latitude' => 0,
            'longitude' => -2.5,
        ]);
        factory(Request::class)->create([
            'latitude' => 1.5,
            'longitude' => 1.5,
        ]);
        factory(Request::class)->create([
            'latitude' => 2.5,
            'longitude' => 0
        ]);
        $items = $this->repository->findAllAroundLocation(0, 0, 100);
        $this->assertCount(2, $items);
        $this->assertContains($center->id, $items->pluck('id'));
        $this->assertContains($offCenter->id, $items->pluck('id'));
    }

    public function testFindAllAroundLocationOrdersProperly()
    {
        $offCenter = factory(Request::class)->create([
            'latitude' => 0.5,
            'longitude' => 0,
        ]);
        $center = factory(Request::class)->create([
            'latitude' => 0,
            'longitude' => 0,
        ]);
        $furthest = factory(Request::class)->create([
            'latitude' => 0,
            'longitude' => -3,
        ]);
        // This is roughly 2.1 in latitude units
        $middle = factory(Request::class)->create([
            'latitude' => 1.5,
            'longitude' => 1.5,
        ]);
        $north = factory(Request::class)->create([
            'latitude' => 2.5,
            'longitude' => 0
        ]);
        $items = $this->repository->findAllAroundLocation(0, 0, 1000);
        $this->assertCount(5, $items);

        $this->assertEquals([
            $center->id,
            $offCenter->id,
            $middle->id,
            $north->id,
            $furthest->id,
        ], $items->pluck('id')->toArray());
    }
}