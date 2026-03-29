<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02024 extends Scheme
{
    public array $ReknownLocations = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Oath of Vengeance');
        $this->Image = "02024.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 24;

        $this->initializeFaction('Montaigne');
        $this->Initiative = 64;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Hunt'),
            clienttranslate('Oathsworn'),
        ];

        $this->Text = clienttranslate("<p>Add two Renown to this card.</p><hr><p><b>Forced:</b> After your <b>Musketeer</b> destroys their adversary • Collect a Renown from this card.</p><p><b>Forced:</b> At the beginning of Dusk • Move each Renown on this card to different locations.</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.  Two Renown will be added to this card.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $reknown = EventFactory::createReknownAddedToCardEvent($this->ControllerId, $this->Id, 2);
            $event->theah->queueEvent($reknown);
        }

        if ($event instanceof EventCharacterDestroyed && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $inDuel = $event->theah->game->globals->get(Game::IN_DUEL);
            if ($inDuel && $this->Reknown > 0)
            {
                $challengerId = $event->theah->getDuelChallengerId();
                $defenderId = $event->theah->getDuelDefenderId();
                $challenger = $event->theah->getCharacterById($challengerId);
                $defender = $event->theah->getCharacterById($defenderId);

                //Do we have a character in this duel?
                if ($challenger->ControllerId == $this->ControllerId || $defender->ControllerId == $this->ControllerId)
                {
                    $myCharacter = $challenger->ControllerId == $this->ControllerId ? $challenger : $defender;

                    //Has to be a musketeer and not the one that was destroyed
                    if ($myCharacter->hasTrait("Musketeer") && $myCharacter->Id != $event->characterId)
                    {
                        $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name}\'s Musketeer destroyed their adversary.  A Renown will be collected from this card.'), [
                            "scheme_inject_code" => $this->getInjectCode(),
                            "player_name" => $event->theah->game->getPlayerNameById($myCharacter->ControllerId),
                        ]);

                        $cardReknown = EventFactory::createReknownRemovedFromCardEvent($this->ControllerId, $this->Id, 1);
                        $event->theah->queueEvent($cardReknown);

                        $playerReknown = EventFactory::createPlayerGainsReknownEvent($myCharacter->ControllerId, 1);
                        $event->theah->queueEvent($playerReknown);
                    }
                }
            }
        }

        if ($event instanceof EventDuskPhaseBegin && $this->Location == Game::LOCATION_PLAYER_HOME && $this->Reknown > 0)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: At the beginning of Dusk, each Renown on this card will be moved to a different location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            for ($i = 0; $i < $this->Reknown; $i++)
            {
                $transtion = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02024");
                $event->theah->queueEvent($transtion);
            }
        }

        if ($event instanceof EventDuskPhaseEnd)
        {
            $this->ReknownLocations = [];
            $this->IsUpdated = true;
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::DUSK_PHASE_BEGIN_02024)
        {
            $locations = $game->theah->getCityLocations();
            $availableLocations = [];
            foreach ($locations as $location) 
            {
                if (! in_array($location->Name, $this->ReknownLocations)) {
                    $availableLocations[] = $location->Name;
                }
            }
    
            $args["locationIds"] = $availableLocations;
        }

       return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::DUSK_PHASE_BEGIN_02024)
        {
            $location = $game->theah->getCityLocation($ids[0]);
            if ($location == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            if (in_array($location->Name, $this->ReknownLocations))
            {
                throw new UserException($game->translate("Renown has already been moved to Location %s"), $location->Name);
            }

            $event = EventFactory::createReknownRemovedFromCardEvent($this->ControllerId, $this->Id, 1);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location->Name, 1, $this->getInjectCode());
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: Renown has been moved to Location ${location_name}.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "location_name" => $location->Name,
            ]);

            $this->ReknownLocations[] = $location->Name;
            $this->IsUpdated = true;

            $game->gamestate->nextState();
        }
    }
}