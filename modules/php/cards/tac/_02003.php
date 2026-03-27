<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02003;

class _02003 extends Character implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Mourad');
        $this->Image = '02003.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 3;

        $this->initializeFaction('Vodacce');
        $this->Title = clienttranslate('Unfettered Fury');
        $this->Resolve = 6;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate('Scoundrel'),
            clienttranslate('Bodyguard'),
            clienttranslate('Red Hand'),
            clienttranslate('Maghreb')
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer</b> abilities cannot target Mourad.</p><p><b>Reaction:</b> When your <b>Strega</b> at this location is challenged • Mourad may intervene even while engaged.</p><p><b>Technique:</b> If Mourad's combat card is a <b>Sorcery</b> and you control a <b>Strega</b> at his location • Draw a card.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02003(),
        ];

        $this->Techniques = [
            new Technique_02003(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventSorcererAbilityStart && $event->targetId == $this->Id)
        {
            throw new \BgaUserException($event->theah->game->translate('You cannot target Mourad with a Sorcerer Ability.'));
        }
    }
}