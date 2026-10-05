# _05Cooper Don Vissenta Scarpa (CAD Leader)

## Classification
Printed Text → three shapes:
1. **Reaction** ignore opponent-ability wound on Vissenta → Pattern D cancel-first CardReaction (Cascade `02059` / Spaulders `04053` gates; **no** engage/wealth cost)
2. **Your Thugs at this location gain Technique +1 Thrust** → Pattern A Jean `_01067` aura (PlusOneThrust + ClassId `Technique_05Cooper`)
3. **Technique: Wound your Red Hand • +2 Riposte** → Pattern E picker (`Technique_02006` wound-other shape) + Calculate +2 Riposte

## WHY decisions
- **Reaction is optional (buttons), not Kaspar eventCheck passive.** Text is labelled Reaction with player choice; Kaspar is unconditional "cannot wound."
- **Target = Vissenta only** (`characterId == owner.Id`). Cascade covers any controlled character; Spaulders covers equipped host. Text names Vissenta.
- **No City / Engaged gate** on the Reaction — not City Reaction, no En Garde printed.
- **Aura ClassId reuses `Technique_05Cooper`** Jean-style (granted PlusOne* shares card Technique id string). Vissenta is not a Thug so she never receives the grant; her own `Technique_05Cooper` (wound Red Hand) stays distinct by instance/class behavior.
- **Muster hook included** (Bastien/Jean gap) — muster does not emit CardMoved.
- **Wound Red Hand after picker, not before transition.** Cost target is chosen; Daniella wound-before-transition is for fixed-self wound. Mirror `02006`.
- **Home excluded** from Thug aura — Jean/"at this location" convention (not Danilo Home+location stat aura).
- **CardNumber = 2** (stub); state id `52105002`. Image OCR said CAD-1 but Dabney already owns card 1.
- **Usurper** added to `TraitNames::$TraitsJson` (novel trait).

## Files
- `cards/cad/_05Cooper.php` — Leader + aura handleEvent + Reactions/Techniques
- `cards/cad/reactions/Reaction_05Cooper.php`
- `cards/cad/techniques/Technique_05Cooper.php`
- `States/cad/State_duelChooseTechnique_05Cooper.php`
- States.php / states.inc.php / TraitNames + cad JS enter/buttons/leave

## Skill update (create-character)
Captured Cooper learnings into create-character:
- SKILL shape table: Reaction ignore-wound (vs Kaspar eventCheck); Technique aura leave-play Destroyed+CardSentToLocker; Wound-Red-Hand Technique
- pattern-a Location Technique aura: Muster + leave-play ClassId clear (hub-first locker Location trap); skip-self when ClassId shared
- pattern-d: Character ignore-wound no-cost CardReaction (Cooper vs Cascade vs Spaulders)
- pattern-e: Wound chosen ally then +N (02006/Cooper — not Daniella fixed-self)
- checklist 59/102–105; references for _05Cooper / Jean / Bastien / Yepikhodov leave-play

## Leave-play aura clear (follow-up)
Filled CardSentToLocker gap on Cooper, Jean `_01067`, Yepikhodov `_03051`, and Bastien `_01063`. Destroyed already stripped; locker path did not (hub-first, no CardMoved). All four clear granted ClassId across controlled in-play on Destroyed + CardSentToLocker. Jean/Bastien skip self (native Technique shares ClassId with granted aura).

## Unfinished / watch
- Approach-deck entry without Muster/Recruited/CardMoved still a known Jean-family hole; not added unless Eddie asks.
- Zombie on Red Hand picker bare-nextStates like 02006 (can skip wound, still get Calculate +2).
