<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyActivitiesRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_activities_routes_and_table_are_removed(): void
    {
        $this->assertFalse(Route::has('activities.index'));
        $this->assertFalse(Schema::hasTable('daily_activities'));

        $this->get('/aktivitas')->assertNotFound();
        $this->get('/aktivitas/tambah')->assertNotFound();
        $this->post('/aktivitas')->assertNotFound();
    }

    public function test_dashboard_has_no_daily_activities_navigation_or_widget(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Task Manager')
            ->assertSee('Goal Tracker')
            ->assertDontSee('Daily Activity')
            ->assertDontSee('Aktivitas Hari Ini')
            ->assertDontSee('/aktivitas');
    }
}
