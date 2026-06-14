<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Models\AgenciaCliente;
use App\Models\AgenciaTarea;
use App\Models\AgenciaTareaComparticion;
use App\Models\AgenciaTareaArchivo;
use App\Mail\TareaNotificacionMail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pack de comunicación de tareas: comentarios bidireccionales, adjuntos/entregables
 * (subida + descarga gateada + borrado), acuse de lectura ("visto"), estados de
 * revisión y notificaciones por correo a la contraparte.
 */
class AgenciaTareasPackTest extends TestCase
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

    private function colaborador(array $permisos = ['agencia.tareas.mias']): User
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
        return AgenciaCliente::create(['nombre' => 'Cliente Pack', 'email' => 'cp@test.cl', 'estado' => 'activo']);
    }

    private function tareaCon(User $admin, AgenciaCliente $cli, ?User $disen = null): AgenciaTarea
    {
        $t = AgenciaTarea::create([
            'agencia_cliente_id' => $cli->id,
            'titulo' => 'Tarea pack',
            'estado' => 'en_curso',
            'prioridad' => 'media',
            'creado_por' => $admin->id,
        ]);
        if ($disen) {
            AgenciaTareaComparticion::create(['agencia_tarea_id' => $t->id, 'user_id' => $disen->id, 'email' => $disen->email, 'compartida_en' => now()]);
        }
        return $t;
    }

    public function test_admin_comenta_y_notifica_al_colaborador(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $disen = $this->colaborador();
        $t = $this->tareaCon($admin, $this->cliente(), $disen);

        $this->actingAs($admin)->post(route('agencia.tareas.comentarios.store', $t), ['cuerpo' => 'Cambia el color del banner'])->assertRedirect();

        $this->assertDatabaseHas('agencia_tarea_comentarios', ['agencia_tarea_id' => $t->id, 'rol' => 'admin', 'cuerpo' => 'Cambia el color del banner']);
        Mail::assertSent(TareaNotificacionMail::class, 1);
    }

    public function test_colaborador_comenta_solo_en_tareas_compartidas(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $disen = $this->colaborador();
        $compartida = $this->tareaCon($admin, $this->cliente(), $disen);
        $ajena = $this->tareaCon($admin, $this->cliente(), null);

        $this->actingAs($disen)->post(route('agencia.mis-tareas.comentarios.store', $compartida), ['cuerpo' => 'Listo, subo entregable'])->assertRedirect();
        $this->assertDatabaseHas('agencia_tarea_comentarios', ['agencia_tarea_id' => $compartida->id, 'rol' => 'colaborador']);

        $this->actingAs($disen)->post(route('agencia.mis-tareas.comentarios.store', $ajena), ['cuerpo' => 'X'])->assertForbidden();
    }

    public function test_admin_sube_brief(): void
    {
        Mail::fake();
        Storage::fake('public');
        $admin = $this->admin();
        $t = $this->tareaCon($admin, $this->cliente());

        $this->actingAs($admin)->post(route('agencia.tareas.archivos.store', $t), [
            'archivo' => UploadedFile::fake()->create('brief.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $arch = AgenciaTareaArchivo::where('agencia_tarea_id', $t->id)->first();
        $this->assertNotNull($arch);
        $this->assertEquals('brief', $arch->tipo);
        Storage::disk('public')->assertExists($arch->ruta);
    }

    public function test_colaborador_sube_entregable_y_descarga_gateada(): void
    {
        Mail::fake();
        Storage::fake('public');
        $admin = $this->admin();
        $disen = $this->colaborador();
        $t = $this->tareaCon($admin, $this->cliente(), $disen);

        $this->actingAs($disen)->post(route('agencia.mis-tareas.archivos.store', $t), [
            'archivo' => UploadedFile::fake()->image('entrega.png'),
        ])->assertRedirect();

        $arch = AgenciaTareaArchivo::where('agencia_tarea_id', $t->id)->first();
        $this->assertEquals('entregable', $arch->tipo);

        // El colaborador compartido puede descargar.
        $this->actingAs($disen)->get(route('agencia.tareas.archivos.descargar', $arch))->assertOk();

        // Otro colaborador no compartido -> 403.
        $otro = $this->colaborador();
        $this->actingAs($otro)->get(route('agencia.tareas.archivos.descargar', $arch))->assertForbidden();
    }

    public function test_eliminar_archivo_solo_autor_o_admin(): void
    {
        Mail::fake();
        Storage::fake('public');
        $admin = $this->admin();
        $disen = $this->colaborador();
        $t = $this->tareaCon($admin, $this->cliente(), $disen);
        $this->actingAs($disen)->post(route('agencia.mis-tareas.archivos.store', $t), ['archivo' => UploadedFile::fake()->image('e.png')]);
        $arch = AgenciaTareaArchivo::where('agencia_tarea_id', $t->id)->first();

        $otro = $this->colaborador();
        $this->actingAs($otro)->delete(route('agencia.tareas.archivos.destroy', $arch))->assertForbidden();

        $this->actingAs($disen)->delete(route('agencia.tareas.archivos.destroy', $arch))->assertRedirect();
        $this->assertDatabaseMissing('agencia_tarea_archivos', ['id' => $arch->id]);
    }

    public function test_visto_se_marca_al_abrir_el_panel(): void
    {
        $admin = $this->admin();
        $disen = $this->colaborador();
        $t = $this->tareaCon($admin, $this->cliente(), $disen);
        $comp = AgenciaTareaComparticion::where('agencia_tarea_id', $t->id)->first();
        $this->assertNull($comp->primer_acceso_en);

        $this->actingAs($disen)->get(route('agencia.mis-tareas'))->assertOk();

        $comp->refresh();
        $this->assertNotNull($comp->primer_acceso_en);
    }

    public function test_estado_en_revision_es_valido_y_notifica_al_admin(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $disen = $this->colaborador();
        $t = $this->tareaCon($admin, $this->cliente(), $disen);

        $this->actingAs($disen)->patch(route('agencia.mis-tareas.estado', $t), ['estado' => 'en_revision'])->assertRedirect();
        $t->refresh();
        $this->assertEquals('en_revision', $t->estado);
        Mail::assertSent(TareaNotificacionMail::class, 1);
    }

    public function test_admin_pide_cambios_y_notifica_al_colaborador(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $disen = $this->colaborador();
        $t = $this->tareaCon($admin, $this->cliente(), $disen);

        $this->actingAs($admin)->patch(route('agencia.tareas.estado', $t), ['estado' => 'requiere_cambios'])->assertRedirect();
        $t->refresh();
        $this->assertEquals('requiere_cambios', $t->estado);
        Mail::assertSent(TareaNotificacionMail::class, 1);
    }

    public function test_conversacion_se_renderiza_en_ambas_vistas(): void
    {
        Mail::fake();
        Storage::fake('public');
        $admin = $this->admin();
        $disen = $this->colaborador();
        $cli = $this->cliente();
        $t = $this->tareaCon($admin, $cli, $disen);

        $this->actingAs($admin)->post(route('agencia.tareas.comentarios.store', $t), ['cuerpo' => 'Revisa el brief adjunto']);
        $this->actingAs($admin)->post(route('agencia.tareas.archivos.store', $t), ['archivo' => UploadedFile::fake()->create('brief.pdf', 30, 'application/pdf')]);

        $this->actingAs($admin)->get(route('agencia.clientes.detalle', $cli))->assertOk()
            ->assertSee('Revisa el brief adjunto')->assertSee('brief.pdf');
        $this->actingAs($disen)->get(route('agencia.mis-tareas'))->assertOk()
            ->assertSee('Revisa el brief adjunto')->assertSee('brief.pdf');
    }
}
