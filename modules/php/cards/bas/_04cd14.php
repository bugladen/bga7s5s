<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class _04cd14 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Millstone Rhud');
        $this->Title = clienttranslate('The Gristmill');

        $this->Image = '04cd14.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->CityCardNumber = 14;

        $this->WealthCost = 6;

        $this->Resolve = 6;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Berserker'),
            clienttranslate('Highland Marches')
        ];

        $this->Text = clienttranslate("<p>Negotiable <i>(You may Parley while paying for this card.)</i></p>
<p><b>Forced:</b> After you recruit Millstone • Wound him and an opposing character.</p>
<p><b>Technique:</b> +1[Thrust]</p>
<p><b>Technique:</b> +1[Thrust]</p>");

        $this->resetCard();

        // WHY: Two identical generic +1 Thrust techniques need distinct Ids (Langschwert _01048 pattern).
        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_04cd14_1");
        $this->Techniques[] = $technique;

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_04cd14_2");
        $this->Techniques[] = $technique;
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD14)
        {
            $opposing = $this->getEligibleOpposingCharacters($game);

            $args['sourceId'] = $this->Id;
            $args['ids'] = array_map(fn($character) => $character->Id, $opposing);
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD14)
        {
            $eligibleIds = array_map(
                fn($character) => $character->Id,
                $this->getEligibleOpposingCharacters($game)
            );

            if (! in_array($id, $eligibleIds))
            {
                throw new UserException($game->translate('You must choose an opposing character at Millstone\'s location.'));
            }

            $target = $game->theah->getCharacterById($id);

            $game->notify->all("message", clienttranslate('${card_inject_code}: Forced — ${target_inject_code} is wounded.'), [
                "card_inject_code" => $this->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $target->Id,
                $this->Id,
                1,
                $this->getInjectCode(),
                (string)$this->Id
            );
            $game->theah->queueEvent($woundEvent);

            $game->gamestate->nextState();
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Forced: After you recruit Millstone • Wound him and an opposing character.
        if ($event instanceof EventCharacterRecruited && $event->characterId == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", clienttranslate('${card_inject_code}: Forced — after recruit, Millstone is wounded.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $this->Id,
                $this->Id,
                1,
                $this->getInjectCode(),
                (string)$this->Id
            );
            $event->theah->queueEvent($woundEvent);

            // WHY: Only open the picker when a legal opposing target exists. Forced cannot
            // require an illegal choose; the self-wound still applies.
            $opposing = $this->getEligibleOpposingCharacters($game);
            if (count($opposing) > 0)
            {
                $transition = EventFactory::createTransitionEvent(
                    $this->ControllerId,
                    $this->Id,
                    "04cd14"
                );
                $event->theah->queueEvent($transition);
            }
        }
    }

    /**
     * @return Character[]
     */
    public function getEligibleOpposingCharacters(Game $game): array
    {
        // WHY: Same-location wound-opposing (Sibella / Adelheide). Printed text has no
        // board-wide range; "target" in the stub means choose, not any-in-play.
        return array_values($game->theah->getOpposingCharactersAtLocation(
            $this->Location,
            $this->ControllerId
        ));
    }
}
