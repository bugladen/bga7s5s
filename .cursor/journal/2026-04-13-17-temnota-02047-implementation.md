# Temnota (02047) Implementation

## Context
Previous session worked on When Revealed ordering, The Great Game audit, Plans Within Plans audit, Boar's Guile audit, and Leshiye of the Wood audit.

## What Was Done
Implemented Temnota (02047), an Ussura FactionAttachment with two abilities:

1. **Equipped character gains Sorcerer** — handled via EventAttachmentEquipped/EventAttachmentUnequipped in the card class, following the same pattern as _02016 (Cross of the Martyrs) which grants Zealot.

2. **City Action: Target an available attachment at this location → Send it to The Locker. If it was an Artifact, add a Renown to this location.** — Implemented as Action_02047 extending AttachmentAction with IAbilityThatTargetsCards. Uses getAvailableAttachmentsAtLocation() which already returns only unattached city attachments. Checks hasTrait("Artifact") before queueing renown event.

## Files Created/Modified
- `modules/php/cards/tac/_02047.php` — Updated: added IHasActions, ActionTrait, handleEvent for Sorcerer grant/removal, Action_02047 in Actions array
- `modules/php/cards/tac/actions/Action_02047.php` — New: AttachmentAction with city check, targets available attachments, sends to locker, conditional renown
- `modules/php/States/tac/State_highDramaPhase02047.php` — New: state class for attachment selection
- `modules/php/States.php` — Added HIGH_DRAMA_PLAYER_TURN_02047 = 402047
- `states.inc.php` — Added "02047" transition

## Design Decisions

### WHY no self-exclusion filter?
Initially added a filter to exclude Temnota itself from the available targets list. Removed it because Temnota is attached to a character when in play, so `getAvailableAttachmentsAtLocation()` already filters it out (it only returns `!isAttached()` cards). The validation in `actFromActionWithId` still checks `isAttached()` as a safety net.

### WHY AttachmentAction not AttachmentCityAction?
No AttachmentCityAction base class exists. City action semantics are added by checking `cardInCity($owner)` in `isAvailableToPlayer()`, following the pattern of other attachment actions like Action_01073.
