# 03 — Classify the printed text

← [[02 — The card class|Risk Guide The Card Class]] · [[Index|Implementing a Risk Card]] · Next: [[04 — City Actions|Risk Guide City Actions]]

Before writing code, split the card Text into clauses and assign each clause a **pattern**. One Risk often uses several patterns.

## Worked examples

### Arrogant (`_03008`)

1. *City Action: Target an opposing character… Combat challenge* → **City Action (challenge)** ([[04|Risk Guide City Actions]])
2. *Gambling Maneuver: If more Influence… +1 Riposte. Draw* → **Maneuver** ([[06|Risk Guide Maneuvers]])

### Follow the Thread (`_03009`)

1. *Sorcerer Strega Action: Move… adjacent location…* → **Action (location chooser)** ([[05|Risk Guide Actions]])
2. *Strega Maneuver: −1 Thrust. Wound the adversary* → **Maneuver** ([[06|Risk Guide Maneuvers]])

### Glorious (`_03033`)

1. *Forced: After your adversary is destroyed… Heal* → **Forced on Risk class** ([[08|Risk Guide Passives Forced And Discounts]])
2. *Gambling Maneuver: equal or greater Influence… Wound* → **Maneuver (pure resolve)** ([[06|Risk Guide Maneuvers]])

## Pattern picker

| If the printed phrase looks like… | Use pattern | Go to |
|---|---|---|
| `<b>City Action:</b>` | **City Action** (`RiskCityAction`) | [04](Risk-Guide-City-Actions) |
| `<b>Action:</b>` (no "City") | **Action** (`RiskAction`) | [05](Risk-Guide-Actions) |
| `<b>Maneuver:</b>` / `<b>Gambling Maneuver:</b>` / `<b>Duelist Maneuver:</b>` / … | **Maneuver** | [06](Risk-Guide-Maneuvers) |
| `<b>Reaction:</b>` / `<b>City Reaction:</b>` | **Reaction** (`RiskReaction`) | [07](Risk-Guide-Reactions) |
| `<b>Forced:</b>` (no player choice) | **Forced on Risk `handleEvent`** | [08](Risk-Guide-Passives-Forced-And-Discounts) |
| "This card has −1 cost if…" / "While … this card has −1 cost" (with a Maneuver) | **Combat-card discount on Maneuver** | [08](Risk-Guide-Passives-Forced-And-Discounts) |
| "−1 cost if your Leader is…" **and no Maneuver printed** | **Action hand discount** | [08](Risk-Guide-Passives-Forced-And-Discounts) |
| `<b>Sorcerer …:</b>` | Ability + `ISorcererAbility` (start + played events) | Matching ability page |
| `<b>Strega / Diplomat / Duelist / Mercenary …:</b>` | **Trait gate** — not Sorcerer | Matching ability page |
| `<b>Leader …:</b>` | Leader is the performer (no performer chooser) | [04](Risk-Guide-City-Actions) / [05](Risk-Guide-Actions) |

## Word traps beginners hit

### "City Action" vs "Action"

| Heading | Performer pool |
|---|---|
| **City Action** | City characters only (`RiskCityAction`) |
| **Action** | Home **and** city (`RiskAction` parent pool) |

Even if the effect *sounds* city-only ("move to an adjacent location"), a plain **Action:** still allows Home performers. Do not swap the pool for `getCharactersInCityByPlayerId`.

### "City Reaction" is not a new base class

Still `extends RiskReaction`. Add: owner has ≥1 character in the city (`getCharactersInCityByPlayerId`). Do **not** invent `RiskCityReaction`.

### Trait prefixes are not Sorcerer

**Strega / Mercenary / Diplomat / Duelist / Pirate / Hero / Villain / Scoundrel / Gambling …** mean: gate the performer (or duel actor / `DUEL_GAMBLED`) with `hasTrait` or a duel global.

Do **not** `implement ISorcererAbility` unless the text literally says **Sorcerer**.

They can stack: "Sorcerer Strega Action" is both.

### "Leader Action" / "Leader City Action"

Leader **is** the performer. Fetch with `getLeaderByPlayerId`. Do **not** set `RequiresPerformerSelected = true` — there is no choice. If a later pressure/challenge pipeline needs `CHOSEN_PERFORMER`, set it yourself to the Leader id on announce.

### "Opposing"

Means **both** different controller **and** same location. Use `$theah->getOpposingCharactersAtLocation(...)`.

### "Target" is a special word

Only implement `IAbilityThatTargetsCharacters` / `IRiskThatTargetsCharacters` when the text says **target**. A UI chooser alone is not enough.

### "Wound your participant" vs "Wound the adversary"

| Phrase | Who takes the wound |
|---|---|
| Wound **the adversary** | `getDuelRoundOpponent()` |
| Wound **your participant** | `getDuelRoundActor()` |

Parse literally. Both appear on Gambling Maneuvers.

### "They claim" vs "you claim"

If the same sentence says the **opponent** claims a location and **you** move Renown, do not rewrite it into you-claim. Mirror Astute (`_03056`).

### "Engage" vs "En garde"

Opposite operations in this codebase:

- **Engage** → character becomes committed (`Engaged = true`) → `createCardEngagedEvent`
- **En garde** (verb) → character becomes ready (`Engaged = false`) → `createCardEngagedEvent`

Valid targets flip accordingly.

### Comparison wording

| Card phrase | Operator |
|---|---|
| "more … than" / "greater … than" | `>` |
| "equal or greater … than" | `>=` |

Use **Modified** stats (`ModifiedInfluence`, …), not printed base.

### "Controlled by an opponent" (location) ≠ "enemy character"

| Phrase | Filter |
|---|---|
| Location **controlled / claimed by an opponent** | `getControllerForLocation($loc) != 0 && != performer->ControllerId` |
| **Enemy character** at location | Controlled by an opposing player (character scan) |

Curious (`_03045`) is claim-control. Follow the Thread (`_03009`) is content (enemy or available Mercenary).

## Process

1. Copy the card Text into a scratch note.
2. Number each independent clause (Action / Maneuver / Reaction / Forced / discount).
3. Assign a pattern to each using the table above.
4. For each clause, open the matching wiki page **and** one reference Risk from [[11 — Examples|Risk Guide Example Risks To Copy]].
5. Implement one clause at a time. Finish checklist last.

## Next

If you have a City Action → [[04 — City Actions|Risk Guide City Actions]]  
Otherwise skip to the page that matches your first clause.
