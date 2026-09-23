# Shoddy Craftsmanship 01174 — attachment button labels

## Ask
When choosing which non-Unique attachment to destroy, buttons only showed attachment Name. Duplicate names in play (common for faction weapons) made the list ambiguous. User wants player name + equipped character name on each button.

## Change
`Action_01174::getArgsFromAction` — label becomes `%s (%s's %s)` via `attachedTo()` + `getPlayerNameById($character->ControllerId)`. Fallback to bare Name if somehow unattached. JS already renders `attachment.name`; no client change.

## Why this shape
Same disambiguation idea as Fine Addition `Action_04029` (`Name (host)`) and Kalla `Reaction_03cd18` (`Name (character)`), but user explicitly asked for **both** player and character. Used character ControllerId (who's running the host), not attachment ControllerId — same person in normal play, clearer wording for "equipped to".

## Unfinished / not touched
Did not broaden this pattern to other global attachment button pickers (only 01174 asked). FakeAttachment still not filtered here (pre-existing).
