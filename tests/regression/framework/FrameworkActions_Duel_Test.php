<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AbilityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelActionsDone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelAttemptGamble;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelPlayerGambled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

/** FrameworkActionsTrait: the duel hub — techniques, gambling, combat cards, maneuvers, ending a round. */
class FrameworkActions_Duel_Test extends TestCase
{
    public function name(): string
    {
        return 'FrameworkActionsTrait: duel';
    }

    /**
     * Player 1's actor and player 2's adversary at the Docks, mid-duel (round 1).
     *
     * @return array{0:FrameworkActionsGame,1:AbilityCharacter,2:GenericCharacter}
     */
    private function duelScene(): array
    {
        $game = new FrameworkActionsGame();
        $actor = new AbilityCharacter('Actor');
        $actor->addTechnique(new Technique_PlusOneParry(), $game, false);
        $game->place($actor, Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $game->place(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $game->theah->duelActor = $actor;
        $game->theah->duelOpponent = $adversary;
        $game->globals->set(Game::IN_DUEL, true);
        $game->globals->set(Game::DUEL_ID, 7);
        $game->globals->set(Game::DUEL_ROUND, 1);
        $game->globals->set(Game::DUEL_TYPE, 0);
        $game->duelRoundRow = ['actor_id' => $actor->Id];
        return [$game, $actor, $adversary];
    }

    /** A character placed in the duel scene's faction deck for player 1. */
    private function deckCard(FrameworkActionsGame $game, string $name): Card
    {
        return $game->place(new GenericCharacter($name), 'Deck-1', 1);
    }

    /** Make the top of the faction deck exactly these cards (what getCardsOnTop... reveals). */
    private function setTop(FrameworkActionsGame $game, array $cards): void
    {
        $game->topFactionCards = array_map(fn(Card $c) => ['id' => $c->Id], $cards);
    }

    private function handCards(FrameworkActionsGame $game, int $count, int $playerId = 1): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $game->place(new GenericCharacter("Hand $i"), Game::LOCATION_HAND, $playerId)->Id;
        }
        return $ids;
    }

