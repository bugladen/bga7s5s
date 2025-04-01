<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction_01181;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;

class _01181 extends CityAttachment implements IHasReactions
{
    use ReactionTrait;

    public int $HealTargetId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Sorte Deck';
        $this->Image = "img/cards/7s5s/181.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 181;
        
        $this->CityCardNumber = 5;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Sorte',
            'Trinket',
        ];

        $this->Reactions = [
            new Reaction_01181()
        ];

        $this->HealTargetId = 0;
    }

    public function argsFromCard(Game $game, int $state, string $internalId): array 
    {
        $args = parent::argsFromCard($game, $state, $internalId);

        if ($game->isInReactionState($state))
        {
            $reaction = $this->getReactionById($internalId);
            $args['buttons'] = $reaction->getButtonProperties($game->theah);
            $args['descriptionmyturn'] = $reaction->getStateDescription($game->theah);
        }

        return $args; 
    }

    public function reactionFromCard(Game $game, int $state, string $reaction, string $internalId): void
    {
        parent::reactionFromCard($game, $state, $reaction, $internalId);

        if ($reaction == "pass")
        {
            // Do nothing
        }

        if ($reaction == "heal1Wound")
        {
            $this->healWound($game, 1);
        }

        if ($reaction == "heal2Wounds")
        {
            $this->healWound($game, 2);
        }

        $game->gamestate->nextState("done");
    }

    private function healWound(Game $game, int $wounds): void
    {
        $reaction = $this->Reactions[0];
        $reaction->Used = true;
        $this->IsUpdated = true;

        $owner = $game->theah->getCardById($this->AttachedToId);

        $engageEvent = $game->theah->createEvent(Events::CardEngaged);
        if ($engageEvent instanceof EventCardEngaged)
        {
            $engageEvent->cardId = $this->Id;
            $engageEvent->playerId = $owner->ControllerId;
        }
        $game->theah->queueEvent($engageEvent);

        $event = $game->theah->createEvent(Events::CharacterHealed);
        if ($event instanceof EventCharacterHealed)
        {
            $event->characterId = $this->HealTargetId;
            $event->sourceId = $this->Id;
            $event->wounds = $wounds;
            $event->reason = 'Sorte Deck';
        }
        $game->theah->queueEvent($event);
    }
}

