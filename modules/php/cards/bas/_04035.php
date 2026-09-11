<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04035 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Meeting of the Minds");
        $this->Image = "04035.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 35;

        $this->initializeFaction("Castille");

        $this->Initiative = 83;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate("Discovery")
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The Grand Bazaar] and [The City Forum].</p>
<p>If you control an <b>Academic</b>, put your non-<b>Revelry</b> risk from your discard or <b>The Locker</b> into your hand.</p>
<hr />
<p><b>Reaction:</b> When a pressure occurs at your performer's location • Add +1 to your total for each of your <b>Academics</b> there.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04035(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;
            $playerId = $event->playerId;
            $controlsAcademic = $this->playerControlsAcademic($event->theah, $playerId);
            $eligibleIds = $controlsAcademic ? $this->getEligibleRiskIds($game, $playerId) : [];

            if ($controlsAcademic && count($eligibleIds) > 0)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown will be added to The Grand Bazaar and The City Forum. ${player_name} will then put a non-Revelry Risk from their discard or The Locker into their hand.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($playerId),
                ]);
            }
            else if ($controlsAcademic)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown will be added to The Grand Bazaar and The City Forum. ${player_name} controls an Academic but has no non-Revelry Risk in their discard or The Locker.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($playerId),
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown will be added to The Grand Bazaar and The City Forum.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                ]);
            }

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);

            $forum = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($forum);

            // WHY: Contingent "If you control an Academic" — skip the pick state when no Academic or no eligible Risk (same discipline as Blood Money's contingent Then).
            if (count($eligibleIds) > 0)
            {
                $transition = EventFactory::createTransitionEvent($playerId, $this->Id, "04035");
                $transition->priority = Event::MEDIUM_PRIORITY;
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04035)
        {
            $playerId = $game->getActivePlayerId();
            $args["ids"] = $this->getEligibleRiskIds($game, $playerId);
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04035)
        {
            $playerId = $game->getActivePlayerId();
            $card = $game->getCardObjectFromDb($id);
            if (! $card)
            {
                throw new UserException($game->translate("Invalid card"));
            }

            if (! $this->isEligibleRisk($card))
            {
                throw new UserException($game->translate("Card must be a non-Revelry Risk."));
            }

            $discardPileName = $game->getPlayerDiscardDeckName($playerId);
            $lockerName = $game->getPlayerLockerName($playerId);

            if ($card->Location == $discardPileName)
            {
                $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $card->Id);
            }
            else if ($card->Location == $lockerName)
            {
                $removeEvent = EventFactory::createCardRemovedFromLockerEvent($playerId, $card->Id);
            }
            else
            {
                throw new UserException($game->translate("Card is not in your discard pile or The Locker."));
            }

            $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
            $game->theah->eventCheck($removeEvent);
            $game->theah->eventCheck($addEvent);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} puts ${risk_inject_code} into their hand.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "risk_inject_code" => $card->getInjectCode(),
            ]);

            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04035)
        {
            $playerId = $game->getActivePlayerId();
            if (count($this->getEligibleRiskIds($game, $playerId)) > 0)
            {
                throw new UserException($game->translate("There is a non-Revelry Risk in your discard pile or The Locker that you must choose."));
            }

            $game->gamestate->nextState("");
        }
    }

    /**
     * @return list<int>
     */
    private function getEligibleRiskIds(Game $game, int $playerId): array
    {
        $ids = [];
        $deck = $game->getGameDeckObject();
        $discardPileName = $game->getPlayerDiscardDeckName($playerId);
        $lockerName = $game->getPlayerLockerName($playerId);

        foreach ($deck->getCardsInLocation($discardPileName) as $row)
        {
            $card = $game->getCardObjectFromDb((int) $row['id']);
            if ($card && $this->isEligibleRisk($card))
            {
                $ids[] = $card->Id;
            }
        }

        foreach ($deck->getCardsInLocation($lockerName) as $row)
        {
            $card = $game->getCardObjectFromDb((int) $row['id']);
            if ($card && $this->isEligibleRisk($card))
            {
                $ids[] = $card->Id;
            }
        }

        return $ids;
    }

    private function isEligibleRisk(Card $card): bool
    {
        return $card instanceof Risk && ! $card->hasTrait("Revelry");
    }

    private function playerControlsAcademic(Theah $theah, int $playerId): bool
    {
        foreach ($theah->getCharactersInPlayByPlayerId($playerId) as $character)
        {
            if ($character->hasTrait("Academic"))
            {
                return true;
            }
        }

        return false;
    }
}
