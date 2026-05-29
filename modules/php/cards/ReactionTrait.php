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

        $owner = $reaction->getOwningCard($game->theah);
        if ($owner instanceof IWealthCost)
            $args['cost'] = $owner->getWealthCost();
    }

    public function updateReactionOwnerIds($id)
    {
        foreach ($this->Reactions as $reaction)
            $reaction->setOwnerId($id);
    }

    public function addReaction(CardReaction $reaction, Game $game, bool $notify = false): void
    {
        $this->Reactions[] = $reaction;
        $this->IsUpdated = true;

        if ($notify)
        {
            $game->notify->all('reactionAdded', clienttranslate('${character_inject_code} has gained Reaction: ${reaction_name}.'), [
                'i18n' => ['reaction_name'],
                'character_inject_code' => $this->getInjectCode(),
                'characterId' => $this->Id,
                'reaction' => $reaction->getPropertyArray($game),
                'reaction_name' => $reaction->Name,
            ]);
        }
    }

    public function removeReaction(CardReaction $reaction, Game $game, bool $notify = false): void
    {
        $this->Reactions = array_values(array_filter($this->Reactions, fn($r) => $r->Id != $reaction->Id));
        $this->IsUpdated = true;

        if ($notify)
        {
            $game->notify->all('reactionRemoved', clienttranslate('${character_inject_code} has lost Reaction: ${reaction_name}.'), [
                'i18n' => ['reaction_name'],
                'character_inject_code' => $this->getInjectCode(),
                'characterId' => $this->Id,
                'reactionId' => $reaction->Id,
                'reaction_name' => $reaction->Name,
            ]);
        }
    }
}