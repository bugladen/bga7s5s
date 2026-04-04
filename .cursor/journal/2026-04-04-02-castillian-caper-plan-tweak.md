# Castillian Caper plan — Part A partial application

Eddie asked to change Part A: **always apply as much effect as possible**. Do **not** skip the planning transition only because there are fewer than two empty locations.

**Updated rule:** Transition to the first pick state only when **zero** locations have `Renown == 0` (nothing to do). If there is **one** empty location, player places one Renown and returns to events. If after the first placement another empty location still exists, go to state 2 for the second Renown.

Plan file updated: `castillian_caper_02035_695a9c9c.plan.md` (user .cursor plans).

## Part B — no Engaged gate

Eddie: card text does not mention engagement. Plan Part B updated to **not** filter performers by `Engaged` (do not mirror Action_01030’s `!Engaged` check for this ability).
