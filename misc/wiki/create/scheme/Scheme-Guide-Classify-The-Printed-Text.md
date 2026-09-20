# 03 — Classify the printed text

← [[02 — The card class|Scheme Guide The Card Class]] · [[Index|Implementing a Scheme Card]] · Next: [[04 — Resolve effects|Scheme Guide Resolve Effects]]

Before writing code, split the card Text into clauses and assign each clause a **pattern**. One Scheme often uses several patterns.

## The horizontal rule matters

On scheme cards, text is usually split by `<hr>`:

- **Above the rule** → scheme effect during Planning (`EventResolveScheme`, and sometimes When-Revealed).
- **Below the rule** → **City Action / Action / Reaction / City Reaction / Forced** keywords — same family as on Characters, but with scheme-specific bases and location gates.

## Worked examples

### Crash the Party (`_02004`)

1. *Add a Renown to the Bazaar. Add a Renown to the Forum.* → **Resolve (trivial)** ([[04|Scheme Guide Resolve Effects]])
2. *City Reaction: After an opponent initiates a Pressure…* → **Reaction** ([[06|Scheme Guide Reactions]])

### No Mercy (`_03005`)

1. *Add Renown…* → **Resolve (auto)** ([[04|Scheme Guide Resolve Effects]])
2. *Put a Gang/Crime/Villainous card from your discard into your hand.* → **Resolve (discard pick state)** ([[04|Scheme Guide Resolve Effects]] + [[09|Scheme Guide Wiring States And JavaScript]])
3. *Reaction: After a Red Hand… challenge refused • Claim that location.* → **Reaction** ([[06|Scheme Guide Reactions]])

### Curry Favor (`_03053`)

1. *Add a Renown to two different locations.* → **Resolve (two-location pick)** ([[04|Scheme Guide Resolve Effects]])
2. *City Action: Spend a Renown • Claim… Each opponent draws.* → **Action (immediate — no HD state)** ([[05|Scheme Guide Actions]])

## Pattern picker

| If the printed phrase looks like… | Use pattern | Go to |
|---|---|---|
| "Add a Renown to [Location]" / "Move a Renown…" during resolve | **Resolve** | [04](Scheme-Guide-Resolve-Effects) |
| "Add a Renown to a city location" / "two different locations" (player picks) | **Resolve + state** | [04](Scheme-Guide-Resolve-Effects) + [09](Scheme-Guide-Wiring-States-And-JavaScript) |
| "Put a card from your discard into your hand" / "Search your discard" | **Resolve + discard pick** | [04](Scheme-Guide-Resolve-Effects) |
| "When this scheme is revealed, …" | **When-Revealed** | [07](Scheme-Guide-When-Revealed-Forced-And-Passives) |
| `<b>Forced:</b> At the end of Planning • …` | **Forced (Planning End)** | [07](Scheme-Guide-When-Revealed-Forced-And-Passives) |
| `<b>Forced:</b> At the end of High Drama • …` | **Forced (HD End)** | [07](Scheme-Guide-When-Revealed-Forced-And-Passives) |
| `<b>Forced:</b>` (other event, no menu) | **Forced on scheme `handleEvent`** | [07](Scheme-Guide-When-Revealed-Forced-And-Passives) |
| Equip costs +1 / ongoing modifiers with no choice | **Passive on scheme class** | [07](Scheme-Guide-When-Revealed-Forced-And-Passives) |
| `<b>City Action:</b>` / `<b>Action:</b>` | **Action** | [05](Scheme-Guide-Actions) |
| `<b>City Reaction:</b>` / `<b>Reaction:</b>` | **Reaction** | [06](Scheme-Guide-Reactions) |
| Action that **issues a challenge** | **Challenge Action** | [08](Scheme-Guide-Challenge-Actions) |
| "Then, each opponent does X" during resolve | **Multi-player sequential loop** | [04](Scheme-Guide-Resolve-Effects) |

## Word traps beginners hit

### "City" on the ability ≠ city-deck card

City Action / City Reaction only adds city-location gates (usually on the performer). The scheme still `extends Scheme`.

### "Spend a Renown" vs "Remove a Renown from [Location]"

| Printed text | Meaning | Event |
|---|---|---|
| **Spend a Renown** (no location named) | Player **score** Renown | `createPlayerLosesReknownEvent` |
| **Remove / Move a Renown from [Location]** | Location token | `createRenownRemovedFromLocationEvent` / move batch |

### Trait-prefixed keywords are not Sorcerer

**Strega / Mercenary / Diplomat / Hero / Villain / Scoundrel …** mean: the chosen performer must `hasTrait("…")`.

Do **not** `implement ISorcererAbility` unless the text literally says **Sorcerer**.

They can stack: "Sorcerer Strega …" is both a trait gate **and** `ISorcererAbility`.

### "Opposing"

Means **both** different controller **and** same location. Use `$theah->getOpposingCharactersAtLocation(...)`.

### "Target" is a special word

Only implement `IAbilityThatTargetsCharacters` when the text says **target**.

### Schemes do not go to discard after resolve

Gates that check "is my scheme active?" use `LOCATION_PLAYER_HOME`, not discard.

## Process

1. Copy the card Text into a scratch note.
2. Split above / below the `<hr>`.
3. Number each independent clause.
4. Assign a pattern to each.
5. For each clause, open the matching wiki page **and** one reference Scheme from [[11 — Examples|Scheme Guide Example Schemes To Copy]].
6. Implement one clause at a time. Finish checklist last.

## Next

Start with resolve text → [[04 — Resolve effects|Scheme Guide Resolve Effects]]
