# Rena (_05Gary) flip + abilities

## Context
User asked to finish `_05Gary` / `_05GaryFlipped` via Character skill. Novel pattern: Forced flip at 4+ wounds to B-side class, carrying wounds/attachments/traits/conditions. Prototype — no `clienttranslate()`.

## Classification
A-side (_05Gary):
1. Setup reveal-attach-equip −1 cost → Constanzo EventTableSetup + states; cost via setup-local −1 (hands empty at SETUP — only WealthCost≤1 complete free)
2. Reaction wound-redirect at location → Pattern D cancel-first (03031/02016 shape), destination always Rena
3. Forced flip ≥4 wounds → new instantiateCard('05GaryFlipped', sameId) + copy runtime + characterFlipped notif

B-side (_05GaryFlipped):
1. Combat card is Attachment → +1 Parry +1 Thrust on EventDuelCalculateCombatCardStats
2. Reaction opposing destroyed → En Garde (Yevgeni shape; gate Engaged)
3. Technique destroy attachment → +1 Thrust + heal (Tomas 04013 shape)

## WHY flip = same Id, new class
card_serialized holds PHP class; leader_card_id is numeric. Destroy recreate already uses instantiateCard(sameId) but wipes state — we copy instead. Do not go through destroy.

## WHY setup −1 baked into setup cost, not permanent getEquipDiscount
Printed "During setup … at -1 cost" — not Kalla forever-while-equipping. Empty hand at SETUP_TABLE (panache draw is Planning) → only cost≤0 after −1 succeed; Pass when none affordable.

## Implemented
- `_05Gary`: setup (EventTableSetup → choose affordable attach → equip −1 → ack), Reaction_05Gary wound redirect, Forced flipToBSide
- `_05GaryFlipped`: combat-card Attachment +1P/+1T, Reaction_05GaryFlipped En Garde (createCardEngardedEvent), Technique_05GaryFlipped destroy+heal+1 Thrust
- States: SETUP_TABLE_05GARY/_2, DUEL_CHOOSE_TECHNIQUE_05GARYFLIPPED + cad State classes + JS + characterFlipped notif/animation
- No clienttranslate on prototype strings
- LEADER_STAT_LABELS += Rena

## WHY flip deltas for Modified*
Attachments already applied modifiers to A's Modified*; B has different printed bases. Copying Modified* raw would keep A's printed contribution. Delta = Modified−printed then B.Modified = B.printed + delta preserves attachment/scheme churn.

## WHY setup only WealthCost≤1
SETUP_TABLE before Planning panache draw → empty hand. −1 discount → only free completes. Auto-skip transition when none affordable.

## Gotcha fixed mid-impl
Reaction_05GaryFlipped initially used createCardEngagedEvent (Engage) — must be createCardEngardedEvent for En Garde (Yevgeni shape).

## Unfinished / watch
- No regression suite yet (prototype CAD)
- Higher-cost setup attach with future "pay from deck" not supported
- Flip mid-duel: EventHub duel actor_serialized refresh on Wounded should pick up B via getCardById after addCardToWorld

## Bug: Reaction_05Gary swallowed Buratino→Kaspar (2026-10-11 later)

User: Buratino City Action wound Kaspar at Rena's location → Reaction offered → Wound Rena → message fired, nobody took a wound.

WHY: Kaspar `_03014` zeros `EventCharacterBeingWounded.wounds` in **eventCheck** (queue time), before any card `handleEvent`. Same pattern as Breastplate `_01153` journal note. Reaction_05Gary canceled+cloned without checking `wounds > 0`, so it cloned a 0-wound event, canceled the original (already a no-op), and re-queued wounds=0 → EventHub skips `CharacterWounded`.

Fix: early-return when `$event->wounds <= 0`. Printed "would suffer a wound" is false after Kaspar/Breastplate nullify. Reaction must not offer.

## Bug: post-flip debug_EngageCard invisible on other browser (2026-10-11)

User: after Rena flips, `debug_EngageCard` engage not picked up by the other browser.

WHY not server/Id: flip keeps same numeric Id; EventHub still notifies `cardEngaged` with that Id. Other client still had `cardProperties[id].divId`. Server path fine.

WHY actually: `notif_characterFlipped` used WAAPI `fill: 'forwards'` on `transform` for the Y-flip. After finish it cleared `style.transform` but did **not** cancel the Animation objects. WAAPI fill is effect-level — it keeps owning `transform` and overrides CSS `._7sfs-engaged { transform: rotate(90deg) }`. So cardEngaged ran (class added) but the rotate never painted.

Why "other browser": studio/active client often reloads (or never kept the live fill); the spectator/opponent client that watched the flip still had the lingering animation effect.

Fix: keep fill mid-flip for the face swap hold; after flipIn.finished, `flipOut.cancel()` + `flipIn.cancel()` then clear style.transform.

## Maneuver column note for B-side combat-card buff (2026-10-11 later)

User: when `_05GaryFlipped` modifies the combat card, note it in the Maneuver column.

WHY Maneuver (not Combat Card): same So It Begins / Yevgeni / Boar's Guile gap — Combat Card column only stores card ids; `recordDuelRoundColumnNote('maneuver', …)` reuses `duel_round_maneuver` for reload-safe text + `duelRoundColumnNote` notif.

WHY dynamic Parry/Thrust parts: ability grants both; either can be dashed independently. Fixed "+1 Parry and +1 Thrust" would lie when only one applied. Skip note entirely if both dashed.

No translate — CAD prototype.
