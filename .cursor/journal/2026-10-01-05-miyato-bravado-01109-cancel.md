# Miyato/Ota copy Maneuver + Night of Drinking (01109) fatal

## Report

Eddie: Miyato/Ota Technique_02043a to copy Bravado (_04046) Maneuver +1 Parry → server error, copy did not land.

Studio log:
```
Call to undefined method ...\tac\_02043::effectsCannotBeCancelled()
in Reaction_01109.php:141
on EventManeuverActivated
```

## Root cause

`Technique_02043a` clones the performed Maneuver onto Miyato (`_02043` Character) and re-queues `EventManeuverActivated` for the clone. Night of Drinking (`Reaction_01109`) listens for that event, takes `$maneuver->getOwningCard()`, and calls `effectsCannotBeCancelled()` without checking the owner is a Risk.

Action / RiskPlayed paths already gate `$risk instanceof Risk`. ManeuverActivated path did not — assumed every Maneuver lives on a Risk combat card. Character-hosted clones (Miyato, also Katain-hosted copies) break that assumption.

WHY it looked Bravado-specific: any copied Maneuver would hit this if an opponent holds Night of Drinking in hand and is available to react. Bravado was just the repro.

## Fix

`Reaction_01109` ManeuverActivated branch: require `$risk instanceof Risk` (and nullsafe getOwningCard) before trait / `effectsCannotBeCancelled` / offer. Non-Risk owners → do not offer cancel (card text is cancel a Risk).

## Do not regress

Do not add `effectsCannotBeCancelled()` to Character to "make the call safe" — wrong rules shape; cancel should not target Character-hosted clones as Risk cancels.
