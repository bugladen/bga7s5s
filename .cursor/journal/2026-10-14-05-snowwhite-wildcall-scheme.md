# _05SnowWhite (Wildcall) — Scheme implementation

## Printed text

1. Add Renown to Forum + Bazaar.
2. **Sorcerer Reaction:** At end of Planning • Muster Orel or Volk at Home from out of play.
3. Your Animals do not count toward Crew Cap (Until the end of the day.).

Test CAD — no `clienttranslate` / `_()` / `$game->translate` (user request; same as William/Orel/Volk).

## Classification

| Clause | Pattern |
|---|---|
| Fixed dual Renown Forum+Bazaar | Pattern A trivial (`_04035` destinations) |
| Sorcerer Reaction Planning End muster | Reaction on `EventPhasePlanningEnd` + Home + Sorcerer gate (`_04025` Look/Pass shape) — **buttons** not follow-on state |
| Animals ≠ Crew Cap until EOD | Passive on scheme at Home; Theah crew-count helper |

## WHY decisions

- **Name Wildcall / Init 56 / Panache 0:** verified against art (scaffold Name was WildCall; traits Dar Matushki + Command — Command added to TraitNames).
- **CardNumber=13:** CAD sequence after William/Orel/Volk (10–12). Footer CAD-US8 is set designation, not this id. Keys stay `_05SnowWhite` / class routing.
- **No resolve/Planning-End GameState:** Renown is fixed; muster pick is Orel/Volk/Pass buttons on the reaction itself (≤2 options). No JS triple.
- **Sorcerer gate without printed performer:** Merchant `_04025` discipline — ≥1 controlled Sorcerer in play; first Sorcerer is `performerId` for `ISorcererAbility` start/played events.
- **Muster from out of play:** Orel/Volk cannot enter Approach/faction decks, so first muster `createCardInLocation('05Orel'|'05Volk', PURGATORY)` then `createCharacterMusteredEvent` at Home (Damned create shape). Remuster: find owned not-in-play in locker/discard/elsewhere → locker/discard remove events then muster. Skip offer when that animal is already in play (Name Gate would just force a destroy).
- **Animals crew-cap exemption:** `Theah::characterCountsAgainstCrewCap` + `playerAnimalsExemptFromCrewCap` via `_05SnowWhite::isAnimalsExemptFromCrewCap()` (`Location == PLAYER_HOME`). EOD = scheme→locker at Dusk — no separate dusk clear. Wired into `getCharacterCountByPlayerId` and `Reaction_CrewCapLimit` button filter + remaining math (do not subtract a sunk Animal that never counted).
- **Plain strings:** CAD prototype; translates later.

## Files

- `_05SnowWhite.php` — resolve + Reactions wire + exempt helper
- `reactions/Reaction_05SnowWhite.php` — Sorcerer Planning-End muster
- `Theah.php` — crew-cap count helpers
- `Reaction_CrewCapLimit.php` — respect Animal exempt
- `TraitNames.php` — Command

## Bugfix (same day)

Fatal: `Cannot access private property Theah::$cards` in `findOutOfPlayAnimal` line 116.
WHY: Catch-all out-of-play scan used `$theah->cards` directly. Outside Theah that property is private.
Fix: `$theah->getAllCards()`. Locker still via `getCardObjectsAtLocation` (locker not loaded into world cards).

## Skill fold-in (same day, user-requested)

Updated create-scheme with Wildcall learnings:
- SKILL shape table: Sorcerer no-performer / Planning-End muster from out of play / Animals Crew Cap rows; Planning-End Reaction row notes button-only; mega-paragraph `_05SnowWhite`
- reactions.md: "Sorcerer Reaction at Planning End — muster named characters from out of play" (+ `getAllCards` private-cards trap)
- helpers: CAD footer vs CardNumber; private `$cards`; Animals Crew Cap; helper list (`getAllCards` / `createCardInLocation` / crew-cap)
- checklist 57–60; references `_05SnowWhite` / `Reaction_05SnowWhite` / CrewCapLimit / `_02031`; walkthrough; wiring note (no JS for button-only)

WHY document `getAllCards` + locker `getCardObjectsAtLocation`: future agents will `$theah->cards` again and hit the same Studio fatal; locker is not in world cards so a getAllCards-only scan misses remusters.

## Unfinished / watch

- Playtest: Planning End with Sorcerer + neither animal in play → Muster Orel/Volk; remuster from locker after destroy; no offer when both in play / no Sorcerer; mustered Animals do not trip Crew Cap under Wildcall; exemption ends when scheme lockers at Dusk.
- No CAD regression suite.
- Art icons vs Text: Text says Forum+Bazaar (authoritative per user); image read described Anchor+Temple — if Eddie corrects destinations, swap Renown events only.
