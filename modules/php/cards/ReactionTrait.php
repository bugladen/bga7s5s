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

    public function getReactionNames($includeAvailable = false): Array
    {
        $names = [];
        foreach ($this->Reactions as $reaction)
        {
            if ($includeAvailable || $reaction->IsAvailable())
            {
                $names[] = $reaction->Name;
            }
        }
        return $names;
    }

    public function getReactions(): Array
    {
        return $this->Reactions;
    }

    public function addReactionProperties(&$properties)
    {
        $properties['reactions'] = $this->getReactionsArray();
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

    public function getReactionsArray(bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Reactions as $reaction) {
            if ($mustBeAvailable && !$reaction->isAvailable()) {
                continue;
            }
            $array[] = ["id" => $reaction->Id, "name" => $reaction->Name];
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
        $args['discount'] = $game->theah->getReactionFromHandDiscount($reaction);
    }

    public function updateReactionOwnerIds($id)
    {
        foreach ($this->Reactions as $reaction)
            $reaction->setOwnerId($id);
    }
}