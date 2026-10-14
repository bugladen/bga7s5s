# _05Volk — Character implementation

## Printed text

1. Cannot be included in Approach decks.
2. Cannot be equipped; cannot perform abilities on risks and schemes.
3. **Reaction:** After an opposing character moved • Move Volk to their new location.

Test CAD card — no `clienttranslate` / `_()` on card or reaction (user request; same as Orel/Cooper).

## Classification

| Clause | Pattern |
|---|---|
| Approach ban | `canEnterApproachDeck()` → false (Orel/Damned) |
| Cannot be equipped / Risk-Scheme | `canReceiveAttachments` / `canPerformRiskAndSchemeAbilities` → `abilitiesAreBlanked()` (Orel) |
| Follow Reaction | Pattern D button Reaction on `EventCardMoved` — Horatio `Reaction_01066` shape **minus** adjacent gate |

## WHY decisions

- **Not Horatio leave-from-here:** First pass copied Horatio's `fromLocation == owner.Location`. Wrong for Volk.
- **`cardInPlay`, not `cardInCity`:** Plain Reaction — Owner may be at Home or in the city.
- **"opposing" = enemy controller (playtest):** Volk follows any enemy Character play→play move. Second playtest: Volk at a city site, opponent Home → other city — must offer Follow even though they never shared a location. Horatio still needs leave-from-here because City Reaction + co-located opposing + adjacent.
- **Skip `toLocation == owner.Location`:** Already there — useless Follow prompt.
- **Origin + destination filter:** both must be city or Home — skip discard/locker/etc.
- **Stash `$FollowedCharacterLocation`:** Horatio shape — destination captured at trigger; performReaction may be a later request.
- **No states/JS:** Follow/Pass buttons only.
- **Novel trait Wolf** → TraitNames. Animal + Familiar already present (Orel).

## Files

- `_05Volk.php` — bans + Reactions wire
- `reactions/Reaction_05Volk.php` — follow Reaction
- `TraitNames.php` — Wolf

## Unfinished / watch

- Playtest: opposing leaves city site → Follow to non-adjacent city and to Home; no prompt when ally moves; Home → Forum Follow now expected (regression `Reaction_05Volk_Test`); Approach/equip/Risk-Scheme bans like Orel.

## Playtest corrections (same day)

1. **Home Owner + opponent Home → Forum:** `cardInCity` blocked it. Switched to `cardInPlay`.
2. **City Owner + opponent Home → other city:** leave-from-here still blocked it. Dropped `fromLocation == owner.Location` for Volk entirely. "Opposing" on this card = enemy controller per playtest — do not re-add the Horatio co-location gate.

Skill/checklist/helpers/references now split Volk vs Horatio explicitly. Regression: `tests/regression/Reaction_05Volk_Test.php`.

## Skill fold-in (same day, user-requested)

Updated create-character skill with Volk follow learnings — later corrected so skill does **not** teach leave-from-here for plain Volk text.
