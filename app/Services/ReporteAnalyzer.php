<?php

namespace App\Services;

/**
 * Analiza la data de un reporte de Ads (Meta o Google) y genera:
 *   - Comparativa vs mes anterior con dirección semáforo
 *   - Resumen ejecutivo en lenguaje natural
 *   - Recomendaciones automáticas basadas en thresholds
 *
 * Es plataforma-agnóstico: recibe modelos genéricos (resumen, campañas, prev) y devuelve arrays.
 */
class ReporteAnalyzer
{
    /**
     * Calcula delta de un KPI vs mes anterior.
     * $inverted = true para métricas donde menos es mejor (CPA, CPC).
     */
    public static function delta($actual, $previo, bool $inverted = false): ?array
    {
        if ($previo === null || $previo == 0) return null;
        $diff = $actual - $previo;
        $pct = round($diff / $previo * 100);
        $direction = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
        $isGood = ($pct === 0)
            ? null
            : ($inverted ? $pct < 0 : $pct > 0);
        return [
            'pct' => $pct,
            'abs' => $pct < 0 ? -$pct : $pct,
            'direction' => $direction,
            'isGood' => $isGood,
            'color' => $isGood === null ? '#6B7280' : ($isGood ? '#059669' : '#DC2626'),
            'icon' => $direction === 'up' ? 'arrow-up' : ($direction === 'down' ? 'arrow-down' : 'minus'),
        ];
    }

    /**
     * Construye el array completo de comparativas para los KPIs principales.
     * $resumen y $previo son modelos MetaAdInsight o GoogleAdInsight (o null).
     */
    public static function comparativas($resumen, $previo): array
    {
        if (!$resumen) return [];
        $r = (array) $resumen->toArray();
        $p = $previo ? (array) $previo->toArray() : [];

        $rInv = $r['inversion'] ?? 0;
        $rVen = $r['ventas'] ?? 0;
        $rComp = $r['compras'] ?? 0;
        $rAlc = $r['alcance'] ?? 0;
        $rImp = $r['impresiones'] ?? 0;
        $rClk = $r['clicks'] ?? 0;
        $rRoas = $rInv > 0 ? $rVen / $rInv : 0;
        $rCpa = $rComp > 0 ? $rInv / $rComp : 0;
        $rCtr = $rImp > 0 ? $rClk / $rImp * 100 : 0;

        $pInv = $p['inversion'] ?? 0;
        $pVen = $p['ventas'] ?? 0;
        $pComp = $p['compras'] ?? 0;
        $pAlc = $p['alcance'] ?? 0;
        $pImp = $p['impresiones'] ?? 0;
        $pClk = $p['clicks'] ?? 0;
        $pRoas = $pInv > 0 ? $pVen / $pInv : 0;
        $pCpa = $pComp > 0 ? $pInv / $pComp : 0;
        $pCtr = $pImp > 0 ? $pClk / $pImp * 100 : 0;

        return [
            'inversion' => self::delta($rInv, $pInv ?: null),
            'ventas' => self::delta($rVen, $pVen ?: null),
            'compras' => self::delta($rComp, $pComp ?: null),
            'alcance' => self::delta($rAlc, $pAlc ?: null),
            'impresiones' => self::delta($rImp, $pImp ?: null),
            'clicks' => self::delta($rClk, $pClk ?: null),
            'roas' => self::delta(round($rRoas, 2), $pRoas ? round($pRoas, 2) : null),
            'cpa' => self::delta($rCpa ? round($rCpa) : null, $pCpa ? round($pCpa) : null, true),
            'ctr' => self::delta(round($rCtr, 2), $pCtr ? round($pCtr, 2) : null),
        ];
    }

