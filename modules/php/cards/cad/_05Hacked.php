<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardWhenRevealedEffect;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Hacked extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Stolen Schematics";
        $this->Image = "05Hacked.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=24 matches CAD sequence; footer CAD-C is set designation.
        $this->CardNumber = 24;

        $this->initializeFaction("Castille");

        // WHY: Art sun/hat are both 0 — unusual but intentional for this playtest scheme.
        $this->Initiative = 0;
        $this->PanacheModifier = 0;

        // WHY: Art traits are Discovery / Stealth (scaffold had Dar Matushki / Command).
        $this->Traits = [
            "Discovery",
            "Stealth",
        ];

        $this->Text = "<p>When this scheme is revealed, if you control an Academic, this scheme becomes a copy of an opponent's 
scheme in play or in The Locker.</p>
<p>If you do not control an Academic, add a Renown to any location.</p>";

        $this->resetCard();
    }

    public function hasWhenRevealedEffect(): bool
    {
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardWhenRevealedEffect && $event->cardId == $this->Id)
        {
            $game = $event->theah->game;

            if (! $this->playerControlsAcademic($event->theah, $this->ControllerId))
            {
                $game->notify->all("message", '${scheme_inject_code} When Revealed: no Academic controlled — Renown will be offered when this scheme resolves.', [
                    "scheme_inject_code" => $this->getInjectCode(),
                ]);
                return;
            }

            $ids = $this->getEligibleOpponentSchemeIds($event->theah, $this->ControllerId);
            if (count($ids) == 0)
            {
                $game->notify->all("message", '${scheme_inject_code} When Revealed: Academic controlled, but no opponent scheme is in play or in The Locker to copy.', [
                    "scheme_inject_code" => $this->getInjectCode(),
                ]);
                return;
            }

            $game->notify->all("message", '${scheme_inject_code} When Revealed: ${player_name} controls an Academic and must choose an opponent\'s scheme to copy.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
            ]);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "05Hacked");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        // WHY: Renown path only when we did not become a copy (still this class at resolve).
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            if ($this->playerControlsAcademic($event->theah, $this->ControllerId))
            {
                // Academic path already handled at When Revealed (copy or empty notify).
                return;
            }

            $game->notify->all("message", '${scheme_inject_code} now resolves. ${player_name} must choose a city location to place Renown onto.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "05Hacked_2");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_05HACKED)
        {
            $ids = $this->getEligibleOpponentSchemeIds($game->theah, $this->ControllerId);
            $cards = [];
            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                if ($card)
                {
                    $cards[] = $card->getPropertyArray($game);
                }
            }
            $args["ids"] = $ids;
            $args["cards"] = $cards;
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05HACKED)
        {
            $args["locationIds"] = array_map(
                fn($location) => $location->Name,
                array_values($game->theah->getCityLocations())
            );
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_05HACKED)
        {
            $eligible = $this->getEligibleOpponentSchemeIds($game->theah, $this->ControllerId);
            if (! in_array($id, $eligible, true))
            {
                throw new UserException("Must choose an opponent's scheme in play or in The Locker.");
            }

            $source = $game->getCardObjectFromDb($id);
            if (! $source instanceof Scheme)
            {
                throw new UserException("Invalid scheme.");
            }

            $game->notify->all("message", '${scheme_inject_code}: ${player_name} copies ${source_inject_code}.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "source_inject_code" => $source->getInjectCode(),
            ]);

            $this->becomeCopyOf($source, $game->theah);

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05HACKED)
        {
            $location = $ids[0];
            $valid = array_map(fn($loc) => $loc->Name, array_values($game->theah->getCityLocations()));
            if (! in_array($location, $valid, true))
            {
                throw new UserException("Location is not a city location.");
            }

            $game->notify->all("message", '${player_name} has chosen to add a Renown to ${location}.', [
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "location" => $location,
            ]);

            $renownEvent = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                $location,
                1,
                $this->getInjectCode()
            );
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->gamestate->nextState("");
        }
    }

    /**
     * Same-Id class swap to the source scheme so resolve / Actions / Reactions run as that card.
     * WHY: RiskClone-style shells cannot host source transition keys or actFromCard* methods.
     * WHY card_type stays 05Hacked: Unique getCardIdByType for the real source must stay unambiguous;
     * serialize carries the copy class (unlike Gary, where B-side is the permanent identity).
     */
    public function becomeCopyOf(Scheme $source, Theah $theah): Scheme
    {
        $game = $theah->game;
        $sourceType = self::typeFromScheme($source);

        $controllerId = $this->ControllerId;
        $ownerId = $this->OwnerId;
        $location = $this->Location;
        $oldPanacheMod = $this->PanacheModifier;

        $copy = $game->instantiateCard($sourceType, $this->Id);
        if (! ($copy instanceof Scheme))
        {
            return $this;
        }

        $copy->ControllerId = $controllerId;
        $copy->OwnerId = $ownerId;
        $copy->Location = $location;
        // WHY: Printed copy uses the *source card's* Initiative / PanacheModifier (not the shell's 0/0).
        // Instantiate already loads class defaults; assign from the live source so locker/in-play
        // instances stay authoritative if those fields ever diverge from the constructor.
        $copy->Initiative = $source->Initiative;
        $copy->PanacheModifier = $source->PanacheModifier;
        $copy->addCondition(Game::STOLEN_SCHEMATICS_COPY);

        // WHY: Leader already absorbed this scheme's PanacheModifier on EventSchemeCardRevealed.
        $panacheDelta = $copy->PanacheModifier - $oldPanacheMod;
        if ($panacheDelta != 0)
        {
            $leader = $theah->getLeaderByPlayerId($controllerId);
            if ($leader)
            {
                $leader->ModifiedPanache += $panacheDelta;
                $leader->IsUpdated = true;
                $game->notify->all("panacheModified", '${leader_inject_code}: Panache modified to ${panache} by copying ${scheme_inject_code}', [
                    "leader_inject_code" => $leader->getInjectCode(),
                    "panache" => $leader->ModifiedPanache,
                    "scheme_inject_code" => $copy->getInjectCode(),
                    "playerId" => $controllerId,
                    "leader" => $leader->getPropertyArray($game),
                ]);
            }
        }

        $theah->addCardToWorld($copy);
        $copy->IsUpdated = true;
        $game->updateCardObjectInDb($copy);

        // WHY: Reuse characterFlipped — same-Id face swap updates cardProperties + image.
        $game->notify->all("characterFlipped", '${scheme_inject_code} becomes a copy of ${source_inject_code}!', [
            "scheme_inject_code" => $copy->getInjectCode(),
            "source_inject_code" => $source->getInjectCode(),
            "rena_inject_code" => $copy->getInjectCode(),
            "player_name" => $game->getPlayerNameById($controllerId),
            "card" => $copy->getPropertyArray($game),
            "characterId" => $copy->Id,
        ]);

        return $copy;
    }

    /**
     * Restore Stolen Schematics after a copied scheme hits The Locker at Dusk.
     * WHY static: after the swap, handlers live on the source class — EventHub calls this.
     */
    public static function revertStolenSchematicsCopy(Theah $theah, Scheme $copy): void
    {
        if (! $copy->hasCondition(Game::STOLEN_SCHEMATICS_COPY))
        {
            return;
        }

        $game = $theah->game;
        $restored = $game->instantiateCard('05Hacked', $copy->Id);
        if (! ($restored instanceof self))
        {
            return;
        }

        $restored->ControllerId = $copy->ControllerId;
        $restored->OwnerId = $copy->OwnerId;
        $restored->Location = $copy->Location;

        $theah->addCardToWorld($restored);
        $restored->IsUpdated = true;
        $game->updateCardObjectInDb($restored);
    }

    /**
     * @return list<int>
     */
    public function getEligibleOpponentSchemeIds(Theah $theah, int $playerId): array
    {
        $ids = [];
        $game = $theah->game;

        foreach ($theah->getAllCards() as $card)
        {
            if (
                $card instanceof Scheme
                && $card->Id != $this->Id
                && $card->ControllerId != $playerId
                && $card->Location == Game::LOCATION_PLAYER_HOME
            )
            {
                $ids[] = $card->Id;
            }
        }

        foreach ($game->loadPlayersBasicInfos() as $opponentId => $_)
        {
            $opponentId = (int) $opponentId;
            if ($opponentId == $playerId)
            {
                continue;
            }

            // WHY: Locker is not in $theah->cards — use location query (Snow White muster shape).
            foreach ($theah->getCardObjectsAtLocation($game->getPlayerLockerName($opponentId)) as $card)
            {
                if ($card instanceof Scheme && $card->Id != $this->Id)
                {
                    $ids[] = $card->Id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function playerControlsAcademic(Theah $theah, int $playerId): bool
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

    public static function typeFromScheme(Scheme $scheme): string
    {
        $short = substr(get_class($scheme), strrpos(get_class($scheme), '\\') + 1);
        return ltrim($short, '_');
    }
}
