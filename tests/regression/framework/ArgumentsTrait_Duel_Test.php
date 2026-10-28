<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AbilityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\GameFramework\UserException;

/**
 * ArgumentsTrait: duel hub + combat-card / gamble chooser args.
 *
 * WHY: button availability on argsChooseDuelAction is easy to "simplify" wrong
 * (Vladislav end-only, techniques needing a combat card, gamble needing revealCount > 0).
 */
class ArgumentsTrait_Duel_Test extends TestCase
{
    public function name(): string
    {
        return 'ArgumentsTrait: duel args';
    }

    /**
     * @return array{0:FrameworkActionsGame,1:AbilityCharacter,2:GenericCharacter}
     */
    private function duelScene(): array
    {
        $game = new FrameworkActionsGame();
        $actor = new AbilityCharacter('Actor');
        $actor->Finesse = 2;
        $actor->resetCard();
        $actor->addTechnique(new Technique_PlusOneParry(), $game, false);
        $game->place($actor, Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $game->place(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $game->theah->duelActor = $actor;
        $game->theah->duelOpponent = $adversary;
        $game->globals->set(Game::IN_DUEL, true);
        $game->globals->set(Game::DUEL_ID, 7);
        $game->globals->set(Game::DUEL_ROUND, 1);
        $game->globals->set(Game::DUEL_TYPE, Game::NORMAL_DUEL_TYPE);
        $game->duelRoundRow = [
            'actor_id' => $actor->Id,
            'gambled' => null,
            'round' => 1,
        ];
        $game->gamblesCount = 0;
        $game->combatCardCount = 0;
        $game->playedTechniquesCount = 0;
        return [$game, $actor, $adversary];
    }

    public function tests(): array
    {
        return [
            'argsChooseDuelAction: Vladislav duel type forces End Duel only' => function () {
                [$game] = $this->duelScene();
                $game->globals->set(Game::DUEL_TYPE, Game::VLADISLAV_DUEL_TYPE);
                $game->combatCardCount = 1;

                $active = $game->argsChooseDuelAction()['_private']['active'];
                Assert::false($active['maneuversAvailable'], 'no maneuvers');
                Assert::false($active['techniquesAvailable'], 'no techniques');
                Assert::false($active['gambleAvailable'], 'no gamble');
                Assert::false($active['combatCardAvailable'], 'no combat card');
                Assert::true($active['endDuelAvailable'], 'end duel');
                Assert::same(0, $active['gamblesLeft'], 'gambles cleared');
            },

            'argsChooseDuelAction: combat card available when none played this round' => function () {
                [$game] = $this->duelScene();
                $active = $game->argsChooseDuelAction()['_private']['active'];
                Assert::true($active['combatCardAvailable'], 'combat card');
                Assert::false($active['techniquesAvailable'], 'techniques need a combat card first');
                Assert::true($active['gambleAvailable'], 'gamble with finesse left and reveal>0');
                Assert::same(2, $active['gamblesLeft'], 'ModifiedFinesse - gambles spent');
                Assert::false($active['endDuelAvailable'], 'normal duel');
            },

            'argsChooseDuelAction: techniquesAvailable needs combat card + unused main technique slot' => function () {
                [$game] = $this->duelScene();
                $game->combatCardCount = 1;

                $active = $game->argsChooseDuelAction()['_private']['active'];
                Assert::true($active['techniquesAvailable'], 'techniques open');
                Assert::false($active['combatCardAvailable'], 'combat card already played');
                Assert::false($active['gambleAvailable'], 'gamble blocked once combat card is down');

                $game->playedTechniquesCount = 1;
                Assert::false(
                    $game->argsChooseDuelAction()['_private']['active']['techniquesAvailable'],
                    'main technique already played'
                );
            },

            'argsChooseDuelAction: gamble hidden when already gambled, spent, vetoed, or revealCount is 0' => function () {
                [$game, $actor] = $this->duelScene();

                $game->duelRoundRow['gambled'] = 1;
                Assert::false($game->argsChooseDuelAction()['_private']['active']['gambleAvailable'], 'already gambled');

                $game->duelRoundRow['gambled'] = null;
                $game->gamblesCount = 2; // equals ModifiedFinesse
                Assert::false($game->argsChooseDuelAction()['_private']['active']['gambleAvailable'], 'no gambles left');
                Assert::same(0, $game->argsChooseDuelAction()['_private']['active']['gamblesLeft'], 'zero left');

                $game->gamblesCount = 0;
                $game->theah->eventCheckHook = function () {
                    throw new UserException('No gambling today');
                };
                Assert::false($game->argsChooseDuelAction()['_private']['active']['gambleAvailable'], 'eventCheck veto');
                $game->theah->eventCheckHook = null;

                // WHY: Mirror actDuelDoneRound — a 0-card reveal is not a useful gamble.
                $actor->Finesse = 2;
                $actor->resetCard();
                $reducer = new class('Reducer') extends GenericCharacter {
                    public function getNumberOfGambleCardsToReveal($theah, $actor, &$explanations): int
                    {
                        return -2;
                    }
                };
                $game->place($reducer, Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($game->argsChooseDuelAction()['_private']['active']['gambleAvailable'], 'revealCount 0');
            },

            'argsChooseDuelAction: maneuversAvailable when pending combat card has maneuvers' => function () {
                [$game] = $this->duelScene();
                $card = new GenericRisk('Combat Card');
                $card->addManeuver(new Maneuver_PlusOneParry(), $game, false);
                $game->place($card, Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);

                Assert::true(
                    $game->argsChooseDuelAction()['_private']['active']['maneuversAvailable'],
                    'pending maneuvers'
                );
            },

            'argsChooseDuelTechnique lists the actor techniques' => function () {
                [$game, $actor] = $this->duelScene();
                $args = $game->argsChooseDuelTechnique();
                Assert::count(1, $args['techniques'], 'one technique');
                Assert::same($actor->getTechniques()[0]->Id, $args['techniques'][0]['id'], 'technique id');
            },

            'argsDuelUseManeuverFromCombatCard builds city then lists maneuvers (Back/refresh path)' => function () {
                [$game] = $this->duelScene();
                $card = new GenericRisk('Combat Card');
                $card->addManeuver(new Maneuver_PlusOneParry(), $game, false);
                $game->place($card, Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);
                $game->globals->set(Game::DUEL_ROUND, 1);
                $game->theah->buildCityCalls = 0;

                $active = $game->argsDuelUseManeuverFromCombatCard()['_private']['active'];
                Assert::true($game->theah->buildCityCalls > 0, 'buildCity before listing');
                Assert::same($card->Id, $active['cardId'], 'card id');
                Assert::count(1, $active['maneuvers'], 'maneuver listed');
                Assert::same(1, $active['round'], 'round');
            },

            'argsDuelPayForManeuverFromCombatCard exposes cost, discount, gambled, abnormalFlow' => function () {
                [$game] = $this->duelScene();
                $card = new GenericRisk('Combat Card', 2);
                $game->place($card, Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);
                $game->globals->set(Game::CHOSEN_CARD_COST, 2);
                $game->globals->set(Game::DISCOUNT, 1);
                $game->globals->set(Game::DUEL_GAMBLED, true);
                $game->globals->set(Game::ABNORMAL_FLOW, true);
                $game->globals->set(Game::DUEL_ROUND, 3);

                $active = $game->argsDuelPayForManeuverFromCombatCard()['_private']['active'];
                Assert::same($card->Id, $active['combatCardId'], 'card');
                Assert::same(2, $active['cost'], 'cost');
                Assert::same(1, $active['discount'], 'discount');
                Assert::true($active['gambled'], 'gambled');
                Assert::true($active['abnormalFlow'], 'abnormal');
                Assert::same(3, $active['round'], 'round');
            },

            'argsDuelChooseGambleCard peeks the duel actor deck, not the active player' => function () {
                [$game, $actor] = $this->duelScene();
                // WHY: Proper Drama can steal the gamble chooser seat for the opponent.
                $game->activePlayerId = 2;
                $top = $game->place(new GenericCharacter('Top'), 'Deck-1', 1);
                $second = $game->place(new GenericCharacter('Second'), 'Deck-1', 1);
                $game->topFactionCards = [['id' => $top->Id], ['id' => $second->Id]];
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);
                $game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);

                $cards = $game->argsDuelChooseGambleCard()['_private']['active']['cards'];
                Assert::count(2, $cards, 'two revealed');
                Assert::same($top->Id, $cards[0]['id'], 'top card');
                Assert::same($second->Id, $cards[1]['id'], 'second card');
                Assert::same($actor->ControllerId, 1, 'deck still actor controller');
            },

            'argsDuelChooseGambleCard can reveal from the bottom of the faction deck' => function () {
                [$game] = $this->duelScene();
                $bottom = $game->place(new GenericCharacter('Bottom'), 'Deck-1', 1);
                $game->bottomFactionCards = [['id' => $bottom->Id]];
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);
                $game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, true);

                $cards = $game->argsDuelChooseGambleCard()['_private']['active']['cards'];
                Assert::count(1, $cards, 'one from bottom');
                Assert::same($bottom->Id, $cards[0]['id'], 'bottom card');
            },
        ];
    }
}
