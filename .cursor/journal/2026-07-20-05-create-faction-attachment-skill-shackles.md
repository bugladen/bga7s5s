# create-faction-attachment skill update (post-Shackles)

## Why
Eddie asked to fold `_03066` (and related `_03063` discount fix) into the skill so the next opponent-equip / while-equipped cannot-move / Forced end-HD-destroy attachment does not rediscover:

1. HD Equip collapses `CHOSEN_PERFORMER` to the **target** under `CanEquipToOpponents`
2. "Less Finesse than your performer" → same-location ally with greater `ModifiedFinesse` (no two-step UI by default)
3. Attachment `ControllerId` after equip = **equipper**, not victim
4. Smuggling Run-style discounts must use `$attachment->ControllerId` for "opponent equips"
5. While-equipped "cannot move" (Shackles) ≠ Lodestone Home-only ≠ Harpoon remainder-of-duel
6. Move-only → skip swap activate-time checks
7. Forced destroy: `EventHighDramaPhaseEnd` → unequip → discard

## Files touched
- `SKILL.md` — triggers; Neutral/Shackles intro; ControllerId correction; shape-table rows; compose note
- `pattern-a.md` — CanEquipToOpponents; performer-Finesse resolution; `_03063` discount footgun
- `pattern-b.md` — scope table (Lodestone vs Shackles vs Harpoon); all-moves gate; Forced HD destroy
- `checklist.md` — 3b / 4b / 4c; item 17 Shackles note
- `wiring.md` — CanEquipToOpponents + discount footgun; B'' scope; Forced HD destroy
- `references.md` — `_01021`, `_03066`, `_03063`
- `helpers.md` — ControllerId note; performer resolution pointer
- `compose.md` — Shackles multi-shape note

## Not put in skill
- Speculation that Eddie may later want a true two-step Equip picker (journal only; skill says ask first)
- Full Makepeace `_01092` rewrite (different printed wording — called out as "don't copy blindly")
