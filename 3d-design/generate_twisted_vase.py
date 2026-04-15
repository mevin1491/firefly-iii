#!/usr/bin/env python3
"""
Generates a 3D-printable twisted vase STL file.

The vase features:
- A smooth twisted hexagonal cross-section that rotates as it rises
- A solid base for stable printing
- Parametric design — adjust constants below to customize

Output: twisted_vase.stl (binary STL format)
"""

import math
import struct
import os

# ── Design Parameters ────────────────────────────────────────────────
VASE_HEIGHT = 100.0       # mm — total height of the vase
BASE_RADIUS = 30.0        # mm — radius at the bottom
TOP_RADIUS = 35.0         # mm — radius at the top (slight flare)
WALL_THICKNESS = 2.5      # mm — wall thickness
NUM_SIDES = 6             # cross-section polygon sides (hexagonal)
TWIST_ANGLE = 90.0        # degrees — total twist from base to top
HEIGHT_STEPS = 120        # vertical resolution
RADIAL_STEPS = 64         # angular resolution per layer
BASE_THICKNESS = 2.0      # mm — solid base thickness
SMOOTHING = 0.35          # 0 = pure polygon, 1 = pure circle

# ── Helper Functions ─────────────────────────────────────────────────

def polygon_radius(angle, num_sides, smoothing=0.35):
    """Return a radius modifier for a smoothed polygon cross-section."""
    sector = 2 * math.pi / num_sides
    # angle within current sector
    a = angle % sector
    # distance from center to edge of regular polygon at this angle
    poly_r = math.cos(sector / 2) / math.cos(a - sector / 2)
    # blend between polygon and circle for smoother prints
    return smoothing * 1.0 + (1.0 - smoothing) * poly_r


def point_on_shell(angle, height_frac, radius, twist_rad, outer=True):
    """Compute a 3D point on the inner or outer vase wall."""
    twist = twist_rad * height_frac
    r_mod = polygon_radius(angle + twist, NUM_SIDES, SMOOTHING)
    r = radius * r_mod
    if not outer:
        r -= WALL_THICKNESS
        if r < 0:
            r = 0
    height = height_frac * VASE_HEIGHT
    x = r * math.cos(angle)
    y = r * math.sin(angle)
    return (x, y, height)


def compute_normal(v0, v1, v2):
    """Compute the unit normal of a triangle (v0, v1, v2)."""
    u = (v1[0] - v0[0], v1[1] - v0[1], v1[2] - v0[2])
    w = (v2[0] - v0[0], v2[1] - v0[1], v2[2] - v0[2])
    nx = u[1] * w[2] - u[2] * w[1]
    ny = u[2] * w[0] - u[0] * w[2]
    nz = u[0] * w[1] - u[1] * w[0]
    length = math.sqrt(nx * nx + ny * ny + nz * nz)
    if length == 0:
        return (0.0, 0.0, 0.0)
    return (nx / length, ny / length, nz / length)


def write_stl(filename, triangles):
    """Write triangles to a binary STL file."""
    with open(filename, 'wb') as f:
        # 80-byte header
        header = b'Binary STL - Twisted Vase Design' + b'\0' * 48
        f.write(header[:80])
        # number of triangles
        f.write(struct.pack('<I', len(triangles)))
        for (n, v0, v1, v2) in triangles:
            f.write(struct.pack('<3f', *n))
            f.write(struct.pack('<3f', *v0))
            f.write(struct.pack('<3f', *v1))
            f.write(struct.pack('<3f', *v2))
            f.write(struct.pack('<H', 0))  # attribute byte count
    print(f"  Wrote {len(triangles)} triangles to {filename}")
    size_kb = os.path.getsize(filename) / 1024
    print(f"  File size: {size_kb:.1f} KB")


# ── Generate Geometry ────────────────────────────────────────────────

