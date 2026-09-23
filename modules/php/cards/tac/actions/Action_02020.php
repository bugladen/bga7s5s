<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02020 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Manipulate a Non-Leader Character");
        $this->RequiresPerformerSelected = true;
    }

    private function isEligibleAttachment(?Attachment $attachment): bool
    {
        if ($attachment === null || $attachment->Engaged)
        {
            return false;
        }

        // WHY: printed "Melee Weapon or Eisenfaust attachment" — Panzerhand is Armor+Eisenfaust
        // (not Weapon), so Eisenfaust alone must qualify.
        return ($attachment->hasTrait("Weapon") && $attachment->hasTrait("Melee"))
            || $attachment->hasTrait("Eisenfaust");
    }

    /**
     * @return list<Attachment>
     */
    private function getEligibleAttachments(Theah $theah, Character $performer): array
    {
        $attachments = [];
        foreach ($performer->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($this->isEligibleAttachment($attachment))
            {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        // WHY no Engaged filter: print is "opposing non-Leader" only — not "that is en garde"
        // (contrast B.7 _04027). Already Engaged → auto-wound after attachment cost (Duckfoot).
        return array_values(array_filter(
            $opposing,
            fn(Character $character) => ! $character->hasTrait("Leader")
        ));
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => count($this->getEligibleAttachments($theah, $performer)) > 0
                && count($this->getValidTargets($theah, $performer)) > 0
        ));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        if ($character->hasTrait("Leader"))
        {
            return [false, $game->translate("Character is a Leader")];
        }

        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, $game->translate("Performer not found")];
        }

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("Character is the same controller as the performer")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        return [true, ""];
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02020", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020)
        {
            $owner = $this->getOwningCard($game->theah);
            $args['yield'] = $owner->getInjectCode();

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;
            $args['ids'] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $args['attachments'] = [];
            if ($performer !== null)
            {
                foreach ($this->getEligibleAttachments($game->theah, $performer) as $attachment)
                {
                    $args['attachments'][] = [
                        "id" => $attachment->Id,
                        "name" => $attachment->Name,
                    ];
                }
            }

            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $args['characterId'] = $characterId;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_3)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $args['characterId'] = $game->globals->get(Game::CHOSEN_CARD);
            $args['character'] = $game->theah->getCharacterById($args['characterId']);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_CARD, $character->Id);

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_2)
        {
            $attachment = $game->theah->getAttachmentById($id);

            if ($attachment == null)
            {
                throw new UserException($game->translate("Attachment not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($attachment->AttachedToId != $performer->Id)
            {
                throw new UserException($game->translate("Attachment is not attached to the performer"));
            }

            if (! $this->isEligibleAttachment($attachment))
            {
                throw new UserException($game->translate("Attachment is not a Melee Weapon or Eisenfaust"));
            }

            $owner = $this->getOwningCard($game->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $attachment->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);

            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            // WHY already Engaged → auto-wound: print has no en-garde target filter
            // (unlike B.7 _04027). Mirror Duckfoot _01049 / Point of Order _04049 —
            // "if they do not" is automatic when they cannot engage.
            if ($character->Engaged)
            {
                $game->notify->all("message", clienttranslate('${owner_inject_code}: ${character_inject_code} is already Engaged and is wounded.'), [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }
            else
            {
                $transitionEvent = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "02020_2", $this->Id);
                $game->theah->queueEvent($transitionEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("attachmentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_3)
        {
            if ($id != 1 && $id != 2)
            {
                throw new UserException($game->translate("Invalid action"));
            }

            $owner = $this->getOwningCard($game->theah);
            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            // Engage
            if ($id == 1)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }

            // Wound
            if ($id == 2)
            {
                $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} declined to engage ${character_inject_code}'), [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($character->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }

            $game->gamestate->nextState();
        }
    }
}
