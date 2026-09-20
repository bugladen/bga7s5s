# 03 — Classify the printed text

← [[02 — The card class|FactionAttachment Guide The Card Class]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[04 — Equip restrictions|FactionAttachment Guide Equip Restrictions]]

Before writing code, split the card Text into clauses and assign each clause a **pattern**. One attachment often uses several patterns — Tabard uses three; Shackles uses three different ones.

## Worked example — Cavalier Hat (`_01073`)

1. *May only equip to your Duelist.* → **Equip restriction** ([[04|FactionAttachment Guide Equip Restrictions]])
2. Stat / combat numbers on the card face → **Constructor fields only** ([[02|FactionAttachment Guide The Card Class]])
3. *City Action: …* → **Action** ([[06|FactionAttachment Guide Actions]])

## Worked example — Tabard (`_01075`)

1. *May only equip to a non-Diplomat.* → **Equip restriction** ([[04|FactionAttachment Guide Equip Restrictions]])
2. *Equipped character gains Musketeer.* → **Passive trait grant** ([[05|FactionAttachment Guide Passives And Forced]])
3. *City Action: …* → **Action** ([[06|FactionAttachment Guide Actions]])

## Worked example — Lodestone (`_03065`)

1. *Opponents' abilities cannot move this character Home.* → **While-equipped condition** ([[05|FactionAttachment Guide Passives And Forced]])
2. *City Action: Sink this card • Move … Home.* → **Immediate Action** ([[06|FactionAttachment Guide Actions]])

## Pattern picker

| If the printed phrase looks like… | Use pattern | Go to |
|---|---|---|
| `+1 [Combat]`, `+2 [Influence]`, Riposte/Parry/Thrust numbers | Constructor modifiers / combat stats | [02](FactionAttachment-Guide-The-Card-Class) |
| **"May only equip to your X"** / trait gate on equip | Equip restriction (dual gate) | [04](FactionAttachment-Guide-Equip-Restrictions) |
| **"May only equip to an opposing character"** | Opponent-equip (`CanEquipToOpponents`) | [04](FactionAttachment-Guide-Equip-Restrictions) |
| **"...and they gain Trait"** | Passive trait grant | [05](FactionAttachment-Guide-Passives-And-Forced) |
| **"(If they lose their Weapon, destroy this card.)"** | Conditional auto-destroy | [05](FactionAttachment-Guide-Passives-And-Forced) |
| **"Equipped character cannot move"** / opponents cannot move Home | While-equipped condition | [05](FactionAttachment-Guide-Passives-And-Forced) |
| `<b>Forced:</b>` — auto, no choice | Forced on card `handleEvent` | [05](FactionAttachment-Guide-Passives-And-Forced) |
| `<b>Forced:</b> At the end of High Drama • Destroy this attachment` | Forced destroy | [05](FactionAttachment-Guide-Passives-And-Forced) |
| `<b>Action:</b>` or `<b>City Action:</b>` | AttachmentAction | [06](FactionAttachment-Guide-Actions) |
| `<b>Reaction:</b>` or `<b>City Reaction:</b>` | AttachmentReaction | [07](FactionAttachment-Guide-Reactions) |
| `<b>Offhand</b>` | Constructor flag only | [02](FactionAttachment-Guide-The-Card-Class) |
| `<b>Technique:</b>` / `<b>Maneuver:</b>` | Technique / Maneuver | [08](FactionAttachment-Guide-Techniques-And-Maneuvers) |
| `<b>Gambling Technique:</b>` / `<b>Gambling Maneuver:</b>` | Technique + Gambling gates | [08](FactionAttachment-Guide-Techniques-And-Maneuvers) |
| `<b>Sorcerer …:</b>` | Ability class + `ISorcererAbility` | matching Action/Reaction/Technique page |
| `<b>Strega Reaction:</b>` / `<b>Diplomat Action:</b>` / etc. | **Trait gate on performer**, not Sorcerer | matching ability page |

## Word traps beginners hit

### FactionAttachment vs CityAttachment

| Stub | Guide |
|---|---|
| `extends FactionAttachment` | This guide |
| `extends CityAttachment` | City-attachment skill (different guide) |

### "City Action" does not change the base class

City Action on a FactionAttachment still uses `AttachmentAction` + a `cardInCity` gate. There is **no** `AttachmentCityAction`.

### "Sorcerer" vs "Strega" / "Diplomat" / "Musketeer"

| Keyword | Meaning |
|---|---|
| **Sorcerer** Action/Reaction | Implement `ISorcererAbility` + emit Start/Played events |
| **Strega** / **Diplomat** / **Musketeer** / … | The equipped character (performer) must `hasTrait("…")`. **Not** a Sorcerer ability |

They can stack: "Sorcerer Strega Reaction" is both.

### "Gambling" is not a trait

**Gambling Technique / Maneuver** means the combat card was obtained by gambling this duel round. Gate `Game::IN_DUEL` **and** `Game::DUEL_GAMBLED`. Do **not** `hasTrait("Gambling")`.

### "Engage this card" vs "Engage the equipped character"

| Printed cost | What you engage |
|---|---|
| Engage **this card** | The attachment (`$attachment->Id`) |
| Engage the **equipped** character / performer | The character (`$owner->Id`) |

Gate availability on whichever card must be unengaged.

### "Sink" vs "discard" vs "destroy"

| Word | Typical destination |
|---|---|
| **Sink** (equipped attachment) | Bottom of owner's **faction deck** |
| **Discard** | Discard pile |
| **Destroy** this attachment | Unequip then discard-from-play |

Match the printed word. See [[06|FactionAttachment Guide Actions]] for the sink chain.

### "Opposing"

Means **both** different controller **and** same location.

### "Target" is a special word

Only implement `IAbilityThatTargetsCharacters` when the text says **target**.

### Remainder-of-duel vs while-equipped

| Duration | Clear when | Pattern |
|---|---|---|
| While equipped / while this card is attached | Unequip | [[05|FactionAttachment Guide Passives And Forced]] |
| For the remainder of the duel | Duel end / cancel | [[08|FactionAttachment Guide Techniques And Maneuvers]] |

Do not mix these up — wrong clear timing is a common bug.

## Process

1. Copy the card Text into a scratch note.
2. Number each independent clause.
3. Assign a pattern to each.
4. For each clause, open the matching wiki page **and** one reference from [[11 — Examples|FactionAttachment Guide Example FactionAttachments To Copy]].
5. Implement one clause at a time. Finish checklist last.

## Next

Start with equip restrictions if you have any → [[04 — Equip restrictions|FactionAttachment Guide Equip Restrictions]]  
Or jump to [[05 — Passives|FactionAttachment Guide Passives And Forced]] / [[06 — Actions|FactionAttachment Guide Actions]].
