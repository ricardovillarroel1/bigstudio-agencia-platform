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

    /** Una tarea por su ID (para la vista de detalle). */
    public function tarea(string $pageId): array
    {
        return $this->mapTarea($this->http()->get("/pages/{$pageId}")->throw()->json());
    }

    /** Agrega contenido al cuerpo de la página, convirtiendo Markdown simple a bloques de Notion. */
    public function agregarNota(string $pageId, string $texto): array
    {
        $children = $this->markdownABloques($texto);
        if (empty($children)) {
            return [];
        }
        return $this->http()->patch("/blocks/{$pageId}/children", ['children' => $children])->throw()->json();
    }

    /** Convierte texto Markdown simple en bloques de Notion (títulos, viñetas, numeradas, citas, párrafos). */
    protected function markdownABloques(string $texto): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $texto) as $linea) {
            $l = rtrim($linea);
            if (trim($l) === '') {
                continue;
            }
            if (preg_match('/^###\s+(.*)/', $l, $mm)) {
                $out[] = $this->bloqueTexto('heading_3', $mm[1]);
            } elseif (preg_match('/^##\s+(.*)/', $l, $mm)) {
                $out[] = $this->bloqueTexto('heading_2', $mm[1]);
            } elseif (preg_match('/^#\s+(.*)/', $l, $mm)) {
                $out[] = $this->bloqueTexto('heading_2', $mm[1]);
            } elseif (preg_match('/^[-*]\s+(.*)/', $l, $mm)) {
                $out[] = $this->bloqueTexto('bulleted_list_item', $mm[1]);
            } elseif (preg_match('/^\d+\.\s+(.*)/', $l, $mm)) {
                $out[] = $this->bloqueTexto('numbered_list_item', $mm[1]);
            } elseif (preg_match('/^>\s+(.*)/', $l, $mm)) {
                $out[] = $this->bloqueTexto('quote', $mm[1]);
            } else {
                $out[] = $this->bloqueTexto('paragraph', $l);
            }
        }
        return $out;
    }

    protected function bloqueTexto(string $type, string $text): array
    {
        return ['object' => 'block', 'type' => $type, $type => ['rich_text' => [['type' => 'text', 'text' => ['content' => $text]]]]];
    }

    public function actualizarCliente(string $pageId, array $d): array
    {
        return $this->http()->patch("/pages/{$pageId}", ['properties' => $this->propsCliente($d)])->throw()->json();
    }

    protected function propsCliente(array $d): array
    {
        $out = [];
        if (array_key_exists('nombre', $d)) {
            $out['Nombre'] = ['title' => [['text' => ['content' => (string) $d['nombre']]]]];
        }
        if (array_key_exists('estado', $d)) {
            $out['Estado'] = ($d['estado'] !== null && $d['estado'] !== '') ? ['select' => ['name' => $d['estado']]] : ['select' => null];
        }
        if (array_key_exists('sitio_web', $d)) {
            $out['Sitio web'] = ['url' => $d['sitio_web'] ?: null];
        }
        if (array_key_exists('email', $d)) {
            $out['Email'] = ['email' => $d['email'] ?: null];
        }
        if (array_key_exists('telefono', $d)) {
            $out['Teléfono'] = ['phone_number' => $d['telefono'] ?: null];
        }
        if (array_key_exists('rubro', $d)) {
            $out['Rubro'] = ['rich_text' => [['text' => ['content' => (string) $d['rubro']]]]];
        }
        if (array_key_exists('notas', $d)) {
            $out['Notas'] = ['rich_text' => [['text' => ['content' => (string) $d['notas']]]]];
        }
        foreach (['servicios' => 'Servicios', 'plataforma' => 'Plataforma'] as $k => $prop) {
            if (array_key_exists($k, $d)) {
                $vals = array_filter(array_map('trim', (array) $d[$k]), fn ($v) => $v !== '');
                $out[$prop] = ['multi_select' => array_map(fn ($v) => ['name' => $v], array_values($vals))];
            }
        }
        return $out;
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

    /**
     * Construye "properties" solo con los campos provistos (array_key_exists).
     * Un valor vacío en un select lo limpia (select => null). Permite updates parciales.
     */
    protected function propsTarea(array $d): array
    {
        $out = [];
        if (array_key_exists('titulo', $d)) {
            $out['Tarea'] = ['title' => [['text' => ['content' => (string) $d['titulo']]]]];
        }
        $selects = ['estado' => 'Estado', 'prioridad' => 'Prioridad', 'cliente' => 'Cliente', 'area' => 'Área', 'responsable' => 'Responsable'];
        foreach ($selects as $k => $prop) {
            if (array_key_exists($k, $d)) {
                $out[$prop] = ($d[$k] !== null && $d[$k] !== '') ? ['select' => ['name' => $d[$k]]] : ['select' => null];
            }
        }
        if (array_key_exists('fecha_limite', $d)) {
            $out['Fecha límite'] = $d['fecha_limite'] ? ['date' => ['start' => $d['fecha_limite']]] : ['date' => null];
        }
        if (array_key_exists('notas', $d)) {
            $out['Notas'] = ['rich_text' => [['text' => ['content' => (string) $d['notas']]]]];
        }
        return $out;
    }

    /** Lee el contenido (bloques) de una página y lo simplifica para renderizar (accesos, notas). */
    public function bloquesSimplificados(string $pageId): array
    {
        $blocks = $this->http()->get("/blocks/{$pageId}/children", ['page_size' => 100])->throw()->json()['results'] ?? [];
        $out = [];
        foreach ($blocks as $b) {
            $type = $b['type'] ?? '';
            if (in_array($type, ['heading_1', 'heading_2', 'heading_3'])) {
                $out[] = ['kind' => 'heading', 'text' => $this->rt($b[$type]['rich_text'] ?? [])];
            } elseif ($type === 'paragraph') {
                $t = $this->rt($b['paragraph']['rich_text'] ?? []);
                if ($t !== '') {
                    $out[] = ['kind' => 'p', 'text' => $t];
                }
            } elseif (in_array($type, ['bulleted_list_item', 'numbered_list_item'])) {
                $out[] = ['kind' => 'li', 'text' => $this->rt($b[$type]['rich_text'] ?? [])];
            } elseif ($type === 'quote') {
                $out[] = ['kind' => 'quote', 'text' => $this->rt($b['quote']['rich_text'] ?? [])];
            } elseif ($type === 'divider') {
                $out[] = ['kind' => 'divider'];
            } elseif ($type === 'table') {
                $rows = [];
                if (($b['has_children'] ?? false)) {
                    $rb = $this->http()->get("/blocks/{$b['id']}/children", ['page_size' => 100])->throw()->json()['results'] ?? [];
                    foreach ($rb as $row) {
                        if (($row['type'] ?? '') === 'table_row') {
                            $rows[] = array_map(fn ($c) => $this->rt($c), $row['table_row']['cells'] ?? []);
                        }
                    }
                }
                $out[] = ['kind' => 'table', 'rows' => $rows];
            }
        }
        return $out;
    }

    protected function rt($richTextArray): string
    {
        return collect($richTextArray ?? [])->pluck('plain_text')->join('');
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
