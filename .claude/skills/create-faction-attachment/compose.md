> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## When the attachment carries multiple shapes

Combine the interfaces:

```php
class _NNNNN extends FactionAttachment implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();
        // ...
        $this->Actions    = [new Action_NNNNN()];
        $this->Reactions  = [new Reaction_NNNNN()];
        $this->Techniques = [new Technique_NNNNN()];
    }
}
```

The framework hydrates each ability separately. No cross-talk needed between them inside the card class.

**Condition + Action on the same card:** `_03065` (Lodestone) stamps a while-equipped condition in the attachment's `handleEvent` (B'') and hosts `Action_03065` for the City Action (C). The Action does not re-implement the restriction — `Character::eventCheck` does. When the City Action sinks self, unequip clears the condition before the (own-ability) move Home fires; that ordering is intentional.

**Opponent-equip + condition + Forced destroy:** `_03066` (Shackles) combines Pattern A (`CanEquipToOpponents` + Finesse-vs-ally gate), Pattern B'' (`SHACKLES_CONDITION` cannot-move), and Forced `EventHighDramaPhaseEnd` destroy. No Action/Reaction class — all on the attachment `handleEvent` / `eventCheck`. Forced unequip clears the condition.
