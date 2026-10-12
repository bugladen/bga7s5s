# Halloween map background tint

## Context
Continuation of CITY_HALLOWEEN seasonal art. User: background map uses blue; when flag=1 use dark grey.

## What / Why
Normal board: `html` has `Theah_Map.jpg` tile; `body` overlays `rgba(57, 135, 201, 0.9)` (blue).

Setup already adds `body._7sfs-halloween` when `window.CITY_HALLOWEEN === 1`. Added CSS:

```css
body._7sfs-halloween {
    background-color: rgba(45, 45, 45, 0.9) !important;
}
```

WHY keep 0.9 alpha: same map-through-tint behavior as blue. WHY !important: body rule already uses it. WHY body class not a new Setup path: class already present for icon swaps.

Grey is `#2d2d2d` at 0.9 — mid-dark, not pure black, so map texture still readable.
