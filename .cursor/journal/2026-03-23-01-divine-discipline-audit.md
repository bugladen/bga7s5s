# Divine Discipline (_02018) Audit

## What I found

Only one bug: the explanation string in `Maneuver_02018.php` used `$actor->Wounds` instead of `$actor->Wounds + 1`. The thrust calculation itself was correct — it added `$actor->Wounds + 1` to account for the wound that's queued but not yet processed. But the explanation reported one less thrust than what was actually applied.

Fixed by extracting `$thrustBonus = $actor->Wounds + 1` and using it in both the calculation and the explanation.

## WHY the +1 is correct

The event queue order in `stResolveManeuverFromCombatCard` is: ManeuverActivated → ResolveManeuver → DuelCalculateManeuverValues. When ResolveManeuver fires for this card, it queues a wound event. That wound event goes to the END of the queue, behind the already-queued DuelCalculateManeuverValues. So when the calculate event fires, the wound hasn't been applied yet — `$actor->Wounds` is the pre-wound count. The +1 forward-accounts for it.

Card text says "+X where X is the number of wounds on your participant" — sequential interpretation after the bullet means X includes the wound just applied. The +1 achieves this.

## Everything else was correct

- Zealot City Action: performer must be Zealot in city, wounds ALL characters at location (including self). Card text says "all characters" with no exceptions.
- Card stats, traits, faction all match.
