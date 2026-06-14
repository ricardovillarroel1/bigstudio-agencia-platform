<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\AgenciaCliente;
use App\Models\AgenciaTarea;
use App\Models\AgenciaTareaComparticion;
use App\Mail\TareaCompartidaMail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cubre el módulo de tareas de agencia: CRUD admin, estados, compartir por correo
 * (varios destinatarios) y —lo más importante— el control de acceso del colaborador
 * (ve solo lo compartido; panel completo solo con el permiso; no toca tareas ajenas).
 */
class AgenciaTareasTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('admin');
        return $u;
    }

    private function colaborador(array $permisos = []): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'colaborador', 'guard_name' => 'web']);
        foreach (['agencia.tareas', 'agencia.tareas.mias'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $u = User::factory()->create(['role' => 'colaborador']);
        $u->assignRole('colaborador');
        if ($permisos) {
            $u->syncPermissions($permisos);
        }
        return $u;
    }

    private function cliente(): AgenciaCliente
    {
        return AgenciaCliente::create(['nombre' => 'Cliente Tareas', 'email' => 'ct@test.cl', 'estado' => 'activo']);
    }

    private function tarea(AgenciaCliente $cli, string $titulo, string $estado = 'pendiente'): AgenciaTarea
    {
        return AgenciaTarea::create([
            'agencia_cliente_id' => $cli->id,
            'titulo' => $titulo,
            'estado' => $estado,
            'prioridad' => 'media',
        ]);
    }

    public function test_admin_crea_tarea(): void
    {
        $admin = $this->admin();
        $cli = $this->cliente();

        $this->actingAs($admin)->post(route('agencia.tareas.store'), [
            'agencia_cliente_id' => $cli->id,
            'titulo' => 'Disenar banner',
            'descripcion' => 'Banner home',
            'estado' => 'pendiente',
            'prioridad' => 'alta',
        ])->assertRedirect();

        $t = AgenciaTarea::where('titulo', 'Disenar banner')->first();
        $this->assertNotNull($t);
        $this->assertEquals($cli->id, $t->agencia_cliente_id);
        $this->assertEquals($admin->id, $t->creado_por);
    }

    public function test_estado_terminado_setea_y_limpia_terminada_en(): void
    {
        $admin = $this->admin();
        $t = $this->tarea($this->cliente(), 'X');

        $this->actingAs($admin)->patch(route('agencia.tareas.estado', $t), ['estado' => 'terminado'])->assertRedirect();
        $t->refresh();
        $this->assertEquals('terminado', $t->estado);
        $this->assertNotNull($t->terminada_en);

        $this->actingAs($admin)->patch(route('agencia.tareas.estado', $t), ['estado' => 'pendiente'])->assertRedirect();
        $t->refresh();
        $this->assertNull($t->terminada_en);
    }

    public function test_compartir_crea_comparticiones_y_envia_correos(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $t = $this->tarea($this->cliente(), 'Compartir');

        $this->actingAs($admin)->post(route('agencia.tareas.compartir', $t), [
            'emails' => ['dis1@test.cl', 'dis2@test.cl'],
        ])->assertRedirect();

        $this->assertEquals(2, AgenciaTareaComparticion::where('agencia_tarea_id', $t->id)->count());
        Mail::assertSent(TareaCompartidaMail::class, 2);
    }

    public function test_compartir_enlaza_user_existente(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $t = $this->tarea($this->cliente(), 'T');
        $disen = $this->colaborador(['agencia.tareas.mias']);

        $this->actingAs($admin)->post(route('agencia.tareas.compartir', $t), ['emails' => [$disen->email]])->assertRedirect();

        $comp = AgenciaTareaComparticion::where('agencia_tarea_id', $t->id)->first();
        $this->assertEquals($disen->id, $comp->user_id);
    }

    public function test_colaborador_ve_solo_sus_tareas(): void
    {
        $cli = $this->cliente();
        $disen = $this->colaborador(['agencia.tareas.mias']);

        $mia = $this->tarea($cli, 'Tarea Mia');
        $this->tarea($cli, 'Tarea Ajena');
        AgenciaTareaComparticion::create(['agencia_tarea_id' => $mia->id, 'user_id' => $disen->id, 'email' => $disen->email, 'compartida_en' => now()]);

        $resp = $this->actingAs($disen)->get(route('agencia.mis-tareas'));
        $resp->assertOk();
        $resp->assertSee('Tarea Mia');
        $resp->assertDontSee('Tarea Ajena');
    }

    public function test_colaborador_ve_tarea_compartida_solo_por_email(): void
    {
        $cli = $this->cliente();
        $disen = $this->colaborador(['agencia.tareas.mias']);
        $t = $this->tarea($cli, 'Compartida por email');
        // Comparticion SOLO por email (user_id nulo): como si se compartió antes de crear la cuenta.
        AgenciaTareaComparticion::create(['agencia_tarea_id' => $t->id, 'user_id' => null, 'email' => $disen->email, 'compartida_en' => now()]);

        $this->actingAs($disen)->get(route('agencia.mis-tareas'))->assertOk()->assertSee('Compartida por email');
    }

    public function test_colaborador_sin_permiso_recibe_403(): void
    {
        $disen = $this->colaborador([]);
        $this->actingAs($disen)->get(route('agencia.mis-tareas'))->assertForbidden();
    }

    public function test_colaborador_con_panel_ve_todas(): void
    {
        $cli = $this->cliente();
        $disen = $this->colaborador(['agencia.tareas']);

        $this->tarea($cli, 'Tarea Ajena');

        $resp = $this->actingAs($disen)->get(route('agencia.mis-tareas'));
        $resp->assertOk();
        $resp->assertSee('Tarea Ajena');
    }

    public function test_colaborador_actualiza_estado_de_tarea_compartida(): void
    {
        $cli = $this->cliente();
        $disen = $this->colaborador(['agencia.tareas.mias']);
        $t = $this->tarea($cli, 'T');
        AgenciaTareaComparticion::create(['agencia_tarea_id' => $t->id, 'user_id' => $disen->id, 'email' => $disen->email, 'compartida_en' => now()]);

        $this->actingAs($disen)->patch(route('agencia.mis-tareas.estado', $t), ['estado' => 'en_curso'])->assertRedirect();
        $t->refresh();
        $this->assertEquals('en_curso', $t->estado);
    }

    public function test_colaborador_no_puede_actualizar_tarea_no_compartida(): void
    {
        $cli = $this->cliente();
        $disen = $this->colaborador(['agencia.tareas.mias']);
        $t = $this->tarea($cli, 'Ajena');

        $this->actingAs($disen)->patch(route('agencia.mis-tareas.estado', $t), ['estado' => 'terminado'])->assertForbidden();
        $t->refresh();
        $this->assertEquals('pendiente', $t->estado);
    }

    public function test_admin_carga_panel_de_tareas(): void
    {
        $admin = $this->admin();
        $this->tarea($this->cliente(), 'Una tarea visible');
        $this->actingAs($admin)->get(route('agencia.tareas'))->assertOk()->assertSee('Una tarea visible');
    }

    public function test_admin_carga_detalle_de_cliente(): void
    {
        $admin = $this->admin();
        $cli = $this->cliente();
        $this->tarea($cli, 'Tarea del detalle');
        $this->actingAs($admin)->get(route('agencia.clientes.detalle', $cli))->assertOk()->assertSee('Tarea del detalle');
    }

    public function test_listado_de_clientes_renderiza(): void
    {
        $admin = $this->admin();
        AgenciaCliente::create(['nombre' => 'Render Test', 'proyecto' => 'PROYECTO X', 'estado' => 'activo', 'email' => 'r@r.cl']);
        // Cubre el listado completo (badge de tareas pendientes incluido): una directiva
        // Blade mal pegada aquí rompió producción con ParseError sin que view:cache lo detectara.
        $this->actingAs($admin)->get(route('agencia.clientes'))->assertOk()->assertSee('Render Test');
    }

    public function test_filtro_de_tareas_busca_por_proyecto(): void
    {
        $admin = $this->admin();
        $cliA = AgenciaCliente::create(['nombre' => 'Andres', 'proyecto' => 'BOTAS MILITARES', 'estado' => 'activo', 'email' => 'a@a.cl']);
        $cliB = AgenciaCliente::create(['nombre' => 'Otro', 'proyecto' => 'TIENDA ZAPATOS', 'estado' => 'activo', 'email' => 'b@b.cl']);
        $this->tarea($cliA, 'Tarea de botas');
        $this->tarea($cliB, 'Tarea de zapatos');

        $resp = $this->actingAs($admin)->get(route('agencia.tareas', ['buscar' => 'BOTAS']));
        $resp->assertOk();
        $resp->assertSee('Tarea de botas');
        $resp->assertDontSee('Tarea de zapatos');
        $resp->assertSee('Andres (BOTAS MILITARES)');
    }
}
