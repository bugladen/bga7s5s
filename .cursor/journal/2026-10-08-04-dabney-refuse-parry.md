# CAD Valeri — refuse allowed + Technique Parry replaces Thrust

## Ask
Action_05DabneyUS01 no longer unrefusable. Technique_05DabneyUS01: +1 Parry replaces +1 Thrust.
Card Text already said Parry; Action Name + refuse wiring + Technique impl still Thrust/unrefusable.

## Action — refuse unlocked
Kept `VALERI_CHALLENGE_TYPE` (still needed for ActivateTechnique Back block after move lands — Servo/Andriana class).
Removed refuse lock from:
- `FrameworkActionsTrait::actHighDramaChallengeActionReject` (server throw)
- `OnUpdateActionButtons.js` Refuse disable
- `ZombieTrait` force-Accept

Name: dropped "Unrefusable". Comment on type updated — Back-lock only, not refuse.

WHY keep the type instead of NORMAL: without it, Back from technique picker after Valeri already moved reopens adjacent-target UI / can stick. Refuse and Back are independent concerns.

## Technique — Parry replaces Thrust
- `CHOICE_THRUST` → `CHOICE_PARRY` (id 1 kept). Calculate: `parry += 1`.
- Removed `EventGenerateChallengeThreat` Thrust→threat path (Parry has none).
- **Duel-only** `isAvailableToPlayer`: with Thrust gone, Parry/Riposte need Calculate and Lethal on challenge was already a no-op at stat cap. Same shape as `Technique_PlusOneParry`.
- Dashed Parry hide mirrors existing Riposte hide (`currentRoundCombatCardsHaveDashedParry` on Theah).
- Challenge resolve state kept registered (stale transition safety) but Pass-only; technique won't be offered on challenge activate.
- Duel JS/args: Parry button + `parryAvailable`; zombie Riposte→Parry→Lethal.

## Skill docs
Updated create-character Pattern E/F, SKILL table, checklist 14/102, references — Dabney is no longer the unrefusable reference (SYG / 04045 / 02061 are).

## Deploy
Action, Technique, both State_*05DabneyUS01, Theah helper, FrameworkActionsTrait, ZombieTrait, OnUpdateActionButtons.js + .cad.js.
