<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04059 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Recruit Available Mercenary (Parley Without Engaging)");
        // WHY: En Garde Action needs a chosen performer; framework sets CHOSEN_PERFORMER before pay.
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getAvailableMercenariesAtLocation(Theah $theah, string $location): array
    {
        $characters = $theah->getCharactersAtLocation($location, $includeUncontrolled = true);
        return array_values(array_filter(
            $characters,
            fn(Character $character) => ! $character->isControlled() && $character->hasTrait("Mercenary")
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
            function (Character $performer) use ($theah) {
                // WHY En Garde heading: precondition, not Engage cost.
                if ($performer->Engaged)
                {
                    return false;
                }

                // WHY bullet-If non-Hero: availability filter (same discipline as A.6 headcount Ifs).
                if ($performer->hasTrait("Hero"))
                {
                    return false;
                }

                if (! $theah->cardInCity($performer))
                {
                    return false;
                }

                return count($this->getAvailableMercenariesAtLocation($theah, $performer->Location)) > 0;
            }
        ));
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

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        if ($performer === null)
        {
            return [false, $game->translate("Performer not found.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        if ($character->isControlled() || ! $character->hasTrait("Mercenary"))
        {
            return [false, $game->translate("Target must be an available Mercenary.")];
        }

        return [true, ""];
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        // WHY: Action-only Risk — combat-card pay has no Maneuver discount channel.
        // Do not invent a Maneuver solely to carry getManeuverFromCombatCardDiscount.
        if ($action->Id == $this->Id)
        {
            if ($performer === null)
            {
                return $discount;
            }

            if ($performer->hasTrait("Merchant") || $performer->hasTrait("Scoundrel"))
            {
                $discount += 1;
                $owner = $this->getOwningCard($theah);
                $explanations[] = sprintf(
                    $theah->game->translate("%s: -1 because your performer is a Merchant or Scoundrel."),
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);

            // WHY: CHOSEN_PERFORMER already set by in-hand Action performer choose before pay.
            $game->globals->set(Game::RECRUIT_TYPE, Game::SILVER_TONGUE_RECRUIT_TYPE);

            // WHY: Enter PARLEYABLE so Negotiable mercs still offer Parley Yes/No.
            // Cirilo skips to choose-merc because he already Engaged as Action cost.
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04059", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent not called because it merges into the recruit action
        }
    }
}