    /**
     * Genera bullets en lenguaje natural para el resumen ejecutivo.
     * Devuelve array de strings (cada uno con un marcador emoji al inicio).
     */
    public static function resumenEjecutivo($resumen, $previo, $campanas): array
    {
        if (!$resumen) return [];
        $fmt = fn($n) => '$' . number_format((int) $n, 0, ',', '.');
        $bullets = [];

        $inv = $resumen->inversion ?? 0;
        $ven = $resumen->ventas ?? 0;
        $comp = $resumen->compras ?? 0;
        $roas = $inv > 0 ? round($ven / $inv, 2) : 0;

        $pInv = $previo->inversion ?? 0;
        $pVen = $previo->ventas ?? 0;
        $pComp = $previo->compras ?? 0;
        $pRoas = $pInv > 0 ? round($pVen / $pInv, 2) : 0;

        // === Bullet 1: Headline general (inversión + ventas + ROAS) ===
        if ($previo && $pInv > 0) {
            $deltaInv = round(($inv - $pInv) / $pInv * 100);
            $deltaVen = $pVen > 0 ? round(($ven - $pVen) / $pVen * 100) : null;
            $sign = fn($n) => $n > 0 ? '+' . $n . '%' : ($n < 0 ? $n . '%' : 'igual');
            $invFrase = $deltaInv > 0 ? "subió un {$sign($deltaInv)}" : ($deltaInv < 0 ? "bajó un " . abs($deltaInv) . "%" : "se mantuvo igual");
            if ($deltaVen !== null) {
                $venFrase = $deltaVen > 0 ? "las ventas crecieron un {$sign($deltaVen)}" : ($deltaVen < 0 ? "las ventas cayeron un " . abs($deltaVen) . "%" : "las ventas quedaron parejas");
                $bullets[] = ['icon' => 'chart-line', 'tone' => 'info', 'text' => "Tu inversión <strong>{$invFrase}</strong> respecto al mes anterior y {$venFrase} ({$fmt($ven)} vs {$fmt($pVen)})."];
            } else {
                $bullets[] = ['icon' => 'chart-line', 'tone' => 'info', 'text' => "Tu inversión <strong>{$invFrase}</strong> respecto al mes anterior, alcanzando {$fmt($inv)} en {$comp} compras."];
            }
        } else {
            $bullets[] = ['icon' => 'chart-line', 'tone' => 'info', 'text' => "Este mes invertiste <strong>{$fmt($inv)}</strong> y generaste <strong>{$fmt($ven)}</strong> en ventas con un ROAS de <strong>{$roas}x</strong>."];
        }

        // === Bullet 2: ROAS evolution ===
        if ($pRoas > 0) {
            $deltaRoas = round(($roas - $pRoas) / $pRoas * 100);
            if ($deltaRoas >= 10) {
                $bullets[] = ['icon' => 'arrow-trend-up', 'tone' => 'good', 'text' => "El <strong>retorno mejoró</strong>: pasaste de un ROAS de {$pRoas}x a <strong>{$roas}x</strong> (+{$deltaRoas}%). Cada peso invertido te está devolviendo más."];
            } elseif ($deltaRoas <= -10) {
                $bullets[] = ['icon' => 'arrow-trend-down', 'tone' => 'warn', 'text' => "El <strong>retorno cayó</strong>: pasaste de un ROAS de {$pRoas}x a <strong>{$roas}x</strong> (" . $deltaRoas . "%). Conviene revisar el rendimiento de las campañas más caras."];
            } else {
                $bullets[] = ['icon' => 'equals', 'tone' => 'info', 'text' => "El retorno se mantuvo estable: ROAS de <strong>{$roas}x</strong> (mes anterior: {$pRoas}x)."];
            }
        } elseif ($roas > 0) {
            $tone = $roas >= 3 ? 'good' : ($roas >= 1.5 ? 'info' : 'warn');
            $bullets[] = ['icon' => 'bullseye', 'tone' => $tone, 'text' => "Tu ROAS actual es de <strong>{$roas}x</strong> — " . ($roas >= 3 ? 'excelente, las campañas están bien afinadas.' : ($roas >= 1.5 ? 'rentable, con espacio para optimizar.' : 'por debajo del breakeven (1x). Hay que revisar urgentemente.'))];
        }

        // === Bullet 3: Mejor campaña ===
        if ($campanas && $campanas->count() > 0) {
            $top = $campanas->sortByDesc(function($c) {
                return $c->inversion > 0 ? $c->ventas / $c->inversion : 0;
            })->first();
            $topRoas = $top->inversion > 0 ? round($top->ventas / $top->inversion, 2) : 0;
            if ($topRoas >= 2) {
                $bullets[] = ['icon' => 'trophy', 'tone' => 'good', 'text' => "La campaña con mejor rendimiento fue <strong>\"{$top->objeto_nombre}\"</strong> con un ROAS de <strong>{$topRoas}x</strong> ({$fmt($top->ventas)} en ventas con {$fmt($top->inversion)} de inversión)."];
            }
        }

        // === Bullet 4: Campañas problemáticas ===
        if ($campanas && $campanas->count() > 0) {
            $perdedoras = $campanas->filter(function($c) {
                return $c->inversion > 50000 && ($c->inversion > 0 ? $c->ventas / $c->inversion : 0) < 1;
            });
            if ($perdedoras->count() > 0) {
                $primera = $perdedoras->first();
                $pRoas2 = $primera->inversion > 0 ? round($primera->ventas / $primera->inversion, 2) : 0;
                if ($perdedoras->count() === 1) {
                    $bullets[] = ['icon' => 'exclamation-triangle', 'tone' => 'warn', 'text' => "Atención: la campaña <strong>\"{$primera->objeto_nombre}\"</strong> está por debajo de breakeven ({$pRoas2}x ROAS) y consumió {$fmt($primera->inversion)}. Conviene revisarla o pausarla."];
                } else {
                    $bullets[] = ['icon' => 'exclamation-triangle', 'tone' => 'warn', 'text' => "Hay <strong>{$perdedoras->count()} campañas</strong> por debajo de breakeven que consumieron {$fmt($perdedoras->sum('inversion'))} sin retorno positivo. Revisar urgente."];
                }
            }
        }

        // === Bullet 5: Compras (si hay buen volumen) ===
        if ($previo && $pComp > 0 && $comp > 0) {
            $deltaComp = round(($comp - $pComp) / $pComp * 100);
            if ($deltaComp >= 15) {
                $bullets[] = ['icon' => 'shopping-bag', 'tone' => 'good', 'text' => "Lograste <strong>{$comp} compras</strong> este mes, un <strong>+{$deltaComp}%</strong> más que las {$pComp} del mes pasado."];
            } elseif ($deltaComp <= -15) {
                $bullets[] = ['icon' => 'shopping-bag', 'tone' => 'warn', 'text' => "Las compras cayeron de {$pComp} a <strong>{$comp}</strong> ({$deltaComp}%). Revisa la frecuencia y creativos del público frío."];
            }
        }

        return $bullets;
    }