    public function tests(): array
    {
        return [
            // ---- Choose technique / Technique chosen ---------------------------------------------------

            'actDuelActionChooseTechnique proceeds when the actor has an available Technique' => function () {
                [$game] = $this->duelScene();

                $game->actDuelActionChooseTechnique();

                Assert::same(['chooseTechnique'], $game->gamestate->transitions, 'transition');
            },

            'actDuelActionChooseTechnique is refused when every Technique is used or gated out' => function () {
                [$game, $actor] = $this->duelScene();

                $actor->getTechniques()[0]->Used = true;
                Assert::throws(fn() => $game->actDuelActionChooseTechnique(), 'No Techniques available for Actor', 'used');

                $actor->getTechniques()[0]->Used = false;
                $game->globals->set(Game::IN_DUEL, false); // Technique_PlusOneParry only works inside a duel
                Assert::throws(fn() => $game->actDuelActionChooseTechnique(), 'No Techniques available for Actor', 'gated');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actDuelTechniqueChosen activates, resolves and prices the Technique against the adversary' => function () {
                [$game, $actor, $adversary] = $this->duelScene();
                $technique = $actor->getTechniques()[0];

                $game->actDuelTechniqueChosen($technique->Id);

                Assert::same($technique->Id, $game->globals->get(Game::CHOSEN_TECHNIQUE), 'chosen technique');
                Assert::true($game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN), 'main technique');
                Assert::same($technique->Id, $game->globals->get(Game::TRANSITION_INTERNAL_ID), 'transition id');
                Assert::same(
                    [EventTechniqueActivated::class, EventResolveTechnique::class, EventDuelCalculateTechniqueValues::class],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'activate, resolve, calculate'
                );
                $activated = $game->queued(EventTechniqueActivated::class)[0];
                Assert::same(1, $activated->playerId, 'player');
                Assert::same($actor->Id, $activated->ownerId, 'owner');
                $resolve = $game->queued(EventResolveTechnique::class)[0];
                Assert::same($actor->Id, $resolve->actorId, 'actor');
                Assert::same($adversary->Id, $resolve->adversaryId, 'adversary');
                Assert::same($technique->Id, $resolve->techniqueId, 'technique');
                $calc = $game->queued(EventDuelCalculateTechniqueValues::class)[0];
                Assert::same($actor->Id, $calc->actorId, 'calc actor');
                Assert::same($adversary->Id, $calc->adversaryId, 'calc adversary');
                Assert::count(3, $game->theah->checkedEvents, 'each event offered to the cards first');
                Assert::same(['techniqueChosen'], $game->gamestate->transitions, 'transition');
            },

            'actDuelTechniqueChosen refuses an unknown Technique or one the actor does not own' => function () {
                [$game, , $adversary] = $this->duelScene();
                $adversary2 = new AbilityCharacter('Their technique holder');
                $adversary2->addTechnique(new Technique_PlusOneParry(), $game, false);
                $game->place($adversary2, Game::LOCATION_CITY_FORUM, 2);

                Assert::throws(fn() => $game->actDuelTechniqueChosen('nope'), 'Technique not found', 'unknown');
                Assert::throws(
                    fn() => $game->actDuelTechniqueChosen($adversary2->getTechniques()[0]->Id),
                    'does not belong to the Actor',
                    "someone else's"
                );
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::false($game->globals->has(Game::CHOSEN_TECHNIQUE), 'nothing recorded');
            },

            'actDuelTechniqueChosen accepts a Technique granted by an attachment on the actor' => function () {
                [$game, $actor] = $this->duelScene();
                $blade = new class('Technique blade') extends GenericAttachment implements IHasTechniques {
                    use TechniqueTrait;
                };
                $blade->addTechnique(new Technique_PlusOneParry(), $game, false);
                $game->place($blade, Game::LOCATION_CITY_DOCKS, 1);
                $actor->Attachments = [$blade->Id];

                $game->actDuelTechniqueChosen($blade->getTechniques()[0]->Id);

                Assert::same(['techniqueChosen'], $game->gamestate->transitions, 'attachment Technique accepted');
            },

            // ---- Daniella's duel option ----------------------------------------------------------------

            'actDuelActionConsiderAdversarySorcerer grants Sorcerer and loops back to the duel hub' => function () {
                $game = new FrameworkActionsGame();
                $daniella = $game->place(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
                $adversary = $game->place(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
                $game->theah->duelActor = $daniella;
                $game->theah->duelOpponent = $adversary;
                $game->globals->set(Game::IN_DUEL, true);

                $game->actDuelActionConsiderAdversarySorcerer();

                Assert::true($adversary->hasTrait('Sorcerer'), 'adversary is a Sorcerer');
                Assert::true(in_array($adversary->Id, $game->updatedCardObjectIds, true), 'adversary persisted');
                Assert::true(in_array($daniella->Id, $game->updatedCardObjectIds, true), 'Daniella persisted');
                Assert::same(['considerAdversarySorcerer'], $game->gamestate->transitions, 'self-loop');
            },

            'actDuelActionConsiderAdversarySorcerer is refused when no such Action is available' => function () {
                [$game] = $this->duelScene(); // nobody here has the Action

                Assert::throws(fn() => $game->actDuelActionConsiderAdversarySorcerer(), 'That Duel Action is not available', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actDuelActionConsiderAdversarySorcerer is refused outside a duel or once the adversary is already a Sorcerer' => function () {
                $game = new FrameworkActionsGame();
                $daniella = $game->place(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
                $adversary = $game->place(new GenericCharacter('Adversary', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);
                $game->theah->duelActor = $daniella;
                $game->theah->duelOpponent = $adversary;

                $game->globals->set(Game::IN_DUEL, false);
                Assert::throws(fn() => $game->actDuelActionConsiderAdversarySorcerer(), 'not available', 'not in a duel');

                $game->globals->set(Game::IN_DUEL, true);
                Assert::throws(fn() => $game->actDuelActionConsiderAdversarySorcerer(), 'not available', 'already a Sorcerer');
            },

            // ---- Gamble prompt -------------------------------------------------------------------------

            'actDuelActionGamble prepares a normal gamble revealing two cards' => function () {
                [$game] = $this->duelScene();
                $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_FREE); // stale

                $game->actDuelActionGamble();

                Assert::same(Game::GAMBLE_TYPE_NORMAL, $game->globals->get(Game::GAMBLE_TYPE), 'normal gamble');
                Assert::same(2, $game->globals->get(Game::GAMBLE_REVEAL_COUNT), 'two cards');
                Assert::same('', $game->globals->get(Game::GAMBLE_REVEAL_EXPLANATIONS), 'no explanations');
                Assert::count(1, $game->theah->checkedEvents, 'gamble attempt offered to the cards');
                Assert::count(0, $game->notify->messages, 'no private explanation without modifiers');
                Assert::same(['chooseGambleCard'], $game->gamestate->transitions, 'transition');
            },

            'actDuelActionGamble applies reveal-count modifiers and explains them privately to the actor' => function () {
                [$game, $actor] = $this->duelScene();
                $game->place(new class('Oracle') extends GenericCharacter {
                    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
                    {
                        $explanations[] = 'Oracle: +1';
                        return 1;
                    }
                }, Game::LOCATION_CITY_FORUM, 1);

                $game->actDuelActionGamble();

                Assert::same(3, $game->globals->get(Game::GAMBLE_REVEAL_COUNT), 'two plus one');
                Assert::same('Oracle: +1', $game->globals->get(Game::GAMBLE_REVEAL_EXPLANATIONS), 'explanations');
                Assert::same('player:1', $game->notify->messages[0]['channel'], 'private to the actor');
                Assert::same('Oracle: +1', $game->notify->messages[0]['args']['explanations'], 'explanations shown');
            },

            'actDuelActionGamble floors the reveal count at zero' => function () {
                [$game] = $this->duelScene();
                $game->place(new class('Fog') extends GenericCharacter {
                    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
                    {
                        return -5;
                    }
                }, Game::LOCATION_CITY_FORUM, 2);

                $game->actDuelActionGamble();

                Assert::same(0, $game->globals->get(Game::GAMBLE_REVEAL_COUNT), 'floored');
            },

            'actDuelActionGamble: a card that forbids gambling stops it' => function () {
                [$game] = $this->duelScene();
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventDuelAttemptGamble) {
                        throw new \Bga\GameFramework\UserException('Mysta prevents gambling');
                    }
                };

                Assert::throws(fn() => $game->actDuelActionGamble(), 'Mysta prevents gambling', 'vetoed');
                Assert::false($game->globals->has(Game::GAMBLE_REVEAL_COUNT), 'nothing recorded');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            // ---- Combat card from hand -----------------------------------------------------------------

            'actDuelActionChooseCombatCard plays the card to the dueling line and queues its stats' => function () {
                [$game] = $this->duelScene();
                $card = $game->place(new GenericRisk('Flourish'), Game::LOCATION_HAND, 1);

                $game->actDuelActionChooseCombatCard((string) $card->Id);

                Assert::same(Game::LOCATION_DUELING_LINE, $card->Location, 'in the dueling line');
                Assert::same($card->Id, $game->globals->get(Game::CHOSEN_CARD), 'chosen card');
                Assert::same($card->Id, $game->globals->get(Game::DUEL_PENDING_MANEUVER_CARD), 'pending maneuver card');
                Assert::same(
                    [EventCombatCardAnnounced::class, EventTransition::class],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'announce, then stats'
                );
                Assert::same($card->Id, $game->queued(EventCombatCardAnnounced::class)[0]->cardId, 'announced card');
                $transition = $game->queued(EventTransition::class)[0];
                Assert::same('applyCombatCardStats', $transition->transition, 'transition name');
                Assert::same($card->Id, $transition->sourceId, 'source');
                Assert::same(['combatCardChosen'], $game->gamestate->transitions, 'transition');
            },

            'actDuelActionChooseCombatCard refuses an unknown card, an opponent card, or one not in hand' => function () {
                [$game] = $this->duelScene();
                $theirs = $game->place(new GenericRisk('Theirs'), Game::LOCATION_HAND, 2);
                $table = $game->place(new GenericRisk('On the table'), Game::LOCATION_CITY_DOCKS, 1);

                Assert::throws(fn() => $game->actDuelActionChooseCombatCard('99999'), 'Card not found', 'unknown');
                Assert::throws(fn() => $game->actDuelActionChooseCombatCard((string) $theirs->Id), 'does not belong to the player', 'opponent card');
                Assert::throws(fn() => $game->actDuelActionChooseCombatCard((string) $table->Id), 'not in your hand', 'not in hand');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::same(Game::LOCATION_HAND, $theirs->Location, 'nothing moved');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            // ---- Maneuver menu -------------------------------------------------------------------------

            'actDuelActionChooseManeuver proceeds when the pending combat card has a Maneuver' => function () {
                [$game] = $this->duelScene();
                $card = new GenericRisk('Flourish');
                $card->addManeuver(new Maneuver_PlusOneParry(), $game, false);
                $game->place($card, Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);

                $game->actDuelActionChooseManeuver();

                Assert::same(['chooseManeuver'], $game->gamestate->transitions, 'transition');
            },

            'actDuelActionChooseManeuver refuses when there is no pending card, it vanished, or it has no Maneuver' => function () {
                [$game] = $this->duelScene();

                Assert::throws(fn() => $game->actDuelActionChooseManeuver(), 'No combat card Maneuver is available', 'nothing pending');

                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, 99999);
                Assert::throws(fn() => $game->actDuelActionChooseManeuver(), 'Combat card not found', 'vanished');

                $plain = $game->place(new GenericRisk('Plain Flourish'), Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $plain->Id);
                Assert::throws(fn() => $game->actDuelActionChooseManeuver(), 'No Maneuvers available for Plain Flourish', 'no maneuvers');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actDuelUseManeuverFromCombatCard records the Maneuver' => function () {
                [$game] = $this->duelScene();
                $card = new GenericRisk('Flourish');
                $card->addManeuver(new Maneuver_PlusOneParry(), $game, false);
                $game->place($card, Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);
                $maneuver = $card->getManeuvers()[0];

                $game->actDuelUseManeuverFromCombatCard($maneuver->Id);

                Assert::same($maneuver->Id, $game->globals->get(Game::CHOSEN_MANEUVER), 'recorded');
                Assert::same(['maneuverChosen'], $game->gamestate->transitions, 'transition');
            },

            'actDuelUseManeuverFromCombatCard refuses an unknown Maneuver or one from another card' => function () {
                [$game] = $this->duelScene();
                $chosen = $game->place(new GenericRisk('Chosen'), Game::LOCATION_DUELING_LINE, 1);
                $other = new GenericRisk('Other');
                $other->addManeuver(new Maneuver_PlusOneParry(), $game, false);
                $game->place($other, Game::LOCATION_HAND, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $chosen->Id);

                Assert::throws(fn() => $game->actDuelUseManeuverFromCombatCard('nope'), 'Maneuver not found', 'unknown');
                Assert::throws(
                    fn() => $game->actDuelUseManeuverFromCombatCard($other->getManeuvers()[0]->Id),
                    'does not belong to chosen combat card',
                    'another card'
                );
                Assert::false($game->globals->has(Game::CHOSEN_MANEUVER), 'nothing recorded');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            // ---- Maneuver payment ----------------------------------------------------------------------

            'actDuelPayForManeuverFromCombatCard pays the combat card cost and records the Maneuver' => function () {
                [$game] = $this->duelScene();
                $card = new GenericRisk('Flourish', 2);
                $card->addManeuver(new Maneuver_PlusOneParry(), $game, false);
                $game->place($card, Game::LOCATION_DUELING_LINE, 1);
                $maneuver = $card->getManeuvers()[0];
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);
                $game->globals->set(Game::CHOSEN_MANEUVER, $maneuver->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $pay = $this->handCards($game, 2);

                $game->actDuelPayForManeuverFromCombatCard(json_encode($pay));

                Assert::same($pay, array_map(fn($e) => $e->cardId, $game->queued(EventCardDiscardedFromHand::class)), 'payment cards discarded');
                Assert::same($maneuver->Id, $game->globals->get(Game::DUEL_MANUEVER_ID), 'maneuver recorded');
                Assert::same(['maneuverPaidFor'], $game->gamestate->transitions, 'transition');
            },

            'actDuelPayForManeuverFromCombatCard applies the discount and rejects a wrong payment' => function () {
                [$game] = $this->duelScene();
                $card = new GenericRisk('Flourish', 3);
                $card->addManeuver(new Maneuver_PlusOneParry(), $game, false);
                $game->place($card, Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);
                $game->globals->set(Game::CHOSEN_MANEUVER, $card->getManeuvers()[0]->Id);
                $game->globals->set(Game::DISCOUNT, 1);

                Assert::throws(
                    fn() => $game->actDuelPayForManeuverFromCombatCard(json_encode($this->handCards($game, 1))),
                    'Cost of Card is 2. You selected 1 Wealth',
                    'underpaid after the discount'
                );
                Assert::throws(fn() => $game->actDuelPayForManeuverFromCombatCard(json_encode([99999])), 'Card #99999 not found', 'ghost card');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::false($game->globals->has(Game::DUEL_MANUEVER_ID), 'nothing recorded');

                $game->actDuelPayForManeuverFromCombatCard(json_encode($this->handCards($game, 2)));
                Assert::same(['maneuverPaidFor'], $game->gamestate->transitions, 'exact payment accepted');
            },

            // ---- Gamble card chosen --------------------------------------------------------------------

            'actGambleCardChosen plays the chosen card, sinks the rest, and records the gamble' => function () {
                [$game, $actor, $adversary] = $this->duelScene();
                $chosen = $this->deckCard($game, 'Chosen');
                $other = $this->deckCard($game, 'Other');
                $this->setTop($game, [$chosen, $other]);
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);
                $game->globals->set(Game::GAMBLE_REVEAL_EXPLANATIONS, 'Two as usual.');

                $game->actGambleCardChosen($chosen->Id);

                Assert::same([['id' => $other->Id, 'deck' => 'Deck-1', 'onTop' => false]], $game->deckInserts, 'the other card sinks to the bottom');
                Assert::count(1, array_filter(array_column($game->notify->messages, 'message'), fn($m) => str_contains($m, 'sinks')), 'sink announced');
                Assert::same($chosen->Id, $game->globals->get(Game::CHOSEN_CARD), 'chosen card');
                Assert::true($game->globals->get(Game::DUEL_GAMBLED), 'duel gambled');
                Assert::count(1, $game->queriesMatching('gambled = 1'), 'gamble spent in duel_round');
                Assert::contains('duel_id = 7 AND round = 1', $game->queriesMatching('gambled = 1')[0], 'for this round');
                Assert::same(Game::LOCATION_DUELING_LINE, $chosen->Location, 'in the dueling line');
                Assert::same($chosen->Id, $game->globals->get(Game::DUEL_PENDING_MANEUVER_CARD), 'pending maneuver card');

                Assert::same(
                    [EventCombatCardAnnounced::class, EventDuelPlayerGambled::class],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'announce, then gambled'
                );
                $gambled = $game->queued(EventDuelPlayerGambled::class)[0];
                Assert::same(1, $gambled->playerId, 'player');
                Assert::same($actor->Id, $gambled->actorId, 'actor from the duel round');
                Assert::same($adversary->Id, $gambled->adversaryId, 'adversary');
                Assert::same($chosen->Id, $gambled->chosenCardId, 'chosen card id');
                Assert::same(2, $gambled->revealCount, 'reveal count');
                Assert::same('Two as usual.', $gambled->explanations, 'explanations');
                Assert::same([1], $game->gamestate->activePlayerChanges, 'actor restored as the active player');
                Assert::same(['noManeuver'], $game->gamestate->transitions, 'transition');
            },

            'actGambleCardChosen with a single revealed card sinks nothing' => function () {
                [$game] = $this->duelScene();
                $only = $this->deckCard($game, 'Only');
                $this->setTop($game, [$only]);
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);

                $game->actGambleCardChosen($only->Id);

                Assert::same([], $game->deckInserts, 'nothing sunk');
                Assert::count(0, array_filter(array_column($game->notify->messages, 'message'), fn($m) => str_contains($m, 'sinks')), 'no announcement');
            },

            'actGambleCardChosen reveals from the bottom and sinks the rest to the top (Jonah\'s Bones)' => function () {
                [$game] = $this->duelScene();
                $chosen = $this->deckCard($game, 'Chosen');
                $other = $this->deckCard($game, 'Other');
                $game->bottomFactionCards = [['id' => $chosen->Id], ['id' => $other->Id]];
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);
                $game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, true);

                $game->actGambleCardChosen($chosen->Id);

                Assert::same([['id' => $other->Id, 'deck' => 'Deck-1', 'onTop' => true]], $game->deckInserts, 'the other card goes to the top');
                Assert::count(1, array_filter(array_column($game->notify->messages, 'message'), fn($m) => str_contains($m, 'top of their faction deck')), 'announced');
            },

            'actGambleCardChosen refuses a card that is unknown, outside the faction deck, or not among the revealed cards' => function () {
                [$game] = $this->duelScene();
                $chosen = $this->deckCard($game, 'Chosen');
                $other = $this->deckCard($game, 'Other');
                $third = $this->deckCard($game, 'Third');
                $this->setTop($game, [$chosen, $other, $third]);
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);
                $inHand = $game->place(new GenericCharacter('In hand'), Game::LOCATION_HAND, 1);

                Assert::throws(fn() => $game->actGambleCardChosen(99999), 'Card not found', 'unknown');
                Assert::throws(fn() => $game->actGambleCardChosen($inHand->Id), 'not in your faction deck', 'not in the deck');
                Assert::throws(fn() => $game->actGambleCardChosen($third->Id), 'not in the top 2 cards', 'too deep');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::same([], $game->deckInserts, 'nothing sunk');
                Assert::same([], $game->gamestate->transitions, 'no transition');

                $game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, true);
                $game->bottomFactionCards = [['id' => $other->Id]];
                Assert::throws(fn() => $game->actGambleCardChosen($chosen->Id), 'not in the bottom 2 cards', 'bottom wording');
            },

            'actGambleCardChosen trusts the deck row over a stale serialized location and repairs the card' => function () {
                [$game] = $this->duelScene();
                $chosen = $this->deckCard($game, 'Chosen');
                $chosen->Location = Game::LOCATION_HAND; // lagging serialized copy
                $game->deckRowLocations[$chosen->Id] = 'Deck-1';
                $this->setTop($game, [$chosen]);
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);

                $game->actGambleCardChosen($chosen->Id);

                Assert::true(in_array($chosen->Id, $game->updatedCardObjectIds, true), 'repaired card persisted');
                Assert::same(['noManeuver'], $game->gamestate->transitions, 'gamble accepted');
            },

            'actGambleCardChosen always uses the actor\'s deck, even if another player is active (Proper Drama)' => function () {
                [$game, $actor] = $this->duelScene();
                $game->activePlayerId = 2;
                $chosen = $this->deckCard($game, 'Chosen');
                $this->setTop($game, [$chosen]);
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);

                $game->actGambleCardChosen($chosen->Id);

                Assert::same(1, $game->queued(EventDuelPlayerGambled::class)[0]->playerId, "the actor's controller gambles");
                Assert::same([1], $game->gamestate->activePlayerChanges, 'active player handed back to the actor');
                Assert::same(Game::LOCATION_DUELING_LINE, $chosen->Location, 'card played');
            },

            'actGambleCardChosen: a free gamble does not spend a gamble in duel_round' => function () {
                [$game] = $this->duelScene();
                $chosen = $this->deckCard($game, 'Chosen');
                $this->setTop($game, [$chosen]);
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);
                $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_FREE);

                $game->actGambleCardChosen($chosen->Id);

                Assert::count(0, $game->queriesMatching('gambled = 1'), 'no duel_round write');
                Assert::true($game->globals->get(Game::DUEL_GAMBLED), 'gambling effects still apply');
                Assert::same(Game::LOCATION_DUELING_LINE, $chosen->Location, 'card still played');
            },

            'actGambleCardChosen: Roll the Bones sinks the chosen card instead of playing it' => function () {
                [$game, $actor, $adversary] = $this->duelScene();
                $bones = $game->place(new GenericRisk('Roll the Bones'), Game::LOCATION_DUELING_LINE, 1);
                $chosen = $this->deckCard($game, 'Chosen');
                $other = $this->deckCard($game, 'Other');
                $this->setTop($game, [$chosen, $other]);
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);
                $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
                $game->globals->set(Game::ROLL_THE_BONES_CARD_ID, $bones->Id);

                $game->actGambleCardChosen($chosen->Id);

                Assert::same('Deck-1', $chosen->Location, 'chosen card stays in the deck');
                Assert::same(
                    [['id' => $other->Id, 'deck' => 'Deck-1', 'onTop' => false], ['id' => $chosen->Id, 'deck' => 'Deck-1', 'onTop' => false]],
                    $game->deckInserts,
                    'both revealed cards sink'
                );
                Assert::count(0, $game->queued(EventCombatCardAnnounced::class), 'not announced as a combat card');
                Assert::count(1, $game->queued(EventDuelPlayerGambled::class), 'gamble still reported');
                Assert::count(0, $game->queriesMatching('gambled = 1'), 'no duel_round write');
                Assert::false($game->globals->has(Game::DUEL_PENDING_MANEUVER_CARD), 'its Maneuvers are never offered');
                Assert::same([1], $game->gamestate->activePlayerChanges, 'actor restored as the active player');
                Assert::same(['noManeuver'], $game->gamestate->transitions, 'transition');
            },

            'actGambleCardChosen: a card that forbids gambling stops it before anything moves' => function () {
                [$game] = $this->duelScene();
                $chosen = $this->deckCard($game, 'Chosen');
                $this->setTop($game, [$chosen]);
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventDuelAttemptGamble) {
                        throw new \Bga\GameFramework\UserException('Proper Drama forbids gambling');
                    }
                };

                Assert::throws(fn() => $game->actGambleCardChosen($chosen->Id), 'Proper Drama forbids gambling', 'vetoed');
                Assert::same('Deck-1', $chosen->Location, 'card untouched');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
            },

            // ---- End of round --------------------------------------------------------------------------

            'actDuelDoneRound queues DuelActionsDone and moves on once a combat card is played' => function () {
                [$game, $actor, $adversary] = $this->duelScene();
                $game->combatCardCount = 1;
                $this->handCards($game, 2);

                $game->actDuelDoneRound();

                $done = $game->queued(EventDuelActionsDone::class);
                Assert::count(1, $done, 'DuelActionsDone queued');
                Assert::same(1, $done[0]->playerId, 'player');
                Assert::same($actor->Id, $done[0]->actorId, 'actor');
                Assert::same($adversary->Id, $done[0]->adversaryId, 'adversary');
                Assert::contains('ends the round', $game->notify->messages[0]['message'], 'announced');
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'transition');
            },

            'actDuelDoneRound in round one demands a combat card when the actor has cards in hand' => function () {
                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 0; // cannot gamble, but can still play from hand
                $this->handCards($game, 1);

                Assert::throws(fn() => $game->actDuelDoneRound(), 'you must either gamble or a combat card must be played', 'hand card available');
                Assert::count(0, $game->queued(EventDuelActionsDone::class), 'round not ended');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actDuelDoneRound in round one demands a combat card when the actor can still gamble' => function () {
                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 2;
                $game->gamblesCount = 1; // one gamble left

                Assert::throws(fn() => $game->actDuelDoneRound(), 'you must either gamble or a combat card must be played', 'gamble available');
            },

            'actDuelDoneRound in round one lets a stuck actor end the round' => function () {
                // empty hand, no Finesse left: nothing can produce a combat card
                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 1;
                $game->gamblesCount = 1;

                $game->actDuelDoneRound();
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'out of gambles');

                // empty hand, Finesse left, but a card forbids gambling
                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 2;
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventDuelAttemptGamble) {
                        throw new \Bga\GameFramework\UserException('No gambling');
                    }
                };
                $game->actDuelDoneRound();
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'gamble forbidden by a card');

                // same, when the card signals the veto with the global exception class
                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 2;
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventDuelAttemptGamble) {
                        throw new \BgaUserException('No gambling');
                    }
                };
                $game->actDuelDoneRound();
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'gamble forbidden via BgaUserException');

                // empty hand, Finesse left, but the reveal count was reduced to zero
                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 2;
                $game->place(new class('Fog') extends GenericCharacter {
                    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
                    {
                        return -2;
                    }
                }, Game::LOCATION_CITY_FORUM, 2);
                $game->actDuelDoneRound();
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'nothing to reveal');
            },

            'actDuelDoneRound does not demand a combat card after round one or in a Vladislav duel' => function () {
                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 2;
                $this->handCards($game, 2);
                $game->globals->set(Game::DUEL_ROUND, 2);
                $game->actDuelDoneRound();
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'round two');

                [$game, $actor] = $this->duelScene();
                $actor->ModifiedFinesse = 2;
                $this->handCards($game, 2);
                $game->globals->set(Game::DUEL_TYPE, Game::VLADISLAV_DUEL_TYPE);
                $game->actDuelDoneRound();
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'Vladislav duel');
            },

            // ---- Vladislav duel end --------------------------------------------------------------------

            'actDuelEndDuel only works in a Vladislav duel' => function () {
                [$game] = $this->duelScene();

                Assert::throws(fn() => $game->actDuelEndDuel(), 'not type Vladislav duel', 'ordinary duel');
                Assert::same([], $game->gamestate->transitions, 'no transition');

                $game->globals->set(Game::DUEL_TYPE, Game::VLADISLAV_DUEL_TYPE);
                $game->actDuelEndDuel();
                Assert::same(['doneWithRound'], $game->gamestate->transitions, 'Vladislav duel may end');
            },
        ];
    }
}
