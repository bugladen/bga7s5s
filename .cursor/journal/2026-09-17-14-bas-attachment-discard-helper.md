# bas: attachment discard helper sweep

On `bas` branch after the createAttachmentDiscardedFromPlayEvent merge.

## What needed updating
Only **Technique_04013** (Tomas destroy-attachment technique) had an attachment destroy→discard path. It already branched CityAttachment manually — replaced with `createAttachmentDiscardedFromPlayEvent`.

## Left alone (correct as-is)
- **Action_04029** — steal-to-hand via player-discard staging (same as Maneuver_01113). Added WHY comment so nobody "fixes" it to the helper.
- **Action_04040** — unequip → locker, not discard.
- **Action_04cd01b** — unequip → sink to city deck.
- **Action_04015 / Action_04037** — discard uncontrolled city cards already at a location (not equipped attachments).
- **_04008_Silence** — `removeRiskAttachment` already uses the helper via RiskAttachmentTrait.
- **Action_04005** — `unEquipAllAttachments` already uses the helper via Character.php.

## Feelings
bas was mostly clean — only one true destroy site. The steal staging comment on 04029 is the valuable part so the next agent doesn't regress.
