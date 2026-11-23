#!/usr/bin/env python3
"""
The thesis's benchmark figures and tables, from the raw CSVs in storage/benchmarks/.

    python3 thesis_figures.py            # writes the two PNGs into documentation/thesis/assets/
    python3 thesis_figures.py --tables   # also prints the tables of §3.5 and Appendix C.3

Figures:
  benchmark-escenarios.png     mean time per scenario, sizes 5–500 (binary search + Phase 2)
  benchmark-antes-despues.png  random scenario: original GLPK Phase 1 (storage/benchmarks/original,
                               from benchmark:glpk --original) vs binary search, median with a band up to p99

Timeouts of the original solver count as the timeout itself (30 s), as in the thesis.
"""

import argparse
import csv
import sys
from pathlib import Path

import matplotlib
import numpy as np

matplotlib.use('Agg')
import matplotlib.pyplot as plt  # noqa: E402

ROOT = Path(__file__).resolve().parents[2]
DATA = ROOT / 'storage' / 'benchmarks'
OLD = DATA / 'original'  # the original method: benchmark:glpk --original
ASSETS = ROOT / 'documentation' / 'thesis' / 'assets'

SIZES = [5, 10, 20, 30, 50, 75, 100, 150, 200, 300, 500]
SCENARIOS = {  # file name -> legend label, colour
    'identical': ('idénticas (peor caso)', '#E8A20B'),
    'opposite': ('opuestas', '#3F2E9E'),
    'realistic': ('realistas', '#13A87B'),
    'random': ('aleatorias', '#2574D1'),
}
TIMEOUT_S = 30.0

csv.field_size_limit(10**9)


TIMEOUTS: dict[Path, int] = {}


def times(path: Path) -> np.ndarray:
    """Seconds per run; a timeout counts as the timeout."""
    with path.open() as f:
        rows = list(csv.DictReader(f))
    TIMEOUTS[path] = sum(r['status'] == 'TIMEOUT' for r in rows)
    return np.array([
        TIMEOUT_S if r['status'] == 'TIMEOUT' else float(r['time_ms']) / 1000
        for r in rows
    ])


def series(folder: Path, scenario: str) -> dict[int, np.ndarray]:
    out = {}
    for n in SIZES + [25]:
        path = folder / f'glpk_{scenario}_{n}.csv'
        if path.exists():
            out[n] = times(path)
    return out


def fmt(seconds: float) -> str:
    """0,7 s / 28 s / 519 ms, the way the thesis writes them."""
    if seconds < 1:
        return f'{seconds * 1000:.0f} ms'
    if seconds < 10:
        return f'{seconds:.1f} s'.replace('.', ',')
    return f'{seconds:.0f} s'


def fmt1(seconds: float) -> str:
    return (f'{seconds * 1000:.0f} ms' if seconds < 1 else f'{seconds:.1f} s').replace('.', ',')


def style(ax):
    ax.set_xscale('log')
    ax.set_yscale('log')
    ax.set_xticks(SIZES)
    ax.set_xticklabels([str(n) for n in SIZES if n not in (75, 150, 300)] and
                       [str(n) if n not in (75, 150, 300) else '' for n in SIZES])
    ax.xaxis.set_minor_locator(matplotlib.ticker.NullLocator())
    ax.grid(True, which='major', alpha=0.3)
    ax.set_xlabel('Tamaño del problema (familias = unidades)', fontsize=14)
    ax.axvspan(20, 30, color='#DDDDCC', alpha=0.35, lw=0)
    for side in ('top', 'right'):
        ax.spines[side].set_visible(False)
    ax.tick_params(labelsize=13)


def figure_scenarios(data: dict[str, dict[int, np.ndarray]]):
    fig, ax = plt.subplots(figsize=(13, 7.5), dpi=144)
    for key, (label, colour) in SCENARIOS.items():
        if key not in data:
            continue
        ns = [n for n in SIZES if n in data[key]]
        ax.plot(ns, [data[key][n].mean() for n in ns], '-o', color=colour, lw=2.2, ms=7, label=label)
    style(ax)
    ax.set_yticks([0.3, 1, 10, 60])
    ax.set_yticklabels(['0,3 s', '1 s', '10 s', '60 s'])
    ax.yaxis.set_minor_formatter(matplotlib.ticker.NullFormatter())
    ax.set_ylabel('Tiempo medio de ejecución (escala log)', fontsize=14)
    ax.text(25, 0.36, 'tamaño típico', ha='center', color='#777', fontsize=12)
    ax.legend(loc='upper left', fontsize=13, frameon=False)
    runs = ' · '.join(f'{len(data[k][SIZES[0]])}' for k in data if SIZES[0] in data[k])
    fig.suptitle('Búsqueda binaria: todos los escenarios, hasta tamaño 500', x=0.06, ha='left',
                 fontsize=19, fontweight='bold')
    ax.set_title('Tiempo medio por escenario de preferencias · 100–1 000 corridas por punto · '
                 '0 timeouts, 100 % de éxito en todos', loc='left', color='#666', fontsize=12, pad=14)
    fig.tight_layout(rect=(0, 0, 1, 0.94))
    fig.savefig(ASSETS / 'benchmark-escenarios.png')
    plt.close(fig)


