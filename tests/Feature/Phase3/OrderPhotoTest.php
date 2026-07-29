<?php

namespace Tests\Feature\Phase3;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_valid_image_upload_succeeds(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $file = UploadedFile::fake()->image('stain.jpg', 200, 200);

        $response = $this->actingAs($user)->post(route('orders.photos.store', $order), [
            'photo' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('order_photos', 1);
    }

    public function test_stored_filename_is_random_not_original(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $file = UploadedFile::fake()->image('secret-original-name.jpg', 200, 200);

        $this->actingAs($user)->post(route('orders.photos.store', $order), ['photo' => $file]);

        $photo = OrderPhoto::first();
        $this->assertStringNotContainsString('secret-original-name', $photo->disk_path);
    }

    public function test_non_image_file_rejected(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $file = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');

        $response = $this->actingAs($user)->post(route('orders.photos.store', $order), ['photo' => $file]);

        $response->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('order_photos', 0);
    }

    public function test_oversized_file_rejected(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $file = UploadedFile::fake()->image('huge.jpg')->size(9000);

        $response = $this->actingAs($user)->post(route('orders.photos.store', $order), ['photo' => $file]);

        $response->assertSessionHasErrors('photo');
    }

    public function test_user_from_different_location_cannot_upload_photo(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $order = Order::factory()->create(['location_id' => $location2->id]);

        $file = UploadedFile::fake()->image('stain.jpg');

        $response = $this->actingAs($user)->post(route('orders.photos.store', $order), ['photo' => $file]);

        $response->assertForbidden();
    }

    public function test_photo_upload_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $this->actingAs($user)->post(route('orders.photos.store', $order), [
            'photo' => UploadedFile::fake()->image('stain.jpg', 200, 200),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'OrderPhoto',
            'action' => 'created',
        ]);
    }

    public function test_user_from_different_location_cannot_view_photo(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $order = Order::factory()->create(['location_id' => $location1->id]);

        $this->actingAs($owner)->post(route('orders.photos.store', $order), [
            'photo' => UploadedFile::fake()->image('stain.jpg', 200, 200),
        ]);
        $photo = OrderPhoto::first();

        $otherUser = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location2->id]);
        $response = $this->actingAs($otherUser)->get(route('orders.photos.show', [$order, $photo]));

        $response->assertForbidden();
    }
}
