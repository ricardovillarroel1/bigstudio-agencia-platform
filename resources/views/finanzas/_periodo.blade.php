@php $rutaPeriodo = $ruta ?? 'finanzas.dashboard'; @endphp
<div style="display:inline-flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
    <span style="font-size:0.8rem; color:#64748b; font-weight:600;">Período</span>
    <div class="fz-dd">
        <button type="button" id="fzMesBtn" onclick="fzToggleMes(event)" class="fz-dd-trigger">
            <i class="far fa-calendar" style="color:#FF8100;"></i>
            <span style="flex:1; text-align:left;">{{ ucfirst(\Carbon\Carbon::create(null, $mes)->translatedFormat('F')) }}</span>
            <i class="fas fa-chevron-down" style="font-size:0.62rem; color:#94a3b8;"></i>
        </button>
        <div id="fzMesMenu" class="fz-dd-menu">
            @for($m=1; $m<=12; $m++)
                <a href="{{ route($rutaPeriodo, ['mes'=>$m, 'anio'=>$anio]) }}" class="fz-dd-item {{ $mes == $m ? 'active' : '' }}">
                    <span>{{ ucfirst(\Carbon\Carbon::create(null, $m)->translatedFormat('F')) }}</span>
                    @if($mes == $m)<i class="fas fa-check" style="font-size:0.65rem;"></i>@endif
                </a>
            @endfor
        </div>
    </div>
    <div style="display:inline-flex; background:#f1f5f9; border-radius:11px; padding:3px; gap:2px;">
        @for($a=now()->year; $a>=now()->year-3; $a--)
            <a href="{{ route($rutaPeriodo, ['mes'=>$mes, 'anio'=>$a]) }}" class="fz-year-pill {{ $anio == $a ? 'active' : '' }}">{{ $a }}</a>
        @endfor
    </div>
</div>
@once
<style>
    .fz-dd { position: relative; }
    .fz-dd-trigger { display: inline-flex; align-items: center; gap: 0.55rem; padding: 0.5rem 0.85rem; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 11px; font-size: 0.875rem; font-weight: 600; color: #0f172a; cursor: pointer; min-width: 160px; transition: border-color .15s ease, box-shadow .15s ease; }
    .fz-dd-trigger:hover { border-color: #FFCB8A; }
    .fz-dd-trigger:focus-visible { outline: none; border-color: #FF9C00; box-shadow: 0 0 0 3px rgba(255,156,0,0.14); }
    .fz-dd-menu { position: absolute; top: calc(100% + 6px); left: 0; z-index: 60; display: none; grid-template-columns: 1fr 1fr; gap: 2px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 14px 32px -10px rgba(15,23,42,0.22); padding: 6px; width: 248px; }
    .fz-dd-menu.open { display: grid; }
    .fz-dd-item { display: flex; align-items: center; justify-content: space-between; gap: 0.4rem; padding: 0.5rem 0.7rem; border-radius: 9px; font-size: 0.82rem; color: #475569; text-decoration: none; transition: background .12s ease, color .12s ease; }
    .fz-dd-item:hover { background: #f8fafc; color: #0f172a; }
    .fz-dd-item.active { background: #FFF7EC; color: #FF8100; font-weight: 700; }
    .fz-year-pill { padding: 0.42rem 0.85rem; border-radius: 9px; font-size: 0.8rem; font-weight: 600; color: #64748b; text-decoration: none; transition: all .12s ease; }
    .fz-year-pill:hover { color: #334155; }
    .fz-year-pill.active { background: #fff; color: #FF8100; box-shadow: 0 1px 3px rgba(15,23,42,0.12); }
</style>
<script>
    function fzToggleMes(e) { if (e) e.stopPropagation(); document.getElementById('fzMesMenu').classList.toggle('open'); }
    document.addEventListener('click', function (e) {
        var menu = document.getElementById('fzMesMenu'), btn = document.getElementById('fzMesBtn');
        if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove('open');
    });
</script>
@endonce
