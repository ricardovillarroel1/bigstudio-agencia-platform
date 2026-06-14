<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Blinda el render de las páginas rebrandeadas (login, register, chat admin):
 * un error de Blade aquí dejaría fuera el acceso al sistema o el centro de chats.
 */
class BrandingUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_renderiza_con_branding(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Bienvenido de vuelta')
            ->assertSee('auth-card', false)
            ->assertSee('BIG STUDIO');
    }

    public function test_register_renderiza_con_branding(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('Crea tu cuenta')
            ->assertSee('Datos de facturación')
            ->assertSee('razon_social', false);
    }

    public function test_admin_chats_renderiza(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/chats')->assertOk()
            ->assertSee('Centro de Comunicaciones')
            ->assertSee('chat-send-btn', false);
    }
}
