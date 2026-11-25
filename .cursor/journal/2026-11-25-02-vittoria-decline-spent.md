# Vittoria Reaction_01014 — Decline marked reaction Used

## Report
Polished Flintlock (`Action_01049`) targeted en garde Vittoria → engage-or-wound → she Engaged → reaction offered (CardEngaged) → she Declined → reaction still marked Used for the day.

## Root cause
`performReaction` decline branches (`inPlayThug` and terminal `inHandThug`) called `setUsed(true)`. Decline is not a use — sibling redirect `Reaction_02016` (Maryam) and UL Pass leave Available. Test `decline hand with no location Thug…` had asserted `Used=true`, locking in the wrong rule.

moveHome decline still setUsed — correct: Thug redirect already happened; optional Home is post-use.

## Fix
1. Drop `setUsed` from both Decline paths; keep releaseEvent + skipNextEvent + clearPhaseFlags.
2. Flip existing decline test to `Used=false`.
3. New test: flintlock CardEngaged offer → Decline → Used false + engage re-released onto Vittoria.

21/21 Reaction_01014 green.

## Not doing
Action_01049 still never fires EventCharacterTargeted at pick time — Vittoria only sees the CardEngaged/BeingWounded aftermath. Separate issue if "when an opponent targets" should open before engage-or-wound.
