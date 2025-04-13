<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction_01023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01023 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Ambush';
        $this->Image = 'img/cards/7s5s/023.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 23;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            'Brawl',
            'Gang',
        ];

        $this->Reactions = [
            new Reaction_01023()
        ];

    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $reaction) : int
    {
        $discount = parent::getReactionFromHandDiscount($theah, $reaction);

        if ($this->Location == Game::LOCATION_HAND && $reaction->Id == $this->Reactions[0]->Id)
        {
            $challengeLocation = $theah->game->globals->get(GAME::CHOSEN_LOCATION);

            //Get all characters at the location of the challenge
            $characters = $theah->getCharactersAtLocation($challengeLocation, $this->ControllerId);
            //Filter out characters that are owned by the player and have the Brute trait
            $characters = array_filter($characters, fn($character) => $character->OwnerId == $this->ControllerId && in_array('Brute', $character->Traits));
            if (count($characters) > 0)
            {
                $discount += 1;
            }
        }

        return $discount;

    }
}