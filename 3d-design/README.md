# Twisted Vase - 3D Printable Design

A parametric twisted vase with a smoothed hexagonal cross-section that rotates 90 degrees from base to top.

## Files

| File | Description |
|------|-------------|
| `twisted_vase.stl` | Ready-to-print STL file (binary format, ~1.5 MB) |
| `generate_twisted_vase.py` | Python generator script (customize and regenerate) |

## Design Specifications

| Parameter | Value |
|-----------|-------|
| Height | 100 mm |
| Base diameter | ~60 mm |
| Top diameter | ~70 mm |
| Wall thickness | 2.5 mm |
| Cross-section | Smoothed hexagon |
| Twist | 90 degrees |
| Triangle count | 30,720 |

## Recommended Print Settings

- **Layer height:** 0.2 mm (or 0.12 mm for finer detail)
- **Infill:** 0% (vase mode / spiral) or 10-15% with walls
- **Supports:** Not required
- **Material:** PLA, PETG, or any standard filament
- **Nozzle:** 0.4 mm
- **Estimated print time:** ~3-5 hours depending on settings

## Customization

Edit the constants at the top of `generate_twisted_vase.py` to customize:

```python
VASE_HEIGHT = 100.0       # mm - total height
BASE_RADIUS = 30.0        # mm - radius at bottom
TOP_RADIUS = 35.0         # mm - radius at top
WALL_THICKNESS = 2.5      # mm - wall thickness
NUM_SIDES = 6             # polygon sides (try 5, 8, etc.)
TWIST_ANGLE = 90.0        # degrees of twist
SMOOTHING = 0.35          # 0 = sharp polygon, 1 = circle
```

Then regenerate:

```bash
python3 generate_twisted_vase.py
```

No external dependencies required - uses only Python standard library.
