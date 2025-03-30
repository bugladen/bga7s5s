<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction;

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
        $reactions = [];
        foreach ($this->Reactions as $reaction)
        {
            if ($reaction->IsAvailable())
            {
                $reactions[] = $reaction;
            }
        }
        return $reactions;
    }

    public function addReactionProperties(&$properties)
    {
        $properties['reactions'] = $this->getReactionsArray();
    }

    public function getReactionById($id): ?Reaction
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

    public function updateReactionOwnerIds($id)
    {
        foreach ($this->Reactions as $reaction)
            $reaction->setOwnerId($id);
    }
}