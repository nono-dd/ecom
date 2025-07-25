<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Http\Livewire\ApiTokenManager;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class CreateApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function testApiTokensCanBeCreated(): void
    {
        if (!Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        Livewire::test(ApiTokenManager::class)
            ->set(['createApiTokenForm' => [
                'name'        => 'Test Token',
                'permissions' => [
                    'read',
                    'update',
                ],
            ]])
            ->call('createApiToken')
        ;

        $this->assertCount(1, $user->fresh()->tokens);
        $this->assertEquals('Test Token', $user->fresh()->tokens->first()->name);
        $this->assertTrue($user->fresh()->tokens->first()->can('read'));
        $this->assertFalse($user->fresh()->tokens->first()->can('delete'));
    }
}
