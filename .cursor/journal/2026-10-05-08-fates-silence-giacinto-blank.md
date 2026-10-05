# Fate's Silence blanking vs Giacinto Influence + clone tooltip

## Report
Fate's Silence on Giacinto (_04032): Sorcerer -1 Influence still applied.
Equipped Silence clone: empty Text + no RPT in full-text tooltip.

## Root causes

### 1. Stamped aura survives blanking
Pattern E.3 correctly skips polymorphic `handleEvent` on blanked Characters
(Theah → `handleCoreCharacterEvent` only). That stops *new* applications of
Giacinto's passive — but Influence was already stamped via
`GIACINTO_INFLUENCE_REDUCTION_CONDITION` + ModifiedInfluence before Silence
equipped. Nothing cleared those stamps, so the penalty kept working.

WHY this is easy to miss: blanking was designed around Turais-style Reactions
and Maryam-style Forced that fire *on events*. Condition-stamped auras are a
different shape — effect is already on the victim card.

Also: Action_04008 only targets characters in the city (same location as
Strega performer). "Approach Giacinto" here means the approached character
who is now at a city location with opposing Strega — not Home (Silence can't
equip at Home).

### 2. FakeAttachment missing display fields
`_04008_Silence` was a bare `Attachment` with Name/Image/Traits only — no
`Text`, no RPT, no `FactionCardTrait`/`deckOrigin`. Full-text attachment
tooltip reads the clone (Risk is in LOCATION_PERMANENTLY_HIDDEN). Burden has
the same historical gap; Silence's blanking reminder makes empty text worse.

## Fix
1. `Character::onAbilitiesBlanked` / `onAbilitiesUnblanked` empty hooks.
2. `_04008_Silence` calls them right after add/remove of FATES_SILENCE_CONDITION.
3. `_04032` clears all debuffs on blank; re-applies at location on unblank.
4. `_04032::handleEvent` early-returns if `abilitiesAreBlanked()` (defense —
   Card early-return still lets subclass code after Character::handleEvent run
   if something calls handleEvent while blanked).
5. Silence clone → `FactionAttachment` with copied Text/RPT/cost/set#.
6. pattern-e.md + checklist E.3 updated so next blanking card doesn't miss aura cleanup.

## WHY hooks on Character (not Giacinto-only in Silence)
Silence shouldn't know every stamped-aura host. Hook is the extension point;
only characters that stamp lasting effects on others need to override.

## Audit: who else needs onAbilitiesBlanked?

**Same class (Character text-box stamps lasting Modified* on *others*, Silence-legal):**
- Tomoe Sango `_04043` — duel adversary -1 Finesse + TOMOE_SANGO_CONDITION
- Danilo Danini `_04002` — Thugs +1 Finesse/+1 Resolve via BuffedThugIds

**Same stamp-on-others shape but Leader → Silence cannot equip (non-Leader only):**
- Soline el Gato `_01089`

**Related gap (self-buffs that stick while blanked):** Benci `_04001`, Axelle
`_04022`, Ise `_03016`, Térence `_03028`, Guillén `_01064`, Pavel `_01120`,
Rena `_01040`, Nazem `_01119`, Íñigo `_03039`, Elena `_03004`, Joern `_03015`,
Angeline `_03026`, Edeline `_01037`, Rosine `_01041`, Aldo `_01007`, etc.

**Not in scope:** Schemes (Contempt `_01143`, Adrift `_04044`, Épée `_01071`)
and Attachments (Forged, Harpoon, Assassin's Garb `_04006`) — Silence blanks
the *character* text box only.

## Future-card note (done 2026-10-05)

Documented so new stamped passives wire hooks without rediscovering Giacinto:

- create-character `pattern-a.md` — section "Fate's Silence blanking"
- create-character `checklist.md` — item 90 (+ Giacinto 89 / Sango 99 callouts)
- create-character `SKILL.md` — Giacinto shape-table row
- create-risk `SKILL.md` / `pattern-e.md` / `checklist.md` / `references.md` — E.3
- create-city-character `checklist.md` — Pattern F location passives

## Gap-fill complete (2026-10-05)

Wired `onAbilitiesBlanked` / `onAbilitiesUnblanked` (+ handleEvent blank early-return) on:

**Stamp-on-others:** `_04043` Tomoe, `_04002` Danilo (plus prior `_04032` Giacinto)

**Self-buffs:** `_04001` Benci, `_04022` Axelle, `_03016` Ise, `_03028` Térence,
`_01064` Guillén, `_01120` Pavel, `_01040` Rena, `_01119` Nazem, `_03039` Íñigo,
`_03004` Elena, `_03015` Joern (dusk Resolve + TURN_PHASE==DUSK reapply),
`_03026` Angeline, `_01037` Edeline, `_01041` Rosine (count-inferred ±1),
`_01007` Aldo

**Skipped (intentional):** Soline `_01089` Leader (Silence non-Leader only);
schemes/attachments (not blanked by Silence).

## Technique grant auras (2026-10-05 follow-up)

Wired blank/unblank for Jean `_01067`, Bastien `_01063`, Yepikhodov `_03051`,
Stranahan `_02022`, Cooper `_05Cooper` (Leader — Silence-immune but hooks for
consistency). Same clear helpers as leave-play; unblank re-grants at location
with ClassId dedup. Stranahan also got Jean-shaped `clearGrantedLethalTechniques`
+ CardSentToLocker (was location-only destroy clear before).

pattern-a Location Technique grant table now lists the blanking hooks as required.
