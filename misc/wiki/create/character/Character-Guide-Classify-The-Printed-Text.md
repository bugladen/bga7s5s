# 03 — Classify the printed text

← [[02 — The card class|Character Guide The Card Class]] · [[Index|Implementing a Character Card]] · Next: [[04 — Passives|Character Guide Passives]]

Before writing code, split the card Text into clauses and assign each clause a **pattern**. One Character often uses several patterns.

## Worked examples

### Aldo (`_01007`)

1. *Aldo gains +1 Influence for each Renown at this location.* → **Passive** ([[04|Character Guide Passives]])
2. *City Action: Move a Renown…* → **Action** ([[05|Character Guide Actions]])

### Ise (`_03016`)

1. *During Dusk, you may choose not to move Ise Home.* → **Reaction** (opt-out / cancel-and-reissue) ([[06|Character Guide Reactions]])
2. *Ise has +1 Combat while wounded.* → **Passive** ([[04|Character Guide Passives]])
3. *Reaction: After an enemy character moves…* → **Reaction** ([[06|Character Guide Reactions]])

Notice: the Dusk line has no bold **Reaction:** keyword, but it is still a player choice — so it is Pattern D, not a silent passive.

## Pattern picker

| If the printed phrase looks like… | Use pattern | Go to |
|---|---|---|
| No player choice: "When X happens, Y" / "While wounded, +1 Combat" / "gains +1 Influence for each Renown" | **A — Passive** | [04](Character-Guide-Passives) |
| `<b>Action:</b>` or `<b>City Action:</b>` (player picks from the High Drama menu) | **C — Action** | [05](Character-Guide-Actions) |
| `<b>Reaction:</b>` or `<b>City Reaction:</b>` (prompt after a trigger) — or unlabelled "you may…" after a trigger | **D — Reaction** | [06](Character-Guide-Reactions) |
| `<b>Technique:</b>` / `<b>Maneuver:</b>` / `<b>Gambling Technique:</b>` | **E — Technique / Maneuver** | [07](Character-Guide-Techniques-And-Maneuvers) |
| Action that **issues a challenge** | **F — Challenge Action** | [08](Character-Guide-Challenge-Actions) |
| Stat printed as a dash (`—`) | Set `DashedCombat` / `DashedFinesse` / `DashedInfluence` + numeric `0` | [02](Character-Guide-The-Card-Class) |
| "cannot challenge / intervene / pressure" (full ban) | Passive predicates + `eventCheck` | [04](Character-Guide-Passives) |
| "cannot issue Combat challenges" (one stat only) | `eventCheck` only — do **not** override `canChallenge()` to false | [04](Character-Guide-Passives) |

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

### "Enemy" vs "opposing"

Some cards say **enemy** (Ise's move Reaction). That still means a different controller. Still require same location when the trigger is "moves to this location."

### "En Garde …" (italic or bold prefix)

Usually a **precondition**: the character must be unengaged (`!$owner->Engaged`).  
It is **not** the same as paying an Engage cost (which queues `createCardEngagedEvent`).

### "Sorcerer Reaction" vs "Reaction after a Sorcerer ability"

- Text says **Sorcerer Reaction** → the Reaction class `implements ISorcererAbility` and must fire start + played events.
- Text says ordinary **Reaction: After X performs a Sorcerer ability** → listen on `EventSorcererAbilityPlayed`, but do **not** implement `ISorcererAbility`.

### "When this character musters"

Must listen to **both** muster events (standard recruit **and** Approach play). See [[04|Character Guide Passives]].

### "Engage" on a challenge Action

Three different shapes exist. Read [[08 — Challenges|Character Guide Challenge Actions]] before copying Don Constanzo or Sanjay blindly.

### Unlabelled "you may…"

If the player chooses something after a trigger, it is almost always a **Reaction** (Pattern D), even without the bold keyword. Ise's Dusk opt-out is the classic example.

## Process

1. Copy the card Text into a scratch note.
2. Number each independent clause.
3. Assign a pattern letter to each.
4. For each clause, open the matching wiki page **and** one reference Character from [[11 — Examples|Character Guide Example Characters To Copy]].
5. Implement one clause at a time. Finish checklist last.

## Next

Start with passives if you have any → [[04 — Passives|Character Guide Passives]]  
Or jump to [[05 — Actions|Character Guide Actions]] / [[06 — Reactions|Character Guide Reactions]].
