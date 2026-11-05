# Ending Threat 0 vs Wounds 1 after Tip the Scales / Ren

User: Dandy (05Dandy) vs Ren (01121), round 1 start threat 3. Tip the Scales → Ren -1 Parry on card (2→1) → Maneuver +1 Parry. Log: Claude threat 3→1. Table: Ending Threat 0, Wounds 1. Thinks Ending Threat wrong.

## Verdict

Not a calc bug. Mid-round: `updateRoundWithCombatStats` sets ending_* and wounds_taken to the same actor leftover (here 1). Log "3 to 1" is maneuver mode's before=starting, after=recomputed ending.

At `stDuelEndOfRound`, actor leftover threat is cashed into CharacterBeingWounded, then that side's `ending_*_threat` is SET TO 0 (so it does not carry into the next round). `wounds_taken` is left alone as the round's wound preview/record.

So after round resolve / on refresh: Ending Threat 0 + Wounds 1 is expected. Wounds answers "what did the actor take"; Ending Threat answers "what's left in the pool to carry" (actor side cleared).

## User follow-up

Looking at round 1 after the duel progressed. Asks: bug? Shouldn't ending threat and wounds match?

Answer: not a bug. They matched mid-round at 1. Post-resolve they diverge by design — ending_* is carry-forward pool (actor cashed out → 0); wounds_taken is the cashed amount. Fixing the UX would need a display/history field; do not leave ending_* uncleared or next-round starting threat double-counts.
