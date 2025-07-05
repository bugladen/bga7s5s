<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction_01045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01045 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("The Song of Eisen");
        $this->Image = "img/cards/7s5s/045.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 45;

        $this->Faction = "Eisen";
        $this->Initiative = 67;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Prepared",
        ];

        $this->Reactions = [
            new Reaction_01045(),
        ];
    }

    public function getParleyDiscount(Character $performer, bool $parleying) : int
    {
        $discount = parent::getParleyDiscount($performer, $parleying);
        if ($this->Location == Game::LOCATION_PLAYER_HOME && $parleying && $performer->ControllerId == $this->ControllerId && $performer instanceof Leader)
        {
            $discount += 1;
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('<strong>${scheme_name}</strong> now resolves.  
            Reknown will be added to The Forum. 
            ${player_name} will now search the City Deck discard pile for a Mercenary to place on top of the City Deck.'), [
                'i18n' => ['scheme_name'],
                "scheme_name" => $this->Name,
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($event->playerId, Game::LOCATION_CITY_FORUM, 1, $this->Name);
            $event->theah->queueEvent($reknown);

            //Transition to the state where player can choose a mercenary out of the City Deck discard pile
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, '01045');
            $event->theah->queueEvent($transition);
        }
    }
}