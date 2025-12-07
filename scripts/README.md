Copilot - Pending review

Utilities for working with test snapshots and screenshots.

extract_snap_pngs.py
- Scans `tests/.pest/snapshots` for `.snap` files and extracts embedded
  PNG images to `tests/Browser/Snapshots/baseline/` so they can be opened
  in an image viewer for manual review.

Usage:
  ./scripts/extract_snap_pngs.py
