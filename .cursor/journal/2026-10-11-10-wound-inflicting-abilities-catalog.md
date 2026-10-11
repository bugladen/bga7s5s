# Wound-inflicting abilities catalog

User asked what abilities inflict wounds on other cards. Quick findings:

- Wounds ONLY go on Characters via `EventFactory::createCharacterBeingWoundedEvent`. No attachment/scheme wound API.
- Self-wound costs are common; "other cards" interpreted as OTHER characters (not self-cost).
- System wounds: challenge reject threat conversion + duel EoR leftover threat in StatesTrait (empty abilityId).
- Full OTHER/SELF catalog produced via explore subagent this session — see that response if we need the tables again.
- Context: Gary/Rena flip just landed; question may be related to what can trigger Gary's redirect / Cooper ignore, but user asked broadly.
- Subagent left underscore scratch files in `.cursor/journal/` (`_extract_wounds.php`, `_all_wound_callers.txt`, etc.). Deleted them after user called it out. Don't leave temp work files in journal.
