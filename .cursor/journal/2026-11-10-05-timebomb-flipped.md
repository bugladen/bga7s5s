# _05TimebombFlipped (The Porcelain Heart) — finish FactionAttachment

## Prior
`2026-11-10-04-timebomb-character.md` finished Klim A-side + flip path. B-side already had canBeMoved + Leader equip (01202). Left open: Forced −1 Parry + permanence (destroy/locker).

## Classification (Text → pattern)
1. cannot be moved / destroyed / sent to Locker → Syrneth Hand `_01204` (`canBeMoved` false + `EventAttachmentUnequipped` veto unless host `IsDying`) + explicit `EventCardSentToLocker` veto (Heart text names Locker; Hand does not)
2. revealed → owner control + equip Leader → already on A-side flip + `getRequiredAttachTargetId` / Equipping eventCheck (Object of Wonder). Not classic Pattern A "May only equip" — no `canAttachTo` (01202 also omits it; redirect handles target)
3. Forced −1 Parry when equipped character's adversary announces combat card → handleEvent on `EventDuelCalculateCombatCardStats` when `adversaryId == AttachedToId`. Mirror Technique_01204 apply / So It Begins / CAD GaryFlipped note path. Not CombatCardAnnounced (that's BoyBand Reaction). Not deferred next-round (that's Syrneth Hand Technique arm).

## WHY dying-host exception
Journal 04 flagged softlock if unequip blocked on Leader death. Hand already solved this: `IsDying` allows unequip so destroy chain can clear attachments. Same here.

## WHY removeParry not floor-at-zero
EventDuelCalculateCombatCardStats comment: negatives must stick so later +Parry techniques net correctly (Syrneth Hand overflow bug).

## WHY Maneuver column note
Combat Card column only stores card ids (So It Begins / 01204 / GaryFlipped). Skip when dashedParry.

## CAD style
Plain strings — no clienttranslate / translate (user: test card).

## Tests
Extended `Card_05Timebomb_Test` — construct, unequip veto/allow dying, locker veto, adversary −1 Parry, own combat card no-op. 11/11 PASS.

## Unfinished elsewhere
Nevermore scheme still has Klim paste text unimplemented.

## OwnerId Leader fix (follow-up)
User: reveal equip must key OwnerId, not ControllerId. Fixed `getRequiredAttachTargetId` + Equipping `eventCheck` to `$this->OwnerId`. WHY: Object of Wonder uses ControllerId/playerId; Porcelain Heart text is "its owner … their Leader" — opponent-controlled Klim / wrong originalTarget must not resolve opponent's Leader. Test: `required attach target is owner leader not original target controller`. Skill pattern-a/checklist/references updated.

## Faction-deck ban (follow-up)
User: cannot add via DeckValidator. Nevermore musters Klim from out of play — A-side already fails non-Brute faction check, but B-side FactionAttachment would be legal. Ban both `05Timebomb` and `05TimebombFlipped` in `validateFactionDeck` (CAD plain string, GaryFlipped/Overcoat shape). FakeGame gained `getCardClassName` for the suite.
