<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03013_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03013';
    }

    /**
     * @return array{0:_03013,1:Technique_03013,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $daniella = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
        $hunter = $world->placeCharacter(new GenericCharacter('Hunter', ['Hunter']), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Technique_03013 $technique */
        $technique = $daniella->getTechniques()[0];
        return [$daniella, $technique, $hunter];
    }

    public function tests(): array
    {
        return [
            'is a technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_03013(), 'technique');
            },

            // WHY: Harpoon is checked on activate, before the wound cost, so she is not
            // wounded and then blocked. Outside a duel the condition does not throw.
            'needs your Hunter or Zealot here, and Harpoon blocks the swap in a duel' => function () {
                $world = new TestWorld();
                [$daniella, $technique, $hunter] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'hunter present');
                $args = $technique->getArgsFromTechnique($world->game, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013, 'x');
                Assert::same([$hunter->Id], $args['characterIds'], 'hunter');

                $hunter->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'elsewhere');
                $hunter->Location = Game::LOCATION_CITY_DOCKS;

                $foe = $world->placeCharacter(new GenericCharacter('Foe', ['Zealot']), Game::LOCATION_CITY_DOCKS, 2);
                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03013, 'x');
                Assert::false(in_array($foe->Id, $args['characterIds'], true), 'not yours');

                $daniella->addCondition(Game::HARPOON_CONDITION);
                $world->game->globals->set(Game::IN_DUEL, true);
                $activated = new EventTechniqueActivated();
                $activated->theah = $world->theah;
                $activated->techniqueId = $technique->Id;
                $threw = false;
                try {
                    $technique->eventCheck($activated);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'harpooned in a duel');

                $world->game->globals->set(Game::IN_DUEL, false);
                $technique->eventCheck($activated);
                Assert::true(true, 'harpoon outside a duel does not throw');
            },

            'resolve wounds her, and the challenge swap rewrites the challenger' => function () {
                $world = new TestWorld();
                [$daniella, $technique, $hunter] = $this->scene($world);
                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($daniella->Id, $wound->characterId, 'wounds Daniella');
                Assert::same(1, $wound->wounds, 'one');
                Assert::same('03013', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $daniella->addCondition(Game::DUEL_CHALLENGER);
                $technique->actFromTechniqueWithId($world->game, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013, 'x', $hunter->Id);
                Assert::false($daniella->hasCondition(Game::DUEL_CHALLENGER), 'she is no longer challenger');
                Assert::true($hunter->hasCondition(Game::DUEL_CHALLENGER), 'hunter is');
                Assert::same($hunter->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                $swapped = $world->theah->queuedOfType(EventChallengerSwapped::class)[0];
                Assert::same($daniella->Id, $swapped->oldChallengerId, 'old');
                Assert::same($hunter->Id, $swapped->newChallengerId, 'new');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');

                $threat = new EventGenerateChallengeThreat();
                $threat->theah = $world->theah;
                $threat->techniqueId = $technique->Id;
                $threat->actorId = $daniella->Id;
                $technique->handleEvent($threat);
                Assert::same($hunter->Id, $threat->actorId, 'threat uses the swap');
            },

            'in a duel the swap rewrites the participant row, and cancel forgets it' => function () {
                $world = new TestWorld();
                [$daniella, $technique, $hunter] = $this->scene($world);
                $world->game->globals->set(Game::DUEL_ID, 7);
                $world->game->globals->set(Game::DUEL_ROUND, 2);
                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03013, 'x', $hunter->Id);
                Assert::same([
                    ['duelId' => 7, 'round' => 2, 'oldId' => $daniella->Id, 'newId' => $hunter->Id],
                ], $world->theah->swappedParticipants, 'duel row');

                $canceled = new EventTechniqueCanceled();
                $canceled->theah = $world->theah;
                $canceled->techniqueId = $technique->Id;
                $technique->handleEvent($canceled);
                $threat = new EventGenerateChallengeThreat();
                $threat->theah = $world->theah;
                $threat->techniqueId = $technique->Id;
                $threat->actorId = $daniella->Id;
                $technique->handleEvent($threat);
                Assert::same($daniella->Id, $threat->actorId, 'cancel cleared the swap');
            },

            'a character who is not your Hunter or Zealot throws' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $civilian = $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 1);
                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03013, 'x', $civilian->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not a hunter or zealot');
                Assert::same([], $world->theah->swappedParticipants, 'no rewrite');
            },
        ];
    }
}
