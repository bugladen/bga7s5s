# City day-phase → PirataOne

User: `#city-day-phase` should use the pirata font.

Set `font-family: PirataOne-Regular, serif` on `#city-day-phase`. WHY: already used for duel ledger / icon number chips; phase labels are short single words (Dawn, Planning, High Drama, Plunder, Dusk) so readability concerns that killed Pirata on multi-word maneuver/technique labels don't apply here. `@font-face` + `fonts/PirataOne-Regular.ttf` already wired.

## Follow-up: dark ink + pad
User asked what color reads better on Stone grey → recommended warm near-black over white (contrast + Pirata stroke weight). Tried dark first: `color: #1F1A14`. L/R padding 5→9px. `#day-indicator` still white — only phase chip changed.

## Follow-up: cream + line-height
User: more headroom + try cream. `line-height` 25→28px; color → `#F5E6D3` (warm cream alt to white). Dark `#1F1A14` kept as prior try if they flip back.

## Follow-up: back to dark, lighter
Cream rejected. Back to dark ink but lighter than `#1F1A14` → `#3A3228` (warm mid-dark). Line-height 28 stays.
