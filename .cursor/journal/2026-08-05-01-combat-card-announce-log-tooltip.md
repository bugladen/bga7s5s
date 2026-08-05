# EventCombatCardAnnounced log tooltip not hydrated

## Symptom
When Reaction_02039 (Raise the Stakes — Add Threat on opponent combat card announce) triggers, the player only sees `{player} announces {card} as their Combat Card.` The hover link isn't hydrated, so they can't tell what combat card was played before deciding Add Threat / Pass.

## Cause
Same class as Risk-play (2026-05-21-03) and Gamble reveal (2026-08-03-06):
- Inject code alone makes the bold log span + image path attribute.
- Text/image log tooltips need full card objects in `logCardCache`.
- `format_string_recursive_with_injection` seeds that cache from notify args that are objects with `id`+`type`.
- `EventCombatCardAnnounced` handler only passed `card_inject_code` — no property array.
- Combat cards are in the announcer's private hand, so opponents never had them in `cardProperties` → cache miss.

## Fix
EventHub `EventCombatCardAnnounced`: pass `"card" => $card->getPropertyArray($theah->game)` on the announce notify.

WHY here (not in Reaction_02039): the broken message is the generic announce notify that fires for every combat card announcement. Reaction_02039 just happens to make the missing tooltip painful because the reacting player must decide based on what was announced.

## Related / unfinished
- `EventDuelPlayerGambled` still only sends `card_inject_code` (noted in 2026-08-03-06) — same gap for gamble-chosen combat card.
- `FrameworkActionsTrait` "is playing … as their Combat Card" message (~1601) also lacks `"card"` — same pattern if reported.
