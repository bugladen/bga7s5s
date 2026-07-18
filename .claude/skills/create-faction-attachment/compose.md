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