def figure_before_after(new: dict[int, np.ndarray], old: dict[int, np.ndarray]):
    fig, ax = plt.subplots(figsize=(13, 7.5), dpi=144)
    blue, amber = '#2574D1', '#E8A20B'

    ns = sorted(old)
    med = [np.median(old[n]) for n in ns]
    p99 = [np.percentile(old[n], 99) for n in ns]
    ax.fill_between(ns, med, p99, color=amber, alpha=0.25, lw=0)
    ax.plot(ns, med, '-o', color=amber, lw=2.4, ms=8)
    ax.plot(ns, p99, '--', color=amber, lw=1.6)

    ns = [n for n in SIZES if n in new]
    med = [np.median(new[n]) for n in ns]
    p99 = [np.percentile(new[n], 99) for n in ns]
    ax.fill_between(ns, med, p99, color=blue, alpha=0.2, lw=0)
    ax.plot(ns, med, '-o', color=blue, lw=2.4, ms=8)
    ax.plot(ns, p99, '--', color=blue, lw=1.6)

    # The runs the original method could not finish within the limit: one cross per size that had any
    capped = {n: int((old[n] >= TIMEOUT_S).sum()) for n in sorted(old) if (old[n] >= TIMEOUT_S).any()}
    if capped:
        ax.plot(list(capped), [TIMEOUT_S] * len(capped), 'x', color=amber, ms=12, mew=2.5)
        runs = f'{len(old[max(capped)]):,}'.replace(',', ' ')
        ax.text(max(capped) + 2, TIMEOUT_S,
                'al tope de 30 s: ' + ', '.join(str(c) for c in capped.values()) + f' de {runs} corridas (N = '
                + ', '.join(str(n) for n in capped) + ')', color='#A67200', fontsize=13, va='center')

    style(ax)
    ax.set_yticks([0.1, 1, 10, 60, 120])
    ax.set_yticklabels(['0,1 s', '1 s', '10 s', '60 s', '120 s'])
    ax.yaxis.set_minor_formatter(matplotlib.ticker.NullFormatter())
    ax.set_ylabel('Tiempo de ejecución (escala log)', fontsize=14)
    ax.text(25, 0.16, 'tamaño típico\nde un proyecto', ha='center', color='#777', fontsize=12)
    ax.text(11, 2.2, 'GLPK original (Fases 1 + 2)', color='#A67200', fontsize=16, fontweight='bold')
    ax.text(11, 1.35, 'p99 se dispara al crecer;\nsin datos por encima de 30', color='#A67200', fontsize=13, va='top')
    ax.text(140, 0.32, 'Búsqueda binaria + Fase 2', color=blue, fontsize=16, fontweight='bold')
    ax.text(140, 0.2, 'banda p50–p99 angosta y estable\nhasta 500 · 0 timeouts', color=blue, fontsize=13, va='top')
    fig.suptitle('El cuello de botella de la Fase 1, antes y después', x=0.06, ha='left',
                 fontsize=19, fontweight='bold')
    ax.set_title('Preferencias aleatorias · mediana y banda hasta p99 · 10 000 corridas por tamaño (original), '
                 '100–1 000 (búsqueda binaria)', loc='left', color='#666', fontsize=12, pad=14)
    fig.tight_layout(rect=(0, 0, 1, 0.94))
    fig.savefig(ASSETS / 'benchmark-antes-despues.png')
    plt.close(fig)


def tables(data: dict[str, dict[int, np.ndarray]]):
    names = {'random': 'Aleatorias', 'realistic': 'Realistas', 'identical': 'Idénticas', 'opposite': 'Opuestas'}
    print('\n§3.5 — tiempos medios\n')
    print('| Escenario | $N=75$ | $N=150$ | $N=300$ | $N=500$ |\n|---|---|---|---|---|')
    for key in ('random', 'realistic', 'identical', 'opposite'):
        if key in data:
            print(f'| {names[key]} | ' + ' | '.join(fmt(data[key][n].mean()) if n in data[key] else '—' for n in (75, 150, 300, 500)) + ' |')
    print('\nC.3 — percentiles (p50 / p95 / p99)\n')
    print('| Escenario | $N$ | p50 | p95 | p99 |\n|---|---|---|---|---|')
    for key in ('random', 'realistic', 'identical', 'opposite'):
        if key not in data:
            continue
        for i, n in enumerate((30, 100, 300, 500)):
            if n not in data[key]:
                continue
            p = np.percentile(data[key][n], [50, 95, 99])
            print(f'| {names[key] if i == 0 else ""} | {n} | ' + ' | '.join(fmt1(x) for x in p) + ' |')
    print('\nCorridas, máximos y timeouts\n')
    for key in data:
        for n in SIZES:
            if n in data[key]:
                t = data[key][n]
                print(f'{key:10} N={n:4}  runs={len(t):5}  mean={t.mean():8.3f}  p99={np.percentile(t, 99):8.3f}  max={t.max():8.3f}  timeouts={TIMEOUTS[DATA / f"glpk_{key}_{n}.csv"]}')


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--tables', action='store_true')
    parser.add_argument('--no-figures', action='store_true')
    args = parser.parse_args()

    data = {key: series(DATA, key) for key in SCENARIOS}
    data = {k: v for k, v in data.items() if v}
    old = series(OLD, 'random')

    if not args.no_figures:
        figure_scenarios(data)
        if 'random' in data and old:
            figure_before_after(data['random'], old)
        print(f'✓ Figuras escritas en {ASSETS}')
    if args.tables:
        tables(data)
        if old:
            for n in sorted(old):
                if n in old:
                    print(f'original N={n}: runs={len(old[n])} median={np.median(old[n]):.3f} p99={np.percentile(old[n], 99):.3f} max={old[n].max():.1f} timeouts={TIMEOUTS[OLD / f"glpk_random_{n}.csv"]}')
    return 0


if __name__ == '__main__':
    sys.exit(main())
