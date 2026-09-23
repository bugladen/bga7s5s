# Yield (02020) unavailable with Engaged opposing non-Leader

## Report
Daniella (03013) + En Garde Panzerhand (02017 Eisenfaust). Opposing Solomonia (02044, non-Leader). Yield Action_02020 greyed when it should be playable.

## Root cause
`getPerformers` / target args / `isValidTargetForAbility` filtered `!$character->Engaged`.

Printed text: "target an opposing non-Leader" — **no** "that is en garde". Pattern docs (A.15 contrast / B.7 contrast) already say Yield **Already Engaged → auto-wound**. March 2026 audit wrongly called the Engaged filter "correct" (copied B.7 mental model).

When Solomonia (or any sole opposing non-Leader) is Engaged, availability returned empty → card greyed. Panzerhand En Garde was a red herring for the cost side; she was fine.

## Fix
1. Base → `RiskCityAction` (printed City Action; was bare `RiskAction`).
2. Targets: non-Leader only — drop Engaged gate.
3. Attachments: require `!$Engaged` for Melee Weapon / Eisenfaust (Engage cost; mirror Action_04019). Eisenfaust alone still qualifies (Panzerhand is Armor+Eisenfaust, not Weapon).
4. After attachment Engage: if target already Engaged → notify + wound, skip `02020_3`. Else → opponent Engage/Decline as before.
5. `_02020` implements `IRiskThatTargetsCharacters` to match Action's Cesca Target interface.

## WHY not B.7 filter
B.7 print literally says "that is en garde". Yield / Duckfoot / Point of Order do not — "may engage / if they do not" resolves as auto-consequence when engage is impossible.
