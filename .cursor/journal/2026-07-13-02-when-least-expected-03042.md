# When Least Expected (_03042) — create-scheme

## Card text
1. Resolve: Add Renown to City Docks + City Forums (trivial)
2. City Action: Engage performer • Finesse challenge to opposing character. If performer is a Duelist, refuse only by discarding a card.

## Context
Continuing FAF Castille schemes after Proper Study (_03041).

## Approach / WHY

### Resolve
Mirror `_03029` / `_03041` — fixed dual Renown, no planning sub-state.

### City Action
Mirror Cornered (`Action_03021`) more than Sworn Swords:
- `SchemeCityAction` + `RequiresPerformerSelected` + engage in `EventActionTriggered`
- Mint `WHEN_LEAST_EXPECTED_CHALLENGE_TYPE = 23` kept OUT of `stIssueChallenge` auto-engage list (engage already done; NORMAL would double-engage)
- Transition `"03042"` → `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` (framework target picker — same as 03008/03021)
- `STAT_FINESSE`
- Engagement trichotomy case (a): Engage printed → engage in ActionTriggered + custom type out of auto-engage list

### Discard-to-refuse (novel)
Text: "If your performer is a Duelist, it can only be refused by discarding a card."
- Intervene stays normal (refuse ≠ intervene; Triskelion precedent)
- Non-Duelist performer: free refuse under same CHALLENGE_TYPE
- Duelist + empty hand: cannot refuse (JS disable + server throw) — "by discarding" implies a card must exist
- Duelist + hand nonempty: Refuse → `"03042"` → `highDramaPhase03042` hand pick → discard asEffect + ChallengeRejected → GENERATE_THREAT path

WHY not disable Refuse entirely for Duelists: they CAN refuse, just must pay a card.
WHY sub-state not inline discard in reject: need card picker UX; TRANSITION_SOURCE_ID still points at scheme so `actFromCardWithId` → Action_03042 works.
WHY always mint custom CHALLENGE_TYPE (not only when Duelist): need engage-out-of-auto-list for all performers; Duelist check is only at refuse time.
WHY transition key `"03042"` not `"discardToRefuse"`: Eddie — card-specific keys on ACCEPT_CHALLENGE, same pattern as other card transitions; not a reusable named transition.

## Done
- Scheme + Action_03042 + State_highDramaPhase03042
- Game/States/states.inc + Framework reject + accept args
- JS: Refuse label/disable + faf triple + EventHandlers
- Skill updated with pattern row + `_03042` walkthrough
- Lint clean, CRLF ok
- `_results/03042-when-least-expected.md`

## Skill updates from _03042 session

Captured in create-scheme:
- Pattern G (discard-to-refuse) full section
- Engage-and-challenge same-performer City Action (Cornered shape on schemes)
- GameState transition pitfall: no `""` + `"back"` (Studio "More than one possible transition")
- Card-number keys on ACCEPT_CHALLENGE (`"03042"` not `"discardToRefuse"`)
- Walkthrough Studio bugs + reference table + finish checklist item 18

