# 03 — Classify the printed text

← [[02 — The card class|CityCharacter Guide The Card Class]] · [[Index|Implementing a CityCharacter Card]] · Next: [[04 — Passives|CityCharacter Guide Passives]]

Before writing code, split the card Text into clauses and assign each clause a **pattern**. One CityCharacter often uses several patterns — Penya uses three.

## Worked example — Penya (`_03cd01`)

1. *Penya cannot intervene.* → **Hard ban** ([[04|CityCharacter Guide Passives]])
2. *City Forced: When Penya participates in a duel or would be wounded • …* → **City Forced** ([[04|CityCharacter Guide Passives]])
3. *City Action: Engage Penya • Move Penya and another…* → **Action** ([[05|CityCharacter Guide Actions]])

## Worked example — Julius (`_03cd10`)

1. *Negotiable* keyword → set `$this->Negotiable = true;` (card class only)
2. *Reaction / City Reaction after recruit or move…* → **Reaction** ([[06|CityCharacter Guide Reactions]])

## Pattern picker

| If the printed phrase looks like… | Use pattern | Go to |
|---|---|---|
| **"Negotiable"** keyword | Constructor flag only | [02](CityCharacter-Guide-The-Card-Class) |
| Stat printed as a dash (`—`) | `Dashed*` + numeric `0` | [02](CityCharacter-Guide-The-Card-Class) |
| "cannot intervene / challenge / pressure" (full ban) | Predicate + `eventCheck` | [04](CityCharacter-Guide-Passives) |
| `<b>City Forced:</b>` or `<b>Forced:</b>` — auto, no choice | Forced / passive `handleEvent` | [04](CityCharacter-Guide-Passives) |
| No player choice: "While wounded, +1 Combat" / ongoing bonus | Passive `handleEvent` | [04](CityCharacter-Guide-Passives) |
| `<b>Action:</b>` or `<b>City Action:</b>` | Action (`CharacterAction`) | [05](CityCharacter-Guide-Actions) |
| `<b>Reaction:</b>` or `<b>City Reaction:</b>` — or unlabelled "you may…" after a trigger | Reaction | [06](CityCharacter-Guide-Reactions) |
| `<b>Technique:</b>` / `<b>Maneuver:</b>` | Technique / Maneuver | [07](CityCharacter-Guide-Techniques-And-Maneuvers) |
| Action that **issues a challenge** | Challenge Action | [08](CityCharacter-Guide-Challenge-Actions) |

## Word traps beginners hit

### CityCharacter vs "City Action" on a Character

| Stub | Guide |
|---|---|
| `extends CityCharacter` | This guide |
| `extends Character` with a City Action | [[Character guide|Implementing a Character Card]] |

### `CharacterAction` vs `EventCityAction`

City Actions **on a CityCharacter** use `CharacterAction`.

`EventCityAction` is for city **event** cards that discard after use. Using it for Penya would treat him like a one-shot event — wrong.

### "Would be wounded" vs "When wounded"

| Verb | Event | Behavior |
|---|---|---|
| **would be** wounded | `EventCharacterBeingWounded` | Often set `$event->canceled = true` (wound never lands) |
| **is / has been** wounded | `EventCharacterWounded` | Wound already applied; react after |

Penya uses "would be" — he cancels the wound, then Forced fires.

### "Participates in a duel" vs "Is challenged"

| Phrase | Event |
|---|---|
| Participates in a duel | `EventDuelStarted` (after the duel actually starts) |
| Is challenged | `EventChallengeIssued` (before accept/refuse) |

A rejected challenge must **not** fire Penya's Forced — that is why he uses `EventDuelStarted`.

### "Opposing"

Means **both** different controller **and** same location.

Use `$theah->getOpposingCharactersAtLocation($location, $playerId)`.

### "Target" is a special word

Only implement `IAbilityThatTargetsCharacters` when the text says **target**.

### "En Garde …"

Usually a **precondition**: the character must be unengaged (`!$owner->Engaged`).  
Not the same as paying an Engage **cost** (which queues `createCardEngagedEvent`).

### "Engage X • …" as Action cost

Engagement is the **cost**. Queue `createCardEngagedEvent` yourself, then move with `$engage = false` so companions are not engaged by the move. Penya's Action does this — see [[05|CityCharacter Guide Actions]].

## Process

1. Copy the card Text into a scratch note.
2. Number each independent clause.
3. Assign a pattern to each.
4. For each clause, open the matching wiki page **and** one reference from [[11 — Examples|CityCharacter Guide Example CityCharacters To Copy]].
5. Implement one clause at a time. Finish checklist last.

## Next

Start with bans / Forced / passives if you have any → [[04 — Passives|CityCharacter Guide Passives]]  
Or jump to [[05 — Actions|CityCharacter Guide Actions]] / [[06 — Reactions|CityCharacter Guide Reactions]].
