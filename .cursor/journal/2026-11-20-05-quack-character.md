# Takama Siad (_05Quack) finish

## Context
Prototype CAD Character. User: finish via create-character skill; no clienttranslate.

## Text → patterns
1. **Action:** Target character at this location heals a wound • Their controller discards a card. → Pattern C two-step (`Action_05Quack`).
2. **Covert Reaction:** When you discard a Discovery or Alquimia • Wound target opposing character. → Pattern D button (`Reaction_05Quack`).

Stub typos left as-is ("controler", "When a you discard") — printed Text property for the test class.

## WHY decisions

### Action heal → controller discard
- Printed **target** → `IAbilityThatTargetsCharacters`.
- Plain Action (not City) → Home legal.
- Eligible = controlled + wounded + **controller hand nonempty**. WHY: right of • is mandatory discard; empty hand softlocks step 2. Uncontrolled mercs have no controller.
- Heal then discard via EVENTS: queue heal + `createTransitionEvent(target.ControllerId, …, "05Quack_2")` then `nextState("characterChosen")`. WHY EVENTS key for `_2`: active may be an **opponent** (Giacinto 04032). Local nextState to step 2 cannot change active.
- No Back on step 2 — heal already flushed.
- State ids `405024` / `4050242`. CardNumber 24 shared with Seadog — Seadog has no HD states so free (Serenissima/Robin shape).

### Covert Reaction on Discovery/Alquimia discard
- Covert = cardInCity + uncontrolled (recheck at resolve) — FunkyCheese/Seadog.
- "you" = `$event->ownerId == owner.ControllerId`. Opponent discarding from Quack's Action does **not** trigger.
- Any discard mode (payment/played/effect) — text says "when you discard", not "as an effect".
- Trait check during handleEvent: `runEventHubAfterCards=true` so card still in hand, traits intact.
- "opposing character" — Leaders legal (unlike FunkyCheese non-Leader). Printed **target** → interface.
- Valid-target gate before offer.

## Skill updates
- SKILL shape table rows for both abilities.
- pattern-c "Heal target • their controller discards"; pattern-d "Covert discard Discovery/Alquimia → wound opposing".
- references + checklist 89d/89e + 122/123; CAD id list includes Quack.
- Regression coverage lists + Quack suites.

## Tests
`Card_05Quack_Test`, `Action_05Quack_Test`, `Reaction_05Quack_Test` — 13 pass.

## Unfinished / watch
- Combo: heal your own Discovery-holding controller while Covert → Action discard can itself trip the Covert Reaction. Intentional per text; no loop (Reaction wounds, does not discard).
- Did not invent Maghreb/Surgeon into TraitNames — both already present.
