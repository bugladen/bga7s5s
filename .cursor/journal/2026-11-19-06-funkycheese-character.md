# _05FunkyCheese (Geistliche Leichenberg) — finish Character

## Prior context
Corncob finished earlier today (Covert Weapon salvage + Forced mill + Covert +1 Parry). FunkyCheese stub open; CAD prototype (no clienttranslate).

## Classification
1. **While your Leader has more wounds than Leichenberg, he has +1 [Influence].** → Pattern A Ise flag ±1 Influence. Condition is Leader.Wounds > Owner.Wounds (not "while wounded"). Hook wound/heal on Owner **and** Leader (stale `characterHandled` delta like Benci). Also muster/approach on self so the bonus applies when he enters play against an already-wounded Leader. Silence blank/unblank. "he" = Leichenberg.
2. **Covert Reaction: When a pressure occurs at this location • Wound Leichenberg and target opposing non-Leader.** → Pattern D button Reaction on `EventPressureOccuring`. Covert = city + uncontrolled (Seadog/Wardogb). Valid-target gate: ≥1 opposing non-Leader at location. Wound Owner then chosen target (Excalibur order). `IAbilityThatTargetsCharacters` because text says "target". No states/JS.

## WHY these mirrors
- Ise flag discipline — attachments also mutate Influence; absolute rewrite would clobber.
- Leader wound comparison needs Leader events + enter-play hooks Ise does not have.
- Excalibur for dual-wound button picker; Seadog/Wardogb for Covert uncontrolled gate; Cesca for "target" interface.
- Novel trait **Priest** → TraitNames.
- Prototype CAD: plain strings, no clienttranslate.

## Touched
- `_05FunkyCheese.php` — IHasReactions + Leader-wound Influence passive + Silence hooks
- `reactions/Reaction_05FunkyCheese.php` — Covert pressure dual-wound (IAbilityThatTargetsCharacters)
- `TraitNames.php` — Priest
- tests: Card (2) + Reaction (7) — 9/9 PASS

## Unfinished
None for this card.

## Skill update 2026-11-20
Folded into create-character:
- SKILL shape rows: Leader-vs-Owner wound Influence; Covert pressure dual-wound Reaction
- pattern-a: "Stat bonus while Leader has more wounds than Owner" (Ise ≠ self-only; Benci delta; muster/approach; "he"=Owner)
- pattern-d: "Covert pressure → wound Owner and target opposing non-Leader" (not a PRESSURE_TYPE bit; Excalibur order; Cesca target)
- checklist 26 cross-ref, 89c, 103 CAD id note (CardNumber 14 shared with Dandy), 106 Priest, 120–121
- references: `_05FunkyCheese` + `Reaction_05FunkyCheese`