    /**
     * Genera recomendaciones accionables basadas en la data.
     * Devuelve array con: tipo (action|review|scale), prioridad, texto.
     */
    public static function recomendaciones($resumen, $previo, $campanas, $demoRegion = null): array
    {
        if (!$resumen) return [];
        $fmt = fn($n) => '$' . number_format((int) $n, 0, ',', '.');
        $recs = [];

        // === Pausar campañas perdedoras ===
        if ($campanas && $campanas->count() > 0) {
            $perdedoras = $campanas->filter(function($c) {
                return $c->inversion > 80000 && ($c->inversion > 0 ? $c->ventas / $c->inversion : 0) < 1;
            });
            foreach ($perdedoras->take(2) as $p) {
                $pRoas = $p->inversion > 0 ? round($p->ventas / $p->inversion, 2) : 0;
                $recs[] = [
                    'prioridad' => 'alta',
                    'icon' => 'pause-circle',
                    'tipo' => 'Pausar',
                    'titulo' => "Pausar o reestructurar \"{$p->objeto_nombre}\"",
                    'detalle' => "ROAS de {$pRoas}x con {$fmt($p->inversion)} invertidos. Está perdiendo plata neto. Sugerencia: pausarla, revisar audiencia/creativo, y relanzar como nueva versión."
                ];
            }
        }

        // === Escalar campañas ganadoras ===
        if ($campanas && $campanas->count() > 0) {
            $ganadoras = $campanas->filter(function($c) {
                $roas = $c->inversion > 0 ? $c->ventas / $c->inversion : 0;
                return $roas >= 3.5 && $c->inversion >= 100000;
            });
            foreach ($ganadoras->take(2) as $g) {
                $gRoas = $g->inversion > 0 ? round($g->ventas / $g->inversion, 2) : 0;
                $sugerido = (int) round($g->inversion * 1.3);
                $recs[] = [
                    'prioridad' => 'media',
                    'icon' => 'arrow-up-right-dots',
                    'tipo' => 'Escalar',
                    'titulo' => "Aumentar presupuesto de \"{$g->objeto_nombre}\"",
                    'detalle' => "ROAS de {$gRoas}x — está rindiendo. Sugerencia: subir el presupuesto un 20-30% (de {$fmt($g->inversion)} a ~{$fmt($sugerido)}) y monitorear que el ROAS se mantenga sobre 3x."
                ];
            }
        }

        // === Frecuencia / fatiga (si tenemos impresiones >> alcance) ===
        $inv = $resumen->inversion ?? 0;
        $alc = $resumen->alcance ?? 0;
        $imp = $resumen->impresiones ?? 0;
        if ($alc > 0 && $imp > 0) {
            $frecuencia = round($imp / $alc, 1);
            if ($frecuencia >= 5) {
                $recs[] = [
                    'prioridad' => 'media',
                    'icon' => 'rotate',
                    'tipo' => 'Refrescar',
                    'titulo' => "Renovar creativos: frecuencia alta",
                    'detalle' => "Cada persona vio tus anuncios {$frecuencia} veces en promedio. Por encima de 4-5 empieza la fatiga creativa. Sugerencia: rotar al menos 2-3 creativos nuevos este mes."
                ];
            }
        }

        // === Concentración geográfica ===
        if ($demoRegion && $demoRegion->count() > 0) {
            $totalInv = $demoRegion->sum('inversion');
            if ($totalInv > 0) {
                $top1 = $demoRegion->sortByDesc('inversion')->first();
                $pct = round($top1->inversion / $totalInv * 100);
                if ($pct >= 60) {
                    $recs[] = [
                        'prioridad' => 'baja',
                        'icon' => 'map-location',
                        'tipo' => 'Explorar',
                        'titulo' => "Diversificar geográficamente",
                        'detalle' => "El {$pct}% de la inversión cayó en {$top1->objeto_nombre}. Probar una campaña separada con presupuesto pequeño (10-15% del total) hacia regiones con buen CTR pero baja inversión actual."
                    ];
                }
            }
        }

        // === ROAS bajo general ===
        $venTot = $resumen->ventas ?? 0;
        $roas = $inv > 0 ? round($venTot / $inv, 2) : 0;
        if ($roas > 0 && $roas < 1.5 && empty(array_filter($recs, fn($r) => $r['tipo'] === 'Pausar'))) {
            $recs[] = [
                'prioridad' => 'alta',
                'icon' => 'magnifying-glass-chart',
                'tipo' => 'Diagnosticar',
                'titulo' => "Revisar el embudo: ROAS general bajo",
                'detalle' => "ROAS de {$roas}x indica que estás cerca o por debajo del breakeven. Revisar: precio de productos, conversión del checkout, alineación creativo-audiencia, y si el seguimiento de Pixel/CAPI está bien configurado."
            ];
        }

        return $recs;
    }
}
