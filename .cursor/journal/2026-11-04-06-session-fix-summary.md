# Session fix summary (for user ask)

Shipped this session (not proven as the table's NoD-vs-02029 cause):

1. De-dupe boundary: `DB::areTransitionEventsOfTypeForPlayerQueued` + `TestTheah` — `Reaction_01109` no longer matches queued `Reaction_01109v2`.
2. Regression: original still offers beside a queued v2 transition (02029 Action announce).
3. `Reaction_01090` null-guard on activated-source lookup (LocationAction sourceId=0 crash).

Investigation conclusion: Action+01109 offer path already correct; user had no v2; Used/redraw not a new bug (reshuffle clear already exists).
