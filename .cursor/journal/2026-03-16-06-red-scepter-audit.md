# The Red Scepter (_02006) Audit

## Context

Sixth card audit in the series (_02001 through _02006). Eddie asked to audit _02006 (The Red Scepter) against its card text. A Vodacce faction attachment with Attire/Heirloom/Unique traits — equips to Red Hand characters only, has a technique that wounds your own other character and engages the card for +1 parry in duels.

## Findings

### 2 bugs found, both fixed

**Bug 1: Missing engagement check in isAvailableToPlayer**

The technique says "engage this card" as part of its cost. If The Red Scepter is already engaged, the technique should be unavailable. `Technique_01049` (Gain Lethal) follows this pattern correctly with `!$owner->Engaged`. `Technique_02006` was missing this check entirely.

WHY this matters: Without it, the technique would appear as available even when the card is engaged. The engage event would fire on an already-engaged card. In the best case it's a no-op double-engage; in the worst case it allows the player to effectively use the technique "for free" (wound + parry without the engage cost being meaningful).

**Bug 2: Missing "other character" server-side validation**

`getArgsFromTechnique` correctly filters out the actor (`$character->Id != $actor->Id`) so the UI only shows other characters. But `actFromTechniqueWithId` didn't enforce this server-side. A modified client could submit the actor's own ID and wound the dueling character. This is the same class of input validation gap seen in 02002's state 02002_2 (where submitted card IDs weren't validated against the original top-3 list).

WHY this keeps recurring: The pattern is always the same — args method gates the UI correctly, but the act method trusts client input. Every card with a target restriction expressed in args should have a mirror validation in act.

## Pattern observations

This is an attachment technique, not a character technique. The technique's `getOwningCard` returns The Red Scepter itself (the attachment), not the character. This distinction matters for the engagement check — we're checking if the attachment is engaged, not the character. Some attachments can be engaged independently. The engage event in `actFromTechniqueWithId` uses `$owner->Id` for both the cardId and sourceId parameters, which is correct — The Red Scepter engages itself.

Compared to `Technique_01049`, the pattern is nearly identical: check duel, check engagement, do effect, create engage event. The only difference is that 02006 has an intermediate state to choose which character to wound (since there could be multiple at the location), while 01049 is immediate. 02006 uses `EventResolveTechnique` to trigger a state transition to the character selection state, while 01049 applies its effect directly in `handleEvent`.
