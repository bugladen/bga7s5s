<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd09;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01178;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01188;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;

/**
 * ArgumentsTrait: Challenge Accept UI args (intervene lists, refuse gates, threat preview).
 *
 * WHY: Accept is the densest args method — intervene specials + Daichi/Knives Out client
 * disables + locker fallbacks. Act* suites prove server enforcement; this pins what the
 * client is told so a filter regression does not silently empty/offer wrong buttons.
 */
class ArgumentsTrait_ChallengeAccept_Test extends TestCase
{
    public function name(): string
    {
        return 'ArgumentsTrait: challenge accept';
    }

    /**
     * @return array{0:FrameworkActionsGame,1:GenericCharacter|\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character,2:GenericCharacter|\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character}
     */
    private function acceptScene(int $type = Game::NORMAL_CHALLENGE_TYPE): array
    {
        $game = new FrameworkActionsGame();
        $game->activePlayerId = 2;
        $challenger = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $defender = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
        $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
        $game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
        $game->globals->set(Game::CHALLENGE_TYPE, $type);
        $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
        $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
        $game->globals->set(Game::CHOSEN_TECHNIQUE, '');
        return [$game, $challenger, $defender];
    }

    public function tests(): array
    {
        return [
            'argsHighDramaChallengeActionAcceptChallenge offers en garde interveners at the challenge site' => function () {
                [$game, $challenger, $defender] = $this->acceptScene();
                $hero = $game->place(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 2);
                $tired = $game->place(new GenericCharacter('Tired'), Game::LOCATION_CITY_DOCKS, 2);
                $tired->Engaged = true;
                $elsewhere = $game->place(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);

                $args = $game->argsHighDramaChallengeActionAcceptChallenge();

                Assert::same($challenger->Id, $args['performerId'], 'challenger');
                Assert::same($defender->Id, $args['targetId'], 'target');
                Assert::same([$hero->Id], $args['ids'], 'only en garde ally at site, not target');
                Assert::same(Game::NORMAL_CHALLENGE_TYPE, $args['challengeType'], 'type');
                Assert::true(is_int($args['defenderThreat']), 'threat preview int');
                Assert::same($defender->ModifiedFinesse, $args['defenderFinesse'], 'finesse');
                Assert::false($args['mustDiscardToRefuse'], 'normal refuse');
                Assert::false($args['cannotRefuseDueToDaichi'], 'no Daichi');
                Assert::false($args['cannotRefuseDueToKnivesOut'], 'no Knives Out');
                unset($elsewhere);
            },

            'argsHighDramaChallengeActionAcceptChallenge uses CHOSEN_LOCATION when the defender is already destroyed' => function () {
                [$game, $challenger, $defender] = $this->acceptScene();
                $hero = $game->place(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 2);
                // Destroyed defender still in DB/world for getCardById; Location is Locker.
                $defender->Location = 'Locker-2';
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $args = $game->argsHighDramaChallengeActionAcceptChallenge();
                Assert::same([$hero->Id], $args['ids'], 'interveners at challenge site, not locker');
                unset($challenger);
            },

            'argsHighDramaChallengeActionAcceptChallenge: Carmella may intervene while engaged until AbilityUsed' => function () {
                [$game] = $this->acceptScene();
                $carmella = $game->place(new _01178(), Game::LOCATION_CITY_DOCKS, 2);
                $carmella->Engaged = true;
                $carmella->AbilityUsed = false;

                Assert::same([$carmella->Id], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'engaged Carmella');

                $carmella->AbilityUsed = true;
                Assert::same([], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'used up');
            },

            'argsHighDramaChallengeActionAcceptChallenge: Rena with en garde Weapon may intervene while engaged' => function () {
                [$game] = $this->acceptScene();
                $rena = $game->place(new _01040(), Game::LOCATION_CITY_DOCKS, 2);
                $rena->Engaged = true;
                $weapon = $game->place(new GenericAttachment('Rapier'), Game::LOCATION_CITY_DOCKS, 2);
                $weapon->Traits = ['Weapon'];
                $rena->Attachments = [$weapon->Id];

                Assert::same([$rena->Id], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'Rena + weapon');

                $weapon->Engaged = true;
                Assert::same([], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'engaged weapon no longer qualifies');
            },

            'argsHighDramaChallengeActionAcceptChallenge: Vladislav may intervene while engaged' => function () {
                [$game] = $this->acceptScene();
                $vlad = $game->place(new _01188(), Game::LOCATION_CITY_DOCKS, 2);
                $vlad->Engaged = true;

                Assert::same([$vlad->Id], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'Vladislav engaged ok');
            },

            'argsHighDramaChallengeActionAcceptChallenge empties interveners for Raven / Valeri / Torvo' => function () {
                foreach ([
                    'Raven' => Game::RAVEN_CHALLENGE_TYPE,
                    'Valeri' => Game::VALERI_MIKHAILOV_CHALLENGE_TYPE,
                    'Torvo' => Game::TORVO_ESPADA_CHALLENGE_TYPE,
                ] as $label => $type) {
                    [$game] = $this->acceptScene($type);
                    $game->place(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 2);
                    Assert::same([], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], "$label no intervene");
                }
            },

            'argsHighDramaChallengeActionAcceptChallenge: Aja / Celerity require ModifiedFinesse >= 3' => function () {
                [$game] = $this->acceptScene(Game::AJA_CHALLENGE_TYPE);
                $slow = $game->place(new GenericCharacter('Slow'), Game::LOCATION_CITY_DOCKS, 2);
                $slow->ModifiedFinesse = 2;
                $fast = $game->place(new GenericCharacter('Fast'), Game::LOCATION_CITY_DOCKS, 2);
                $fast->ModifiedFinesse = 3;

                Assert::same([$fast->Id], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'Aja finesse gate');

                [$game] = $this->acceptScene(Game::CELERITY_CHALLENGE_TYPE);
                $slow = $game->place(new GenericCharacter('Slow'), Game::LOCATION_CITY_DOCKS, 2);
                $slow->ModifiedFinesse = 2;
                $fast = $game->place(new GenericCharacter('Fast'), Game::LOCATION_CITY_DOCKS, 2);
                $fast->ModifiedFinesse = 3;
                Assert::same([$fast->Id], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'Celerity finesse gate');
            },

            'argsHighDramaChallengeActionAcceptChallenge: Sworn Swords requires Duelist trait' => function () {
                [$game] = $this->acceptScene(Game::SWORN_SWORDS_CHALLENGE_TYPE);
                $game->place(new GenericCharacter('Peasant'), Game::LOCATION_CITY_DOCKS, 2);
                $duelist = $game->place(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 2);

                Assert::same([$duelist->Id], $game->argsHighDramaChallengeActionAcceptChallenge()['ids'], 'Duelist only');
            },

            'argsHighDramaChallengeActionAcceptChallenge: When Least Expected + Duelist challenger sets mustDiscardToRefuse' => function () {
                $game = new FrameworkActionsGame();
                $game->activePlayerId = 2;
                $challenger = $game->place(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $defender = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $game->place(new GenericCharacter('Hand'), Game::LOCATION_HAND, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::WHEN_LEAST_EXPECTED_CHALLENGE_TYPE);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $args = $game->argsHighDramaChallengeActionAcceptChallenge();
                Assert::true($args['mustDiscardToRefuse'], 'must discard');
                Assert::same(1, $args['defenderHandCount'], 'hand count');
            },

            'argsHighDramaChallengeActionAcceptChallenge: Daichi Combat gate sets cannotRefuseDueToDaichi' => function () {
                $game = new FrameworkActionsGame();
                $game->activePlayerId = 2;
                $daichi = $game->place(new _03050(), Game::LOCATION_CITY_DOCKS, 1);
                $defender = $game->place(new GenericCharacter('Strong'), Game::LOCATION_CITY_DOCKS, 2);
                $defender->Combat = 5;
                $defender->resetCard();
                $game->globals->set(Game::CHOSEN_PERFORMER, $daichi->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                Assert::true(
                    $game->argsHighDramaChallengeActionAcceptChallenge()['cannotRefuseDueToDaichi'],
                    'defender Combat > Daichi Combat'
                );
            },

            'argsHighDramaChallengeActionAcceptChallenge: Knives Out at the defender site sets cannotRefuseDueToKnivesOut' => function () {
                [$game, , $defender] = $this->acceptScene();
                $game->place(new _04cd09(), $defender->Location, 0);

                Assert::true(
                    $game->argsHighDramaChallengeActionAcceptChallenge()['cannotRefuseDueToKnivesOut'],
                    'Knives Out present'
                );
            },

            'argsHighDramaChallengeActionAcceptChallenge uses last-known Finesse when the defender is in the Locker' => function () {
                [$game, , $defender] = $this->acceptScene();
                $defender->Location = 'Locker-2';
                // WHY: FakeGame::characterIsInDiscardOrLocker is knobbed (not Location string match).
                $game->discardOrLockerByCardId[$defender->Id] = true;
                $defender->ModifiedFinesse = 1;
                $snapshot = new GenericCharacter('Snapshot');
                $snapshot->ModifiedFinesse = 4;
                // WHY: StatesTraitGame overrides getChallengeLastKnownCharacter to use lastKnownCharacters.
                $game->lastKnownCharacters[$defender->Id] = $snapshot;
                $game->place(new _04cd09(), Game::LOCATION_CITY_DOCKS, 0);

                $args = $game->argsHighDramaChallengeActionAcceptChallenge();
                Assert::same(4, $args['defenderFinesse'], 'last-known finesse');
                // WHY: Knives Out gate uses live $target — destroyed defenders are out of city → off.
                Assert::false($args['cannotRefuseDueToKnivesOut'], 'destroyed defender not at Knives Out');
            },
        ];
    }
}
