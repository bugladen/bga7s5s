<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class _01069 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maxime de Lafayette");
        $this->Image = "01069.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 69;

        $this->initializeFaction("Montaigne");
        $this->Title = clienttranslate("Bloody Socialite");
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->Traits = [
            clienttranslate("Villain"),
            clienttranslate("Sorcerer"),
            clienttranslate("Montaigne"),
        ];

        $this->Text = clienttranslate("<p>Maxime ignores wounds from Sorceries and Sorcerer abilities he performs. (Wound costs are considered paid.)</p><p><b>Sorcerer Action:</b> Discard a card • Put target non-Unique attachment from your discard pile into your hand.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01069(),
        ];
    }

    public function handleEvent(Event $event)
    {
        if (! ($event instanceof EventCharacterWounded))
        {
            parent::handleEvent($event);
            return;
        }

        //Maxime ignores wounds from Sorceries and Sorcerer abilities he performs.
        if ($event->characterId != $this->Id || $event->sourceId == 0)
        {
            parent::handleEvent($event);
            return;
        }

        $source = $event->theah->getCardById($event->sourceId);
        if ($source == null)
        {
            parent::handleEvent($event);
            return;
        }

        $ignoreWounds = false;

        // Sorcery cards: a Sorcery's effect that wounds a character wounds its chosen
        // sorcerer (the performer), so if Maxime is the wound target he's the performer.
        if ($source->hasTrait("Sorcery"))
        {
            $ignoreWounds = true;
        }

        // Sorcerer abilities (ISorcererAbility) on any card: Maxime performs it when
        // he is the CHOSEN_PERFORMER. Covers cases where the source is a Sorcery card,
        // Maxime himself, an attachment on Maxime, or any other host of the ability.
        if (! $ignoreWounds && $event->abilityId != '')
        {
            $ability = $source->getAbilityById($event->abilityId);
            if ($ability instanceof ISorcererAbility)
            {
                $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
                if ($performerId == $this->Id)
                {
                    $ignoreWounds = true;
                }
            }
        }

        if ($ignoreWounds)
        {
            $event->theah->game->notify->all("message", clienttranslate('${character_inject_code} ignores wounds from Sorceries and Sorcerer abilities he performs. ${wounds} wound(s) ignored from ${source_inject_code}.'), [
                "character_inject_code" => $this->getInjectCode(),
                "source_inject_code" => $source->getInjectCode(),
                "wounds" => $event->wounds,
            ]);
        }
        else
        {
            parent::handleEvent($event);
        }
    }

}