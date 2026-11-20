# Friends at Sea (_05Scallywag) finish

## Context
Prototype CAD Scheme. User: finish via create-scheme skill; no clienttranslate / translate / _().

## Art vs scaffold / Text
- Name Friends at Sea, Init 21, Panache +1, Traits Daring/Camaraderie — match art.
- User said Text property is authoritative for abilities → Forum **or** Docks (Pattern A pick like `_04045`).
- Vision of art described Anchor **and** Temple (same icons as Wildcall's fixed dual). If Eddie says art is AND, switch to trivial dual Renown and delete the resolve GameState/JS. Kept OR for now.
- CardNumber 24 collides with Hacked `2605024` — resolve state id `2605240`.
- Added `Daring` to TraitNames (alpha after Dar Matushki).

## Text → patterns
1. **"Add a Renown to [The City Forum] or [The City Docks]"** → Pattern A Forum/Docks pick (`_04045`).
2. **Reaction: opponent Leader wounded from your performer's challenge or during their duel → Muster Pirate engaged at their location** → `CardReaction` on `EventCharacterWounded`.

## WHY decisions
- **Muster source = Approach** when text does not say hand / out of play / locker (Chance Meeting / game Muster default).
- **Engage after muster** — printed "engaged"; muster enters En Garde by default.
- **Challenge gate:** owner must be the **challenger** (`CHOSEN_PERFORMER` / duel challenger), not defender. Non-duel: wounded == `CHOSEN_TARGET`. Duel: wounded is a participant.
- Capture Leader location at wound time (lethal can move before click).
- Offer only with ≥1 Approach Pirate; buttons per Pirate + Pass; Pass without `setUsed`.
- State id `2605240` / transition `"05Scallywag"` — avoid Hacked collision.

## Shipped
- `_05Scallywag` + `Reaction_05Scallywag` + `State_planningPhaseResolveSchemes05Scallywag`
- States.php / states.inc.php / cad.js triple
- TraitNames `Daring`
- `Card_05Scallywag_Test` — 7 pass

## Unfinished / watch
- Confirm with Eddie if art is AND (fixed dual) vs Text OR (pick).
- No CAD Pirate characters yet — reaction needs Approach Pirate from other sets / future CAD.

## Skill update (same day)
Folded into create-scheme: shape table (Forum/Docks or + Leader-wound muster Reaction), pattern-a two-named-locations, reactions.md section, checklist 57–62 (CAD id collision, Muster=Approach, engaged, challenger-only wound), helpers, references, walkthrough.
