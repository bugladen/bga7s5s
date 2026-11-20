# _05Serenissima (Patricia Moustakas) — Leader finish

## Why this note
User said "Create Schema skill" but stub `extends Leader` with Resolve/Combat/CrewCap/Panache and Covert Action + Technique text — not a Scheme. Used **create-character**. Prototype CAD: no clienttranslate.

## Text → patterns
1. **Passive any-trait** — Uwe `_01043` `hasTrait($trait, $queryCard)` override. Return true for **any** trait when `$queryCard !== null` + same ControllerId + not blanked. Null query = printed only (opponent trait gates must not see fiction). Systemic limit unchanged: callers must pass `$queryCard` (only Uwe consumers do today).
2. **Covert Action** — Pattern C. Uncontrolled City (Vulture/Corncob). Own discard Bureaucracy/Discovery → Locker (`_02053` remove+locker) • En garde **Engaged** at location. **WHY Engaged on picker (user):** right of `•` is effect, not cost — but Engarde targets must be Engaged (01152a/02051); ready must not highlight. Still gate *offer* on ≥1 Engaged so state 2 cannot softlock empty. **Engarde = `createCardEngagedEvent`** (02051) — do **not** copy Action_01152a's `createCardEngagedEvent` bug. Stash `CHOSEN_CARD` on pick so Back stays legal on discard step; locker+Engarde queue together on target pick. No opposing filter ("target character"). States `405016` / `4050162`; EVENTS key only `"05Serenissima"` (step 2 via local `cardChosen`).
3. **Technique** — Pattern E. Subclass `Technique_PlusOneParry` + actor hand discard picker (`createTechniqueTransitionEvent` HIGHEST) + `array_rand` adversary discard (skip empty — 01093). **Not Covert.** Duel state `52105016`. CardNumber 16 collides with Robin attachment — Robin has no HD/duel pickers so ids free (Corncob/Snow White pattern).

## Files
- `_05Serenissima.php`, `Action_05Serenissima.php`, `Technique_05Serenissima.php`
- States + JS cad hooks + EventHandlers discard enable
- Tests: Card / Action / Technique — 10 PASS

## Unfinished
None for this card. Skill update for "any trait" / discard→Locker Covert Action / discard•Parry+random Technique could fold into create-character later if user asks (like Corncob).
