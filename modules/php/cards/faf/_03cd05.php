<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGambleSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03cd05 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Devil Jonah\'s Bones');
        $this->Image = '03cd05.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;

        $this->CityCardNumber = 5;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Artifact'),
            clienttranslate('Corruption'),
            clienttranslate('Trinket'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> When a character equips this card • Wound them.</p><p>When the equipped character gambles during a duel, reveal an additional card. You may reveal cards from the bottom of the deck instead of the top. If you do, cards sink to the top the of the deck instead of the bottom.</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Forced: When a character equips this card • Wound them.
        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $event->characterId,
                $this->Id,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($woundEvent);
        }

        // When the equipped character gambles during a duel: prompt the top/bottom
        // choice. The +1 reveal comes from getNumberOfGambleCardsToReveal below,
        // computed up-front at gamble time per the convention used by Sarafina /
        // Ivy / Roll the Bones.
        if ($event instanceof EventGambleSetup
            && $this->isAttached()
            && $event->actorId == $this->AttachedToId)
        {
            $character = $this->attachedTo($event->theah);
            $controllerId = $character !== null ? $character->ControllerId : $event->playerId;

            $transition = EventFactory::createTransitionEvent($controllerId, $this->Id, "03cd05");
            $event->theah->queueEvent($transition);
        }
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

        if ($this->isAttached() && $actor->Id == $this->AttachedToId)
        {
            $count += 1;
            $explanations[] = sprintf($theah->game->translate("%s reveals +1 card when Gambling."), $this->getInjectCode());
        }

        return $count;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::DUEL_GAMBLE_SETUP_03CD05)
        {
            if ($id == 2)
            {
                $game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, true);
                $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} will reveal Gamble cards from the bottom of their deck.'), [
                    "card_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($game->getActivePlayerId()),
                ]);
            }
            else
            {
                $game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);
            }

            $game->gamestate->nextState();
        }
    }
}
