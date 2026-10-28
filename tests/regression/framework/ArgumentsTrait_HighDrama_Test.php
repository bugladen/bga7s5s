<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01178;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AbilityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericBrute;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericFactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;

/**
 * ArgumentsTrait: High Drama picker id lists (move/recruit/equip/claim/challenge/actions/brute).
 *
 * WHY: these filters are where UI bugs hide (DashedCombat offered, Extra Action lock ignored,
 * Defending Honor filtering on the active player after UL). Capability *gates* live in
 * Theah_Capabilities; this suite pins the *args wiring*.
 */
class ArgumentsTrait_HighDrama_Test extends TestCase
{
    public function name(): string
    {
        return 'ArgumentsTrait: High Drama pickers';
    }

    public function tests(): array
    {
        return [
            // ---- Move -----------------------------------------------------------------------

            'argsHighDramaMoveActionChoosePerformer excludes engaged and honours Extra Action lock' => function () {
                $game = new FrameworkActionsGame();
                $ready = $game->place(new GenericCharacter('Ready'), Game::LOCATION_CITY_DOCKS, 1);
                $tired = $game->place(new GenericCharacter('Tired'), Game::LOCATION_CITY_FORUM, 1);
                $tired->Engaged = true;
                $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);

                Assert::same([$ready->Id], $game->argsHighDramaMoveActionChoosePerformer()['ids'], 'only ready own');

                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $tired->Id);
                Assert::same([], $game->argsHighDramaMoveActionChoosePerformer()['ids'], 'lock on engaged → empty');

                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $ready->Id);
                Assert::same([$ready->Id], $game->argsHighDramaMoveActionChoosePerformer()['ids'], 'lock keeps ready');
            },

            'argsHighDramaMoveActionChooseLocation returns adjacent sites for the performer' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);

                $args = $game->argsHighDramaMoveActionChooseLocation();

                Assert::same($mover->Id, $args['performerId'], 'performer');
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $args['locations'], true), 'Docks adjacent to Forum');
                Assert::false(in_array(Game::LOCATION_CITY_FORUM, $args['locations'], true), 'not self');
            },

            // ---- Recruit --------------------------------------------------------------------

            'argsHighDramaRecruitActionChoosePerformer needs an uncontrolled Mercenary at site' => function () {
                $game = new FrameworkActionsGame();
                $withMerc = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $alone = $game->place(new GenericCharacter('Alone'), Game::LOCATION_CITY_FORUM, 1);
                $game->place(new GenericCityCharacter('Hire'), Game::LOCATION_CITY_DOCKS, 0);

                Assert::same([$withMerc->Id], $game->argsHighDramaRecruitActionChoosePerformer()['ids'], 'only with merc');
                unset($alone);
            },

            'argsHighDramaRecruitActionChooseMercenary filters to Negotiable after Parley on NORMAL' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $nego = $game->place(new GenericCityCharacter('Negotiable', true), Game::LOCATION_CITY_DOCKS, 0);
                $hard = $game->place(new GenericCityCharacter('Hard', false), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::RECRUIT_TYPE, Game::NORMAL_RECRUIT_TYPE);
                $game->globals->set(Game::DISCOUNT, 1);

                $before = $game->argsHighDramaRecruitActionChooseMercenary();
                Assert::true(in_array($nego->Id, $before['characterIds'], true), 'nego before parley');
                Assert::true(in_array($hard->Id, $before['characterIds'], true), 'hard before parley');

                $game->globals->set(Game::PERFORMER_PARLEYED, true);
                $after = $game->argsHighDramaRecruitActionChooseMercenary();
                Assert::same([$nego->Id], $after['characterIds'], 'only Negotiable after Parley');
                Assert::same(1, $after['discount'], 'discount passed');
            },

            'argsHighDramaRecruitActionPayForMercenary forces Cirilo cost to 1' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $game->place(new GenericCityCharacter('Hire', true), Game::LOCATION_CITY_DOCKS, 0);
                $merc->WealthCost = 5;
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_CARD, $merc->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $game->globals->set(Game::RECRUIT_TYPE, Game::CIRILO_RECRUIT_TYPE);

                $args = $game->argsHighDramaRecruitActionPayForMercenary();
                Assert::same(1, $args['cost'], 'Cirilo cost');
                Assert::true($args['negotiable'], 'negotiable flag');
                Assert::same(Game::CIRILO_RECRUIT_TYPE, $args['recruitType'], 'type');
            },

            'argsHighDramaRecruitActionParley exposes performer and recruitType (Silver Tongue Back gate)' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::CHOSEN_PERFORMER, 42);
                $game->globals->set(Game::RECRUIT_TYPE, Game::SILVER_TONGUE_RECRUIT_TYPE);

                Assert::same([
                    'performerId' => 42,
                    'recruitType' => Game::SILVER_TONGUE_RECRUIT_TYPE,
                ], $game->argsHighDramaRecruitActionParley(), 'parley args');
            },

            // ---- Equip ----------------------------------------------------------------------

            'argsHighDramaEquipActionChoosePerformer includes hosts with city or hand attachments' => function () {
                $game = new FrameworkActionsGame();
                $withCity = $game->place(new GenericCharacter('City Host'), Game::LOCATION_CITY_DOCKS, 1);
                $homeOnly = $game->place(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 1);
                $noGear = $game->place(new GenericCharacter('Empty'), Game::LOCATION_CITY_FORUM, 1);
                $game->place(new GenericAttachment('Loose'), Game::LOCATION_CITY_DOCKS, 0);

                $ids = $game->argsHighDramaEquipActionChoosePerformer()['_private']['active']['ids'];
                Assert::true(in_array($withCity->Id, $ids, true), 'city attachment host');
                Assert::false(in_array($homeOnly->Id, $ids, true), 'home needs hand attachment');
                Assert::false(in_array($noGear->Id, $ids, true), 'no gear at Forum');

                $game->place(new GenericFactionAttachment('Hand Gear', 1), Game::LOCATION_HAND, 1);
                $ids2 = $game->argsHighDramaEquipActionChoosePerformer()['_private']['active']['ids'];
                Assert::true(in_array($homeOnly->Id, $ids2, true), 'home with hand attachment');
                Assert::true(in_array($noGear->Id, $ids2, true), 'Forum with hand attachment');
            },

            'argsHighDramaEquipActionChoosePerformer skips hosts that cannotReceiveAttachments' => function () {
                $game = new FrameworkActionsGame();
                $blocked = new class('Blocked') extends GenericCharacter {
                    public function canReceiveAttachments(): bool
                    {
                        return false;
                    }
                };
                $game->place($blocked, Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericAttachment('Loose'), Game::LOCATION_CITY_DOCKS, 0);

                Assert::same([], $game->argsHighDramaEquipActionChoosePerformer()['_private']['active']['ids'], 'Orel-style host skipped');
            },

            'argsHighDramaEquipActionChoosePerformer can include opponents when a CanEquipToOpponents attachment is in hand' => function () {
                $game = new FrameworkActionsGame();
                $mine = $game->place(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $theirs = $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_FORUM, 2);
                $game->place(new GenericFactionAttachment('Curse', 1, true), Game::LOCATION_HAND, 1);

                $ids = $game->argsHighDramaEquipActionChoosePerformer()['_private']['active']['ids'];
                Assert::true(in_array($mine->Id, $ids, true), 'own');
                Assert::true(in_array($theirs->Id, $ids, true), 'opponent via CanEquipToOpponents');
            },

            'argsHighDramaEquipActionChooseAttachmentFromHand / PayForAttachmentFromHand expose hand picks' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $att = $game->place(new GenericFactionAttachment('Blade', 3), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_CARD, $att->Id);
                $game->globals->set(Game::DISCOUNT, 1);

                $choose = $game->argsHighDramaEquipActionChooseAttachmentFromHand()['_private']['active'];
                Assert::same([$att->Id], $choose['attachmentsInHand'], 'hand ids');

                $pay = $game->argsHighDramaEquipActionPayForAttachmentFromHand()['_private']['active'];
                Assert::same($att->Id, $pay['chosenAttachmentId'], 'chosen');
                Assert::same(3, $pay['cost'], 'cost');
                Assert::same(1, $pay['discount'], 'discount');
            },

            'argsHighDramaEquipActionChooseAttachmentFromPlay uses site attachments unless Smuggled Item' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $here = $game->place(new GenericAttachment('Here'), Game::LOCATION_CITY_DOCKS, 0);
                $elsewhere = $game->place(new GenericAttachment('There'), Game::LOCATION_CITY_FORUM, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::EQUIP_TYPE, Game::NORMAL_EQUIP_TYPE);

                Assert::same([$here->Id], $game->argsHighDramaEquipActionChooseAttachmentFromPlay()['attachmentsInPlay'], 'site only');

                $game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE);
                $smuggled = $game->argsHighDramaEquipActionChooseAttachmentFromPlay()['attachmentsInPlay'];
                Assert::true(in_array($here->Id, $smuggled, true), 'smuggled includes docks');
                Assert::true(in_array($elsewhere->Id, $smuggled, true), 'smuggled includes forum');
            },

            // ---- Claim ----------------------------------------------------------------------

            'argsHighDramaClaimActionChoosePerformer excludes engaged and DashedInfluence' => function () {
                $game = new FrameworkActionsGame();
                $ok = $game->place(new GenericCharacter('Ok'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged = $game->place(new GenericCharacter('Engaged'), Game::LOCATION_CITY_FORUM, 1);
                $engaged->Engaged = true;
                $dashed = $game->place(new GenericCharacter('Dashed'), Game::LOCATION_CITY_BAZAAR, 1);
                $dashed->DashedInfluence = true;

                Assert::same([$ok->Id], $game->argsHighDramaClaimActionChoosePerformer()['ids'], 'only claimable');
            },

            // ---- Challenge performer / target -----------------------------------------------

            'argsHighDramaChallengeActionChoosePerformer excludes DashedCombat, engaged, and needs an opponent at site' => function () {
                $game = new FrameworkActionsGame();
                $ok = $game->place(new GenericCharacter('Ok'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $alone = $game->place(new GenericCharacter('Alone'), Game::LOCATION_CITY_FORUM, 1);
                $engaged = $game->place(new GenericCharacter('Engaged'), Game::LOCATION_CITY_BAZAAR, 1);
                $engaged->Engaged = true;
                $game->place(new GenericCharacter('Foe2'), Game::LOCATION_CITY_BAZAAR, 2);
                $dashed = $game->place(new GenericCharacter('Dashed'), Game::LOCATION_CITY_DOCKS, 1);
                $dashed->DashedCombat = true;

                Assert::same([$ok->Id], $game->argsHighDramaChallengeActionChoosePerformer()['ids'], 'only ok challenger');
                unset($alone);
            },

            'argsHighDramaChallengeActionChoosePerformer: Carmella may challenge while engaged until AbilityUsed' => function () {
                $game = new FrameworkActionsGame();
                $carmella = $game->place(new _01178(), Game::LOCATION_CITY_DOCKS, 1);
                $carmella->Engaged = true;
                $carmella->AbilityUsed = false;
                $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                Assert::same([$carmella->Id], $game->argsHighDramaChallengeActionChoosePerformer()['ids'], 'engaged Carmella ok');

                $carmella->AbilityUsed = true;
                Assert::same([], $game->argsHighDramaChallengeActionChoosePerformer()['ids'], 'used up');
            },

            'argsHighDramaChallengeActionChooseTarget filters by performer controller, not active player' => function () {
                $game = new FrameworkActionsGame();
                // WHY: After Unyielding Loyalty the defender can still be active on this picker.
                $game->activePlayerId = 2;
                $challenger = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $defender = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $ally = $game->place(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $game->globals->set(Game::CHOSEN_ACTION, 'BasicChallenge');

                $ids = $game->argsHighDramaChallengeActionChooseTarget()['ids'];
                Assert::same([$defender->Id], $ids, 'only opposing characters of the challenger');
                unset($ally);
            },

            'argsHighDramaChallengeActionChooseTarget: targeting ability filters unless Defending Honor' => function () {
                $game = new FrameworkActionsGame();
                $challenger = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $game->place(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);
                $noble = $game->place(new GenericCharacter('Noble'), Game::LOCATION_CITY_DOCKS, 2);
                $owner = new AbilityCharacter('Owner');
                $targeting = new class extends GenericCharacterAction implements IAbilityThatTargetsCharacters {
                    public function isValidTargetForAbility(Game $game, Character $character): array
                    {
                        return [$character->hasTrait('Mercenary'), 'not a Mercenary'];
                    }
                };
                $owner->addAction($targeting, $game, false);
                $game->place($owner, Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $game->globals->set(Game::CHOSEN_ACTION, $targeting->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);

                Assert::same([$merc->Id], $game->argsHighDramaChallengeActionChooseTarget()['ids'], 'ability filter');

                $game->globals->set(Game::CHALLENGE_TYPE, Game::DEFENDING_HONOR_CHALLENGE_TYPE);
                $ids = $game->argsHighDramaChallengeActionChooseTarget()['ids'];
                Assert::true(in_array($merc->Id, $ids, true) && in_array($noble->Id, $ids, true), 'Defending Honor skips ability filter');
            },

            'argsHighDramaChallengeActionActivateTechnique returns performer, target, and techniques' => function () {
                $game = new FrameworkActionsGame();
                $challenger = new AbilityCharacter('Challenger');
                $challenger->addTechnique(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry(), $game, false);
                $game->place($challenger, Game::LOCATION_CITY_DOCKS, 1);
                $defender = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $game->globals->set(Game::IN_DUEL, false);

                $args = $game->argsHighDramaChallengeActionActivateTechnique();
                Assert::same($challenger->Id, $args['performerId'], 'performer');
                Assert::same($defender->Id, $args['targetId'], 'target');
                // Technique_PlusOneParry requires IN_DUEL; outside duel the list may be empty — pin shape.
                Assert::true(is_array($args['techniques']), 'techniques array');
            },

            // ---- In-play / in-hand / brute --------------------------------------------------

            'argsHighDramaInPlayActionChooseAction lists available in-play actions' => function () {
                $game = new FrameworkActionsGame();
                $owner = new AbilityCharacter('Owner');
                $action = new GenericCharacterAction();
                $owner->addAction($action, $game, false);
                $game->place($owner, Game::LOCATION_CITY_DOCKS, 1);

                $actions = $game->argsHighDramaInPlayActionChooseAction()['_private']['active']['actions'];
                Assert::count(1, $actions, 'one action');
                Assert::same($action->Id, $actions[0]['id'], 'action id');
            },

            'argsHighDramaInPlayActionConfirm exposes action name and owning card' => function () {
                $game = new FrameworkActionsGame();
                $owner = new AbilityCharacter('Owner');
                $action = new GenericCharacterAction();
                $owner->addAction($action, $game, false);
                $game->place($owner, Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_ACTION, $action->Id);

                $args = $game->argsHighDramaInPlayActionConfirm();
                Assert::same('Test Character Action', $args['actionName'], 'name');
                Assert::same($owner->Id, $args['actionCardId'], 'owner');
            },

            'argsHighDramaInHandActionChooseAction / ChoosePerformer / Pay wire Risk actions' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $risk = new GenericRisk('Hand Risk', 2);
                $action = new GenericRiskAction(true);
                $risk->addAction($action, $game, false);
                $game->place($risk, Game::LOCATION_HAND, 1);

                $choose = $game->argsHighDramaInHandActionChooseAction()['_private']['active'];
                Assert::count(1, $choose['actions'], 'one hand action');
                Assert::same([$risk->Id], $choose['ids'], 'card id');

                $game->globals->set(Game::CHOSEN_ACTION, $action->Id);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $game->globals->set(Game::ABNORMAL_FLOW, false);

                $performers = $game->argsHighDramaInHandActionChoosePerformer()['_private']['active'];
                Assert::true(in_array($performer->Id, $performers['ids'], true), 'performer offered');
                Assert::same($risk->Id, $performers['actionCardId'], 'risk card');

                $pay = $game->argsHighDramaInHandActionPay()['_private']['active'];
                Assert::same(2, $pay['cost'], 'wealth cost');
                Assert::same($performer->Id, $pay['performerId'], 'performer');
                Assert::true($pay['requiresPerformerSelected'], 'requires performer');
            },

            'argsHighDramaBruteActionChooseBrute / PayForBrute expose hand Brutes' => function () {
                $game = new FrameworkActionsGame();
                $brute = new GenericBrute('Thug');
                $brute->WealthCost = 2;
                $game->place($brute, Game::LOCATION_HAND, 1);
                $game->globals->set(Game::CHOSEN_CARD, $brute->Id);
                $game->globals->set(Game::DISCOUNT, 1);

                Assert::same([$brute->Id], $game->argsHighDramaBruteActionChooseBrute()['_private']['active']['ids'], 'brute ids');

                $pay = $game->argsHighDramaBruteActionPayForBrute()['_private']['active'];
                Assert::same($brute->Id, $pay['bruteId'], 'brute id');
                Assert::same(2, $pay['cost'], 'cost');
                Assert::same(1, $pay['discount'], 'discount');
            },
        ];
    }
}
