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

- **Mirror Horatio, drop adjacent:** Horatio text is "moved to an *adjacent* location" + City Reaction. Volk omits adjacent and is plain Reaction. Same opposing leave-from-here gates; destination = any city **or** Home.
- **Still `cardInCity(owner)` despite not City Reaction:** "Opposing" needs same location; `LOCATION_PLAYER_HOME` is shared across players. Without the city gate, Volk at Home would treat every opponent leaving *their* Home as opposing. Horatio had the City label; we keep the gate for correctness.
- **fromLocation == owner.Location:** Follow when they leave Volk's site — not when they arrive (arrival destination == current → useless prompt).
- **Destination filter:** city or Home only — skip discard/locker/etc. so we never offer a dead Follow.
- **Stash `$FollowedCharacterLocation`:** Horatio shape — destination captured at trigger; performReaction may be a later request.
- **No states/JS:** Follow/Pass buttons only.
- **Novel trait Wolf** → TraitNames. Animal + Familiar already present (Orel).

## Files

- `_05Volk.php` — bans + Reactions wire
- `reactions/Reaction_05Volk.php` — follow Reaction
- `TraitNames.php` — Wolf

## Unfinished / watch

- Playtest: opposing leaves city site → Follow to non-adjacent city and to Home; no prompt when ally moves; no prompt at Home; Approach/equip/Risk-Scheme bans like Orel.

## Skill fold-in (same day, user-requested)

Updated create-character skill with Volk follow learnings:
- SKILL shape table: follow opposing → new location (any dest vs adjacent)
- pattern-d: "Follow opposing character to their new location" + example table rows for `Reaction_05Volk` / `Reaction_01066`
- checklist 113; CAD id note 103 extended for Volk
- references `_05Volk` / `Reaction_05Volk` / `Reaction_01066`
- helpers: Home opposing false-positive note on `getOpposingCharactersAtLocation`

WHY document `cardInCity` for non-City Reaction: future agents will drop it because the keyword is absent, then Home Volk spuriously follows every opponent leaving their Home.
