# Crew Cap sink must not fire destroy triggers

## Rule (Eddie)
When over Crew Cap, sink characters to The Locker until at/under cap. That sink is **not** a destroy — "when a character is destroyed" triggers must not fire.

## Bug
`Reaction_CrewCapLimit::performReaction` queued `createCharacterDestroyedEvent`. That fired every destroy Reaction/Forced (Odette, Blood Money, etc.) and used the destroy notify path.

## Fix
1. **`Reaction_CrewCapLimit`**: queue `createCardSentToLockerEvent` instead. Unequip attachments first (unchanged — CardSentToLocker does not detach).
2. **Re-prompt while still over**: after queueing the sink, if `count - 1 > ModifiedCrewCap`, queue another reaction transition. WHY subtract here: locker event is queued, not processed yet. LostBrute can put someone several over in one shot.
3. **Sink buttons**: exclude Brutes (`hasTrait("Brute")`) — they do not count against cap, so sinking one never helps.
4. **`EventHub` CardSentToLocker**: end Indomitable Will **before** `moveCard` while Location is still the city site. WHY: this event has `runEventHubAfterCards=false`, so hub runs first; Character handlers would see Location=Locker and `endEffect` would skip unclaim. Crew-cap and spend-to-locker both use this path.

## Do not
- Recreate the Character in EventHub on CardSentToLocker. Hub-before-cards would wipe Deal-with-the-Devil / other conditions before their Character handlers run. Deal with the Devil still recreates itself in `Character::handleEvent`.
- Change Name Gate — that rule really is destroy.

## Status
php -l clean. Not studio-playtested.
