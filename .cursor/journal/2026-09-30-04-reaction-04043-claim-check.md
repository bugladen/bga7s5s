# Reaction_04043 claim click already re-checks controller

Eddie asked for a final check on the Claim button so a location that is already claimed cannot be claimed again.

## Finding (then change)

That check was already in `performReaction`. Eddie then asked: for already-claimed, throw `UserException` instead of a log message.

## Change

On Claim, if `getControllerForLocation != 0`, throw `UserException` ("{location} is already claimed.") **before** `parent::performReaction`. Engaged / `!canLocationBeClaimedBy` still soft-fail with the log line and close the window.

WHY throw for already-claimed: keeps Claim/Pass open so the player can Pass after another end-of-HD reaction claimed first. WHY before parent: avoid stacking `ReactionActivatedEvent` then relying on rollback. WHY leave Engaged/IW as soft-fail: Eddie only asked for the already-claimed path.
