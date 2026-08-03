# EventDuelGambleCardsRevealed log tooltips not hydrated

## Symptom
Gamble reveal line in EventHub (`${player_name} reveals the following cards for their Gamble: ${names}.`) shows card names but hover text/image tooltips don't hydrate for viewers who never had those deck cards client-side.

## Cause
Same class of bug as `2026-05-21-03-risk-log-tooltip-fallback.md`:
- Inject codes alone make the bold log span + image path attribute.
- Text tooltips (pref 100=2) need full card objects in `logCardCache`.
- `format_string_recursive_with_injection` seeds that cache from notify args that are objects with `id`+`type`.
- Handler only passed implode'd inject codes in `names` — no property arrays.
- Even if we had passed `cards => [...]`, the JS skipped `Array.isArray` values, so multi-card payloads never cached.

Faction-deck gamble reveals are never in opponents' `cardProperties`, so cache miss → minimal/broken tooltip.

## Fix
1. EventHub: collect `$cards[] = $card->getPropertyArray(...)` and pass `"cards" => $cards` on the notify.
2. JS: walk arrays in `format_string_recursive_with_injection` and cache each item with id+type.

WHY not rename `names` to `*_inject_code`: variable card count; implode of inject codes into one placeholder is the established Otto/Gustavo pattern. Hydration needs the parallel property-array payload, not a key rename.

WHY fix JS array scan now: Otto/Gustavo/city-reveal notifies already implode names the same way; once they pass `cards` (or if other notifs already do for UI), log tooltips will work without per-notify JS hacks.

## Unfinished / related
`EventDuelPlayerGambled` still only sends `card_inject_code` without `"card" => getPropertyArray` — same opponent tooltip gap for the chosen combat card. Not touched this pass; fix if Eddie reports it.
