#!/usr/bin/env python3
"""
Copilot - Pending review

Scan Pest .snap snapshot files and extract embedded PNG images
into `tests/Browser/Snapshots/baseline/...` preserving the directory
structure under `tests/.pest/snapshots/`.

Usage:
  ./scripts/extract_snap_pngs.py

This writes files like:
  tests/Browser/Snapshots/baseline/Browser/Healthcheck/SampleScreenshotTest/projects_index_visual_snapshot.expected.png

"""
import os
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SNAP_DIR = ROOT / 'tests' / '.pest' / 'snapshots'
OUT_ROOT = ROOT / 'tests' / 'Browser' / 'Snapshots' / 'baseline'

PNG_HEADER = b"\x89PNG\r\n\x1a\n"
IEND = b'IEND'


def extract_png_from_file(path: Path) -> bytes | None:
    data = path.read_bytes()
    start = data.find(PNG_HEADER)
    if start == -1:
        return None
    iend = data.find(IEND, start)
    if iend == -1:
        return None
    end = iend + 8  # IEND + 4-byte CRC
    return data[start:end]


def main() -> int:
    if not SNAP_DIR.exists():
        print(f"No snapshot dir found at {SNAP_DIR}")
        return 1

    found = 0
    for snap in SNAP_DIR.rglob('*.snap'):
        png = extract_png_from_file(snap)
        rel = snap.relative_to(SNAP_DIR)
        out_dir = OUT_ROOT / rel.parent
        out_dir.mkdir(parents=True, exist_ok=True)

        out_name = rel.stem + '.expected.png'
        out_path = out_dir / out_name

        if png is None:
            # skip files without PNG
            continue

        out_path.write_bytes(png)
        print(f'Wrote: {out_path}')
        found += 1

    if found == 0:
        print('No embedded PNG images found in .snap files.')
        return 2

    print(f'Extracted {found} PNG(s) to {OUT_ROOT}')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
