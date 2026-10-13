<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05William;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05William extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Syrneth Secrets";
        $this->Image = "05William.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=10 matches printed CAD sequence; class routing uses `_05William`.
        $this->CardNumber = 10;

        $this->initializeFaction("Ussura");

        $this->Initiative = 27;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Discovery",
            "Scholarship",
        ];

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Text = "<p>Add a Renown to [The City Docks] and [The Grand Bazaar].</p>
<p>Then, move your Academic to a City location. If an available Artifact is there, you may equip it to your performer, paying all costs.</p>
<hr />
<p><b>City Action:</b> Engage an <b>Artifact</b> equipped to your performer and target an opposing non-<b>Leader</b> • Move them <b>Home</b>.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05William(),
        ];
    }

    /**
     * @return list<Character>
     */
    public function getEligibleAcademics(Theah $theah, int $playerId): array
    {
        return array_values(array_filter(
            $theah->getCharactersInPlayByPlayerId($playerId),
            fn(Character $character) => $character->hasTrait("Academic")
                && count($this->getValidCityDestinations($theah, $character)) > 0
        ));
    }

    /**
     * @return list<string>
     */
    public function getValidCityDestinations(Theah $theah, Character $character): array
    {
        $destinations = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name != $character->Location)
            {
                $destinations[] = $location->Name;
            }
        }

        return $destinations;
    }

    /**
     * Available Artifacts at a location the Academic can afford to equip.
     *
     * @return list<Attachment>
     */
    public function getEquippableArtifactsAtLocation(Theah $theah, Character $performer, string $location): array
    {
        $game = $theah->game;
        $handWealth = $game->handWealthCount($performer->ControllerId);
        $artifacts = [];

        foreach ($theah->getAvailableAttachmentsAtLocation($location) as $attachment)
        {
            if (! $attachment instanceof Attachment || ! $attachment->hasTrait("Artifact") || $attachment->FakeAttachment)
            {
                continue;
            }

            [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($performer, $attachment);
            if ($hasRestrictions)
            {
                continue;
            }

            [$discount] = $theah->getEquipDiscount($performer, $attachment);
            $cost = $attachment->getWealthCost() - $discount;
            if ($cost < 0)
            {
                $cost = 0;
            }

            if ($handWealth < $cost)
            {
                continue;
            }

            $artifacts[] = $attachment;
        }

        return $artifacts;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;
            $academics = $this->getEligibleAcademics($event->theah, $this->ControllerId);

            if (count($academics) > 0)
            {
                $game->notify->all("message", '${scheme_inject_code} now resolves. Renown will be added to The City Docks and The Grand Bazaar. ${player_name} must then move an Academic to a City location.', [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);
            }
            else
            {
                $game->notify->all("message", '${scheme_inject_code} now resolves. Renown will be added to The City Docks and The Grand Bazaar. ${player_name} has no Academic to move.', [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);
            }

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);

            // WHY: Only enter pick states when a legal Academic+destination exists — "Then" is contingent.
            if (count($academics) > 0)
            {
                $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "05William");
                $transition->priority = Event::MEDIUM_PRIORITY;
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM)
        {
            $args["ids"] = array_map(
                fn(Character $character) => $character->Id,
                $this->getEligibleAcademics($game->theah, $this->ControllerId)
            );
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_2)
        {
            $characterId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);
            $args["characterId"] = $characterId;
            $args["locationIds"] = $character !== null
                ? $this->getValidCityDestinations($game->theah, $character)
                : [];
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_3)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            // WHY: Prefer CHOSEN_LOCATION — destination was locked at move pick. Do not rely
            // solely on performer->Location in case args race the CardMoved update.
            $location = (string)$game->globals->get(Game::CHOSEN_LOCATION, $performer !== null ? $performer->Location : '');
            $args["characterId"] = $performerId;
            $args["ids"] = $performer !== null && $location !== ''
                ? array_map(
                    fn(Attachment $attachment) => $attachment->Id,
                    $this->getEquippableArtifactsAtLocation($game->theah, $performer, $location)
                )
                : [];
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_4)
        {
            $chosenAttachmentId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->getCardObjectFromDb($chosenAttachmentId);

            // WHY: City Artifact stays on the board — client highlights it in-place (equip-from-play
            // idiom), no chooseList copy.
            $args["chosenAttachmentId"] = $chosenAttachmentId;
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args["performerId"] = $performerId;

            $discount = (int)$game->globals->get(Game::DISCOUNT, 0);
            $args["discount"] = $discount;
            if ($chosenAttachment instanceof IWealthCost)
            {
                $args["cost"] = $chosenAttachment->getWealthCost();
            }
            else
            {
                $args["cost"] = 0;
            }
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException("Character not found");
            }

            if ($character->ControllerId != $this->ControllerId)
            {
                throw new UserException("You do not control that character.");
            }

            if (! $character->hasTrait("Academic"))
            {
                throw new UserException("Character must be an Academic.");
            }

            if (count($this->getValidCityDestinations($game->theah, $character)) == 0)
            {
                throw new UserException("That Academic has no valid City destination.");
            }

            $game->globals->set(Game::CHOSEN_CARD, $character->Id);
            $game->gamestate->nextState("academicChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_3)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException("Performer not found");
            }

            $location = (string)$game->globals->get(Game::CHOSEN_LOCATION, $performer->Location);
            $equippable = $this->getEquippableArtifactsAtLocation($game->theah, $performer, $location);
            $equippableIds = array_map(fn(Attachment $attachment) => $attachment->Id, $equippable);
            if (! in_array($id, $equippableIds, true))
            {
                throw new UserException("Attachment is not an available Artifact you can equip there.");
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null)
            {
                throw new UserException("Attachment not found");
            }

            $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);

            $payEvent = EventFactory::createEnteringPayStateEvent(
                $performer->ControllerId,
                $attachment->Id,
                Game::PAY_STATE_EQUIP_ATTACHMENT
            );
            $game->theah->queueEvent($payEvent);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "05William_4");
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("artifactChosen");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_2)
        {
            $characterId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);
            if ($character === null)
            {
                throw new UserException("Character not found");
            }

            $locationName = $ids[0];
            $validLocations = $this->getValidCityDestinations($game->theah, $character);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException("Location is not a valid City destination.");
            }

            $game->notify->all("message", '${scheme_inject_code}: ${player_name} moves ${character_inject_code} to ${location_name}.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
                "location_name" => $locationName,
            ]);

            // WHY: engage=false — printed text says Move only (no Engage).
            $moveEvent = EventFactory::createCardMovingEvent(
                $this->ControllerId,
                $character->Id,
                $character->Location,
                $locationName,
                false,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            // WHY: Pay/equip needs CHOSEN_PERFORMER; planning Academic pick used CHOSEN_CARD.
            $game->globals->set(Game::CHOSEN_PERFORMER, $character->Id);
            $game->globals->set(Game::CHOSEN_LOCATION, $locationName);

            // WHY: Optional equip only when an affordable available Artifact is at the destination.
            $equippable = $this->getEquippableArtifactsAtLocation($game->theah, $character, $locationName);
            if (count($equippable) > 0)
            {
                $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "05William_3");
                // WHY: CardMoving only *queues* CardMoved (both MEDIUM). A MEDIUM equip
                // transition FIFO-ahead of CardMoved left performer->Location stale → empty
                // ids and nothing selectable. LOW runs after CardMoved so the Academic is
                // at the destination before the Artifact pick.
                $transition->priority = Event::LOW_PRIORITY;
                $game->theah->queueEvent($transition);
            }

            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_4)
        {
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($attachment === null || $performer === null)
            {
                throw new UserException("Card not found");
            }

            if (! $attachment->hasTrait("Artifact")
                || $attachment->Location != $performer->Location
                || $attachment->isAttached())
            {
                throw new UserException("Attachment is not an available Artifact at the performer's location.");
            }

            $discount = (int)$game->globals->get(Game::DISCOUNT, 0);
            $explanations = $game->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');
            $cost = $attachment->getWealthCost() - $discount;
            if ($cost < 0)
            {
                $cost = 0;
            }

            $totalWealth = 0;
            $hasWealthCard = false;
            foreach ($ids as $cardId)
            {
                $card = $game->getCardObjectFromDb((int)$cardId);
                if ($card === null)
                {
                    throw new UserException(sprintf("Card %d not found.", $cardId));
                }

                $isWealth = $card->hasTrait("Wealth");
                if ($isWealth)
                {
                    $hasWealthCard = true;
                }
                $totalWealth += $isWealth ? 2 : 1;
            }

            if (! $game->isValidWealthPayment($totalWealth, $cost, $hasWealthCard))
            {
                throw new UserException(sprintf("Cost of Attachment is %d. You selected %d Wealth of cards.", $cost, $totalWealth));
            }

            $game->notify->all("message", '${scheme_inject_code}: ${player_name} equips ${attachment_inject_code} to ${character_inject_code}.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "attachment_inject_code" => $attachment->getInjectCode(),
                "character_inject_code" => $performer->getInjectCode(),
            ]);

            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $performerId);

            $equipEvent = EventFactory::createAttachmentEquippedEvent(
                $this->ControllerId,
                $actualTargetId,
                $attachmentId,
                $discount,
                $cost,
                $asAction = false,
                $explanations,
                false,
                $this->Id
            );
            $game->theah->eventCheck($equipEvent);
            $game->theah->queueEvent($equipEvent);

            foreach ($ids as $cardId)
            {
                $card = $game->getCardObjectFromDb((int)$cardId);
                if ($card === null)
                {
                    continue;
                }
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                    $card->OwnerId,
                    $card->Id,
                    $this->Id,
                    $asPayment = true
                );
                $game->theah->queueEvent($discardEvent);
            }

            $game->gamestate->nextState("attachmentEquipped");
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_3)
        {
            // WHY: "you may" — Pass is always legal; do not throw if Artifacts remain.
            $game->notify->all("message", '${scheme_inject_code}: ${player_name} declines to equip an Artifact.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
            ]);
            $game->gamestate->nextState("pass");
        }
    }
}
