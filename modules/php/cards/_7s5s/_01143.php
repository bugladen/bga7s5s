<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01143;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01143 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Contempt and Hatred");
        $this->Image = "01143.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 143;

        $this->Initiative = 43;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Demoralize"), 
            clienttranslate("Duress"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The City Forum]. Then, you may add another Renown to any location. If you do, discard all City Cards there.</p><hr><p>All Mercenaries have -1 [Influence].</p><p><b>City Action:</b> Engage your performer • Pressure with [Influence]. You succeed even if tied. If successful, claim the location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01143(),
        ];
    }

    // WHY: Continuous aura only while the scheme is the day's revealed scheme at Home.
    // Once EventCardSentToLocker moves us to Locker-*, Location is no longer Home and
    // we must not keep debuffing recruits (or leave stale stamps on anyone).
    private function isSchemeInPlay(): bool
    {
        return $this->Location == Game::LOCATION_PLAYER_HOME;
    }

    private function applyMercenaryAura(Theah $theah, Character $mercenary, int $playerId): void
    {
        if ($mercenary->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION))
        {
            return;
        }

        if ($theah->game->characterIsInDiscardOrLocker($mercenary))
        {
            return;
        }

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $playerId,
            $mercenary->Id,
            $mercenary->ModifiedInfluence,
            $mercenary->ModifiedInfluence - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($modifiedEvent);

        $mercenary->addCondition(Game::CONTEMPT_AND_HATRED_CONDITION);
        $theah->game->updateCardObjectInDb($mercenary);

        $theah->game->notify->all("contemptAndHatredConditionStarted", '', [
            "cardId" => $mercenary->Id,
        ]);
    }

    private function removeMercenaryAura(Theah $theah, Character $mercenary): void
    {
        if (! $mercenary->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION))
        {
            return;
        }

        if ($theah->game->characterIsInDiscardOrLocker($mercenary))
        {
            // WHY: Locker rows are not in $theah->cards, so a queued InfluenceModified
            // event's IsUpdated flush would miss them. Write the +1 directly so a later
            // Muster does not inherit the scheme's -1 after we are gone.
            $mercenary->ModifiedInfluence = $mercenary->ModifiedInfluence + 1;
        }
        else
        {
            $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                $this->ControllerId,
                $mercenary->Id,
                $mercenary->ModifiedInfluence,
                $mercenary->ModifiedInfluence + 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($modifiedEvent);
        }

        $mercenary->removeCondition(Game::CONTEMPT_AND_HATRED_CONDITION);
        $theah->game->updateCardObjectInDb($mercenary);

        $theah->game->notify->all("contemptAndHatredConditionEnded", '', [
            "cardId" => $mercenary->Id,
        ]);
    }

    private function clearAuraFromAllAffected(Theah $theah): void
    {
        foreach ($theah->getCharactersInPlay() as $character)
        {
            $this->removeMercenaryAura($theah, $character);
        }

        // WHY: buildCity() does not load Locker piles. Spend-to-Locker characters keep
        // their serialized Conditions; strip the stamp so a later Muster does not look
        // like the aura is still active after this scheme is gone.
        foreach ($theah->game->loadPlayersBasicInfos() as $playerId => $player)
        {
            $lockerName = $theah->game->getPlayerLockerName($playerId);
            foreach ($theah->getCardObjectsAtLocation($lockerName) as $card)
            {
                if ($card instanceof Character)
                {
                    $this->removeMercenaryAura($theah, $card);
                }
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $mercenaries = $event->theah->getCharactersInPlay();
            $mercenaries = array_filter($mercenaries, fn($character) => $character->hasTrait("Mercenary"));
            foreach ($mercenaries as $mercenary)
            {
                $this->applyMercenaryAura($event->theah, $mercenary, $this->ControllerId);
            }

            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.  Renown will be added to The City Forum.
            Then ${player_name} may choose a city location to place Renown onto. If they do, all City Cards will be discarded from that location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $reknown = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            //Transition to the state where player can choose any location.
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01143");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->clearAuraFromAllAffected($event->theah);
        }

        // WHY: isSchemeInPlay — after dusk moves us to Locker-* we can still sit in
        // $this->cards for the rest of that request; without this gate we would keep
        // stamping recruits while already in The Locker.
        if ($event instanceof EventCharacterRecruited && $this->isSchemeInPlay())
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->hasTrait("Mercenary"))
            {
                $this->applyMercenaryAura($event->theah, $character, $event->playerId);
            }
        }
    }
    
    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state === States::PLANNING_PHASE_RESOLVE_SCHEMES_01143) 
        {
            $location = $ids[0];
            $playerId = $game->getActivePlayerId();
    
            $event = EventFactory::createRenownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            //Get all cards in the chosen location
            $game->theah->buildCity();
            $cards = $game->theah->getCardObjectsAtLocation($location);
            foreach ($cards as $card)
            {
                //Discard all city cards
                if ($card instanceof ICityDeckCard)
                {
                    $discard = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $card->Id, $location, $this->Id, $asEffect = true);
                    $game->theah->queueEvent($discard);
                }
            }
    
            $game->gamestate->nextState("");
        }
    }
}
