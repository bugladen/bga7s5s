# 03 — Classify the printed text

← [[02 — The card class|CityAttachment Guide The Card Class]] · [[Index|Implementing a CityAttachment Card]] · Next: [[04 — Passives and Forced|CityAttachment Guide Passives And Forced]]

Before writing code, split the card Text into clauses and assign each clause a **pattern**. One attachment often uses several patterns — Devil Jonah's Bones uses three.

## Worked example — Devil Jonah's Bones (`_03cd05`)

1. *Forced: When a character equips this card • Wound them.* → **Forced on equip** ([[04|CityAttachment Guide Passives And Forced]])
2. *…reveal an additional card* → **Steady-state override** ([[07|CityAttachment Guide Steady State And Custom States]])
3. *You may reveal cards from the bottom…* → **Custom state in core flow** ([[07|CityAttachment Guide Steady State And Custom States]])

## Worked example — Guild Triskelion (`_01198`)

1. *The equipped character gains Duelist.* → **Passive trait grant** ([[04|CityAttachment Guide Passives And Forced]])
2. *City Action: … Issue a challenge…* → **Action** ([[05|CityAttachment Guide Actions]])

## Worked example — Silver Spine (`_03cd21`)

1. *Forced: Each Day, the first time an opponent's Risk targets the equipped character • Cancel…* → **Forced once-per-Day cancel** ([[04|CityAttachment Guide Passives And Forced]])

## Pattern picker

| If the printed phrase looks like… | Use pattern | Go to |
|---|---|---|
| `+1 [Combat]`, `+2 [Influence]` | Constructor modifiers only | [02](CityAttachment-Guide-The-Card-Class) |
| **"May only equip to …"** / trait gate on equip | Override `canAttachTo` (and usually `eventCheck`) | [04](CityAttachment-Guide-Passives-And-Forced) briefly; see also FactionAttachment equip page for dual-gate detail |
| **"...gains Trait"** / **"...gains Duelist"** | Passive trait grant (equip + unequip pair) | [04](CityAttachment-Guide-Passives-And-Forced) |
| `<b>Forced:</b>` — auto, no choice (wound on equip, etc.) | Forced on card `handleEvent` | [04](CityAttachment-Guide-Passives-And-Forced) |
| `<b>Forced:</b> first time each Day an opponent's Risk targets…` | Pattern G once-per-Day cancel | [04](CityAttachment-Guide-Passives-And-Forced) |
| `<b>Action:</b>` or `<b>City Action:</b>` | AttachmentAction | [05](CityAttachment-Guide-Actions) |
| `<b>Reaction:</b>` or `<b>City Reaction:</b>` | AttachmentReaction | [06](CityAttachment-Guide-Reactions) |
| **"reveal an additional …"** / **"has +N wounds capacity"** / other lasting play-area property | Steady-state `get*` override | [07](CityAttachment-Guide-Steady-State-And-Custom-States) |
| Mid-duel / mid-pressure **player choice** inside an auto flow | Custom state inserted into core flow | [07](CityAttachment-Guide-Steady-State-And-Custom-States) |
| `<b>Technique:</b>` / `<b>Maneuver:</b>` (rare on city gear) | Technique / Maneuver — mirror FactionAttachment examples | [[FactionAttachment Techniques|FactionAttachment Guide Techniques And Maneuvers]] |

## Word traps beginners hit

### CityAttachment vs FactionAttachment

| Stub | Guide |
|---|---|
| `extends CityAttachment` | This guide |
| `extends FactionAttachment` | [[FactionAttachment guide|Implementing a FactionAttachment Card]] |

### CityAttachment vs CityEventCard

| Stub | Meaning |
|---|---|
| `extends CityAttachment` | Lasting gear that equips onto a character |
| `extends CityEventCard` | One-shot city event — **different skill** |

### "City Action" does not change the base class

City Action on a CityAttachment still uses `AttachmentAction` + a `cardInCity` gate. There is **no** `AttachmentCityAction`.

### "Destroy this card" vs "Discard" vs "Sink"

| Word | Typical destination for a **city** attachment |
|---|---|
| **Destroy** this attachment | Unequip, then **city discard** |
| **Discard** | City discard / discard-from-play helpers |
| **Sink** | Rare on city gear — if printed, read carefully (faction-deck sink is a FactionAttachment pattern) |

Match the printed word. See [[05|CityAttachment Guide Actions]] for the destroy chain.

### "Engage this card" vs "Engage the equipped character"

| Printed cost | What you engage |
|---|---|
| Engage **this card** | The attachment (`$attachment->Id`) |
| Engage the **equipped** character / performer | The character (`$owner->Id`) |

### Steady-state vs event mutation

If the bonus is a **property of the play area** ("reveal +1 when gambling"), override a `get*` method.  
Do **not** bump a global inside `handleEvent` — that is a common first draft that gets rejected on review. See [[07|CityAttachment Guide Steady State And Custom States]].

### Forced vs Reaction

| Printed word | Implementation |
|---|---|
| **Forced** | Card `handleEvent` — mandatory, no player choice |
| **Reaction** | Separate `AttachmentReaction` class — opt-in prompt |

Do not put Forced effects in a Reaction file. The pre-commit hook would then demand `setUsed` / `isAvailable` for something that is not optional.

## Process

1. Copy the card Text into a scratch note.
2. Number each independent clause.
3. Assign a pattern to each.
4. For each clause, open the matching wiki page **and** one reference from [[10 — Examples|CityAttachment Guide Example CityAttachments To Copy]].
5. Implement one clause at a time. Finish checklist last.

## Next

Start with Forced / passives if you have any → [[04 — Passives and Forced|CityAttachment Guide Passives And Forced]]  
Or jump to [[05 — Actions|CityAttachment Guide Actions]] / [[07 — Steady-state|CityAttachment Guide Steady State And Custom States]].
