<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Cliente delgado de la API de Notion para el módulo de Agencia.
 * Lee/escribe las bases de Tareas y Clientes del workspace BigStudio.
 */
class NotionService
{
    protected string $base = 'https://api.notion.com/v1';

    protected function http()
    {
        return Http::withToken(config('notion.token'))
            ->withHeaders(['Notion-Version' => config('notion.version')])
            ->baseUrl($this->base)
            ->acceptJson()
            ->timeout(20);
    }

    public function configurado(): bool
    {
        return filled(config('notion.token'));
    }

    /** Consulta todas las páginas de un database (resuelve la paginación). */
    public function queryDatabase(string $databaseId, array $body = []): array
    {
        $results = [];
        $cursor = null;
        do {
            $payload = $body;
            $payload['page_size'] = 100; // garantiza que el body se serialice como objeto JSON (no [])
            if ($cursor) {
                $payload['start_cursor'] = $cursor;
            }
            $resp = $this->http()->post("/databases/{$databaseId}/query", $payload)->throw()->json();
            $results = array_merge($results, $resp['results'] ?? []);
            $cursor = ($resp['has_more'] ?? false) ? ($resp['next_cursor'] ?? null) : null;
        } while ($cursor);

        return $results;
    }

    /** Tareas mapeadas a un array simple para las vistas. */
    public function tareas(): array
    {
        return array_map([$this, 'mapTarea'], $this->queryDatabase(config('notion.db_tareas')));
    }

    /** Clientes mapeados a un array simple. */
    public function clientes(): array
    {
        return array_map([$this, 'mapCliente'], $this->queryDatabase(config('notion.db_clientes')));
    }

    public function crearTarea(array $props): array
    {
        return $this->http()->post('/pages', [
            'parent'     => ['database_id' => config('notion.db_tareas')],
            'properties' => $this->propsTarea($props),
        ])->throw()->json();
    }

    public function actualizarTarea(string $pageId, array $props): array
    {
        return $this->http()->patch("/pages/{$pageId}", [
            'properties' => $this->propsTarea($props),
        ])->throw()->json();
    }

    public function archivarTarea(string $pageId): array
    {
        return $this->http()->patch("/pages/{$pageId}", ['archived' => true])->throw()->json();
    }

    // ---------- Mapping Notion -> array simple ----------

    protected function mapTarea(array $p): array
    {
        $props = $p['properties'] ?? [];
        return [
            'id'           => $p['id'],
            'url'          => $p['url'] ?? null,
            'titulo'       => $this->plainTitle($props['Tarea'] ?? null),
            'cliente'      => $this->selectName($props['Cliente'] ?? null),
            'area'         => $this->selectName($props['Área'] ?? null),
            'responsable'  => $this->selectName($props['Responsable'] ?? null),
            'estado'       => $this->selectName($props['Estado'] ?? null),
            'prioridad'    => $this->selectName($props['Prioridad'] ?? null),
            'fecha_limite' => $this->dateStart($props['Fecha límite'] ?? null),
            'notas'        => $this->plainRichText($props['Notas'] ?? null),
        ];
    }

    protected function mapCliente(array $p): array
    {
        $props = $p['properties'] ?? [];
        return [
            'id'         => $p['id'],
            'url'        => $p['url'] ?? null,
            'nombre'     => $this->plainTitle($props['Nombre'] ?? null),
            'estado'     => $this->selectName($props['Estado'] ?? null),
            'sitio_web'  => $props['Sitio web']['url'] ?? null,
            'email'      => $props['Email']['email'] ?? null,
            'telefono'   => $props['Teléfono']['phone_number'] ?? null,
            'rubro'      => $this->plainRichText($props['Rubro'] ?? null),
            'servicios'  => $this->multiSelect($props['Servicios'] ?? null),
            'plataforma' => $this->multiSelect($props['Plataforma'] ?? null),
            'notas'      => $this->plainRichText($props['Notas'] ?? null),
        ];
    }

    /** Construye el bloque "properties" solo con los campos provistos. */
    protected function propsTarea(array $d): array
    {
        $out = [];
        if (isset($d['titulo'])) {
            $out['Tarea'] = ['title' => [['text' => ['content' => $d['titulo']]]]];
        }
        if (isset($d['estado'])) {
            $out['Estado'] = ['select' => ['name' => $d['estado']]];
        }
        if (isset($d['prioridad'])) {
            $out['Prioridad'] = ['select' => ['name' => $d['prioridad']]];
        }
        if (isset($d['cliente'])) {
            $out['Cliente'] = ['select' => ['name' => $d['cliente']]];
        }
        if (isset($d['area'])) {
            $out['Área'] = ['select' => ['name' => $d['area']]];
        }
        if (isset($d['responsable'])) {
            $out['Responsable'] = ['select' => ['name' => $d['responsable']]];
        }
        if (array_key_exists('fecha_limite', $d)) {
            $out['Fecha límite'] = $d['fecha_limite'] ? ['date' => ['start' => $d['fecha_limite']]] : ['date' => null];
        }
        if (isset($d['notas'])) {
            $out['Notas'] = ['rich_text' => [['text' => ['content' => $d['notas']]]]];
        }
        return $out;
    }

    // ---------- helpers de extracción ----------
    protected function plainTitle($prop): string
    {
        return collect($prop['title'] ?? [])->pluck('plain_text')->join('');
    }

    protected function plainRichText($prop): string
    {
        return collect($prop['rich_text'] ?? [])->pluck('plain_text')->join('');
    }

    protected function selectName($prop): ?string
    {
        return $prop['select']['name'] ?? null;
    }

    protected function multiSelect($prop): array
    {
        return collect($prop['multi_select'] ?? [])->pluck('name')->all();
    }

    protected function dateStart($prop): ?string
    {
        return $prop['date']['start'] ?? null;
    }
}
