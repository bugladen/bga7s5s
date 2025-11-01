<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

trait ReactionTrait
{
    protected Array $Reactions = [];

    public function anyReactionsAvailable(): bool
    {
        foreach ($this->Reactions as $reaction)
        {
            if ($reaction->IsAvailable())
            {
                return true;
            }
        }
        return false;
    }

    public function getReactionNames(Game $game, $includeAvailable = false): Array
    {
        $names = [];
        foreach ($this->Reactions as $reaction)
        {
            if ($includeAvailable || $reaction->IsAvailable())
            {
                $names[] = $game->translate($reaction->Name);
            }
        }
        return $names;
    }

    public function getReactions(): Array
    {
        return $this->Reactions;
    }

    public function addReactionProperties(Game $game, &$properties)
    {
        $properties['numberofReactions'] = count($this->Reactions);
        $properties['reactions'] = $this->getReactionsArray($game);
    }

    public function getReactionById($id): ?CardReaction
    {
        foreach ($this->Reactions as $reaction)
        {
            if ($reaction->Id == $id)
            {
                return $reaction;
            }
        }
        return null;
    }

    public function getReactionsArray(Game $game, bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Reactions as $reaction) {
            if ($mustBeAvailable && !$reaction->isAvailable()) {
                continue;
            }
            $array[] = $reaction->getPropertyArray($game);
        }

        return $array;
    }

    public function reactionFromCard(Game $game, int $state, string $internalId, string $reactionId): void
    {
        $reaction = $this->getReactionById($internalId);
        $reaction->performReaction($game, $state, $internalId, $reactionId);
    }

    public function updateArgsFromReaction(Game $game, Array &$args, int $state, string $stateName, string $internalId): void
    {
        $reaction = $this->getReactionById($internalId);
        $args['buttons'] = $reaction->getReactionButtonProperties($game->theah);
        $args['descriptionmyturn'] = $reaction->getReactionDescription($game->theah);
    }

    public function updatePayForArgsFromReaction(Game $game, Array &$args, int $state, string $stateName, string $internalId): void
    {
        $reaction = $this->getReactionById($internalId);

        $args['descriptionmyturn'] = $reaction->getReactionPayForDescription($game->theah);
        $args['reactionId'] = $this->Id;

        $args['discount'] = $game->globals->get(Game::DISCOUNT);
    }

    public function updateReactionOwnerIds($id)
    {
        foreach ($this->Reactions as $reaction)
            $reaction->setOwnerId($id);
    }
}