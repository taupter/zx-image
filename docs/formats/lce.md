# Format: lce

Two standard ZX Spectrum screens combined into one static 512×384 interlaced image.

## Identifiers

- Type key: `lce`
- Plugin class: `ZxImage\Plugin\Lce`

## Description

LCE stores the same data as [gigascreen](gigascreen.md), but the screens are not alternated in time.
Both screens are shown at once on an interlaced display: the picture does not flicker, and the two
screens supply the odd and the even scanlines of a 384-line frame. Horizontal size is doubled so the
resulting pixel stays square.

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Screen 1 pixel data |
| 6144 | 768 | Screen 1 attribute data |
| 6912 | 6144 | Screen 2 pixel data |
| 13056 | 768 | Screen 2 attribute data |

Total: **13824 bytes** (`strictFileSize = 13824`), identical to `gigascreen`.

## Rendering

Canvas is **512×384**, border is **64×48** (doubled standard border).

- Output line `2 * y` is line `y` of screen 1, output line `2 * y + 1` is line `y` of screen 2.
- Every screen pixel is drawn into two neighbouring output columns (`2 * x` and `2 * x + 1`).
- Colors are resolved per screen from its own attributes; screens are never blended, so `gigaColors`
  is not used.

`gigascreenMode` is ignored — the format has a single fixed representation.

## Flash

If either screen has flash cells, the output is an animated GIF of two frames (normal and swapped,
32 cs each), like `gigascreen` in `mix` mode.

## Output

- No flash → PNG (`image/png`)
- Flash → animated GIF (`image/gif`)