def generate_vase():
    triangles = []
    twist_rad = math.radians(TWIST_ANGLE)
    d_angle = 2 * math.pi / RADIAL_STEPS

    # Pre-compute radii at each height level (interpolate base to top)
    def radius_at(h_frac):
        return BASE_RADIUS + (TOP_RADIUS - BASE_RADIUS) * h_frac

    # ── Outer wall ───────────────────────────────────────────────────
    print("Generating outer wall...")
    for i in range(HEIGHT_STEPS):
        h0 = i / HEIGHT_STEPS
        h1 = (i + 1) / HEIGHT_STEPS
        r0 = radius_at(h0)
        r1 = radius_at(h1)
        for j in range(RADIAL_STEPS):
            a0 = j * d_angle
            a1 = (j + 1) * d_angle
            p00 = point_on_shell(a0, h0, r0, twist_rad, outer=True)
            p10 = point_on_shell(a1, h0, r0, twist_rad, outer=True)
            p01 = point_on_shell(a0, h1, r1, twist_rad, outer=True)
            p11 = point_on_shell(a1, h1, r1, twist_rad, outer=True)
            # Two triangles per quad (outward-facing normals)
            n = compute_normal(p00, p10, p01)
            triangles.append((n, p00, p10, p01))
            n = compute_normal(p10, p11, p01)
            triangles.append((n, p10, p11, p01))

    # ── Inner wall ───────────────────────────────────────────────────
    # Starts above the solid base
    print("Generating inner wall...")
    base_frac = BASE_THICKNESS / VASE_HEIGHT
    inner_start = max(1, int(base_frac * HEIGHT_STEPS))
    for i in range(inner_start, HEIGHT_STEPS):
        h0 = i / HEIGHT_STEPS
        h1 = (i + 1) / HEIGHT_STEPS
        r0 = radius_at(h0)
        r1 = radius_at(h1)
        for j in range(RADIAL_STEPS):
            a0 = j * d_angle
            a1 = (j + 1) * d_angle
            p00 = point_on_shell(a0, h0, r0, twist_rad, outer=False)
            p10 = point_on_shell(a1, h0, r0, twist_rad, outer=False)
            p01 = point_on_shell(a0, h1, r1, twist_rad, outer=False)
            p11 = point_on_shell(a1, h1, r1, twist_rad, outer=False)
            # Inward-facing normals (reverse winding)
            n = compute_normal(p00, p01, p10)
            triangles.append((n, p00, p01, p10))
            n = compute_normal(p10, p01, p11)
            triangles.append((n, p10, p01, p11))

    # ── Top rim (connects outer and inner walls at the top) ──────────
    print("Generating top rim...")
    h_top = 1.0
    r_top = radius_at(1.0)
    for j in range(RADIAL_STEPS):
        a0 = j * d_angle
        a1 = (j + 1) * d_angle
        outer0 = point_on_shell(a0, h_top, r_top, twist_rad, outer=True)
        outer1 = point_on_shell(a1, h_top, r_top, twist_rad, outer=True)
        inner0 = point_on_shell(a0, h_top, r_top, twist_rad, outer=False)
        inner1 = point_on_shell(a1, h_top, r_top, twist_rad, outer=False)
        n = compute_normal(outer0, outer1, inner0)
        triangles.append((n, outer0, outer1, inner0))
        n = compute_normal(outer1, inner1, inner0)
        triangles.append((n, outer1, inner1, inner0))

    # ── Solid base (bottom disc) ─────────────────────────────────────
    print("Generating base...")
    center = (0.0, 0.0, 0.0)
    r_base = radius_at(0.0)
    for j in range(RADIAL_STEPS):
        a0 = j * d_angle
        a1 = (j + 1) * d_angle
        p0 = point_on_shell(a0, 0.0, r_base, twist_rad, outer=True)
        p1 = point_on_shell(a1, 0.0, r_base, twist_rad, outer=True)
        # downward-facing normal
        n = (0.0, 0.0, -1.0)
        triangles.append((n, center, p1, p0))

    # ── Inner base (floor inside the vase at BASE_THICKNESS height) ──
    print("Generating inner floor...")
    h_floor = base_frac
    r_floor = radius_at(h_floor)
    center_floor = (0.0, 0.0, BASE_THICKNESS)
    for j in range(RADIAL_STEPS):
        a0 = j * d_angle
        a1 = (j + 1) * d_angle
        p0 = point_on_shell(a0, h_floor, r_floor, twist_rad, outer=False)
        p1 = point_on_shell(a1, h_floor, r_floor, twist_rad, outer=False)
        # upward-facing normal
        n = (0.0, 0.0, 1.0)
        triangles.append((n, center_floor, p0, p1))

    return triangles


# ── Main ─────────────────────────────────────────────────────────────
if __name__ == '__main__':
    print("=" * 55)
    print("  Twisted Vase STL Generator")
    print("=" * 55)
    print(f"  Height:     {VASE_HEIGHT} mm")
    print(f"  Base radius:{BASE_RADIUS} mm")
    print(f"  Top radius: {TOP_RADIUS} mm")
    print(f"  Wall:       {WALL_THICKNESS} mm")
    print(f"  Twist:      {TWIST_ANGLE} degrees")
    print(f"  Sides:      {NUM_SIDES} (smoothed)")
    print("=" * 55)

    tris = generate_vase()

    output_dir = os.path.dirname(os.path.abspath(__file__))
    output_file = os.path.join(output_dir, "twisted_vase.stl")
    write_stl(output_file, tris)

    print("=" * 55)
    print("  Done! Open twisted_vase.stl in your slicer software")
    print("  (Cura, PrusaSlicer, BambuStudio, etc.)")
    print("=" * 55)
