# chooseNextReaction description copy tweak

## Ask
1. `description`: "...is choosing the order." → "...is choosing the order to release them."
2. `descriptionmyturn`: "...which Reaction resolves next:" →
   "...which Reaction choice is released to its owner next:"

## Done
Bulk replace in `states.inc.php` — 50 of each. No other files had these exact strings.

## Note
Public log in ArgumentsTrait ("choosing the order of reactions") left alone —
different string, different surface.
