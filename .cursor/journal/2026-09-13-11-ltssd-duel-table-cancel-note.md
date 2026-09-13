# Let The Sword Decide — duel table cancel note (implemented)

## Decision

Centralize in `EventHub` on `EventTechniqueCanceled` / `EventManeuverCanceled` → `Theah::recordCanceledAbilityInDuelTable()`, NOT in each reaction.

WHY EventHub not Reaction_01146b alone:
- Same cancel path is shared by 01146b, 01047 (Breastplate), 03044 (Torres Cloak), and player Back on duelChooseTechnique_01063.
- Wiring each reaction would drift; EventHub already owns the Resolve INSERT that cancel skips.

## Behavior

On in-duel cancel:
1. INSERT into `duel_round_technique` / `duel_round_maneuver` with display name `Card: Ability (Canceled)`.
2. `technique_is_main = 0` — canceled must not count as main technique used.
3. Notify `duelAbilityCanceled` so live UI appends the same text and clears `_7sfs-ability-not-chosen` on the name cell only (stats stay silver — no R/P/T).

Page reload already loads names from those tables via UtilitiesTrait, so DB write covers refresh.

## Deliberate non-goals

- No schema change (suffix in `*_name` is enough).
- No stats rollback notify needed — Resolve/Calculate never ran for HIGH_PRIORITY cancel.
- Self-cancel via 01063 Back also shows the note — historical for the round if they then pick another technique. Acceptable; don't special-case unless Eddie complains.

## Files

- `modules/php/theah/Theah.php` — `recordCanceledAbilityInDuelTable`
- `modules/php/theah/EventHub.php` — canceled event cases
- `modules/js/Notifications.js` — subscribe + `notif_duelAbilityCanceled`
- `modules/js/Utilities.js` — reload path detects `(Canceled)` suffix
- `seventhseacityoffivesails.css` — `_7sfs-duel-ability-canceled` strikethrough

## Strikethrough

Eddie asked for strikethrough on the canceled name only — `(Canceled)` stays normal.
Live: `<span class="...">Card: Ability</span> (Canceled)`.
Reload: `formatDuelAbilityNameMarkup` splits on English `(Canceled)` DB suffix.
