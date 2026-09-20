# 03 — Classify the printed text

← [02 — The card class](02-card-class.md) · [Index](README.md) · Next: [04 — Passives](04-passives.md)

Before writing code, split the card Text into clauses and assign each clause a **pattern**. One Leader often uses several patterns.

## Cesca worked example

Printed text:

1. *At the end of Dawn, draw five cards.* → **Passive** ([04](04-passives.md))
2. *City Reaction: After Cesca performs a Sorcerer ability • Wound an opposing character.* → **Reaction** ([06](06-reactions.md))
3. *City Action: Target an opposing non-Leader • Move a wound…* → **Action** ([05](05-actions.md))

## Pattern picker

| If the printed phrase looks like… | Use pattern | Go to |
|---|---|---|
| No player choice: "When X happens, Y" / "At the end of Dawn…" / "While wounded, +1 Combat" | **A — Passive** | [04](04-passives.md) |
| `<b>Action:</b>` or `<b>City Action:</b>` (player picks from the High Drama menu) | **C — Action** | [05](05-actions.md) |
| `<b>Reaction:</b>` or `<b>City Reaction:</b>` (prompt after a trigger) | **D — Reaction** | [06](06-reactions.md) |
| `<b>Technique:</b>` / `<b>Maneuver:</b>` / `<b>Gambling Technique:</b>` | **E — Technique / Maneuver** | [07](07-techniques-maneuvers.md) |
| Action that **issues a challenge** | **F — Challenge Action** | [08](08-challenges.md) |
| Stat printed as a dash (`—`) | Set `DashedCombat` / `DashedFinesse` / `DashedInfluence` + numeric `0` | [02](02-card-class.md) |
| "cannot challenge / intervene / pressure" (full ban) | Passive predicates + `eventCheck` | [04](04-passives.md) |
| "cannot issue Combat challenges" (one stat only) | `eventCheck` only — do **not** override `canChallenge()` to false | [04](04-passives.md) |

## Word traps beginners hit

### "City" on the ability ≠ CityCharacter

City Action / City Reaction only adds a `cardInCity($owner)` gate.

### "Target" is a special word

Only implement `IAbilityThatTargetsCharacters` when the text says **target**.  
"Wound an opposing character" without "target" is **not** a targeted ability interface — use a private eligibility helper instead.

### "Opposing"

Means **both**:

1. different controller, and  
2. same location.

Use `$theah->getOpposingCharactersAtLocation($location, $playerId)`. Do not roll your own `ControllerId !=` check alone.

### "En Garde …" (italic or bold prefix)

Usually a **precondition**: the character must be unengaged (`!$owner->Engaged`).  
It is **not** the same as paying an Engage cost (which queues `createCardEngagedEvent`).

### "Sorcerer Reaction" vs "Reaction after a Sorcerer ability"

- Text says **Sorcerer Reaction** → the Reaction class `implements ISorcererAbility` and must fire start + played events.
- Text says ordinary **City Reaction: After X performs a Sorcerer ability** → listen on `EventSorcererAbilityPlayed`, but do **not** implement `ISorcererAbility` (Cesca).

### "Engage" on a challenge Action

Three different shapes exist. Read [08 — Challenges](08-challenges.md) before copying Don Constanzo or Sanjay blindly.

## Process

1. Copy the card Text into a scratch note.
2. Number each independent clause.
3. Assign a pattern letter to each.
4. For each clause, open the matching wiki page **and** one reference Leader from [11 — Examples](11-examples.md).
5. Implement one clause at a time. Finish checklist last.

## Next

Start with passives if you have any → [04 — Passives](04-passives.md)  
Or jump to [05 — Actions](05-actions.md) / [06 — Reactions](06-reactions.md).
