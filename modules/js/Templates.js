/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {

    // Global template for BGA format_block (expects template name lookup)
    window.jstpl_deck_picker = `
            <div id="deck-picker" class="_7sfs-deck-picker-container">
            <div class="_7sfs-deck-picker-modal">

                <div class="_7sfs-deck-picker-tab-content _7sfs-deck-picker-active" id="startingTab">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-banner-image"></div>
                <div class="_7sfs-deck-picker-tab-text">\${banner_description}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Castille" id="SDCastille">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-castille"></div>
                <div class="_7sfs-deck-picker-tab-text">\${castille_description_core}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Eisen" id="SDEisen">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-eisen"></div>
                <div class="_7sfs-deck-picker-tab-text">\${eisen_description_core}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Montaigne" id="SDMontaigne">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-montaigne"></div>
                <div class="_7sfs-deck-picker-tab-text">\${montaigne_description_core}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Ussura" id="SDUssura">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-ussura"></div>
                <div class="_7sfs-deck-picker-tab-text">\${ussura_description_core}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Vodacce" id="SDVodacce">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-vodacce"></div>
                <div class="_7sfs-deck-picker-tab-text">\${vodacce_description_core}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Castille" id="SDCastilleTaC">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-castille"></div>
                <div class="_7sfs-deck-picker-tab-text">\${castille_description_tac}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Eisen" id="SDEisenTaC">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-eisen"></div>
                <div class="_7sfs-deck-picker-tab-text">\${eisen_description_tac}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Montaigne" id="SDMontaigneTaC">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-montaigne"></div>
                <div class="_7sfs-deck-picker-tab-text">\${montaigne_description_tac}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Ussura" id="SDUssuraTaC">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-ussura"></div>
                <div class="_7sfs-deck-picker-tab-text">\${ussura_description_tac}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" data-deck-name="Vodacce" id="SDVodacceTaC">
                <div class="_7sfs-deck-picker-tab-image _7sfs-deck-picker-faction-image _7sfs-vodacce"></div>
                <div class="_7sfs-deck-picker-tab-text">\${vodacce_description_tac}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-content" id="Custom">
                <div style="flex: 1 1 auto; min-height: 0; display: flex;"><textarea id="customJson" placeholder="Paste your deck JSON here." class="_7sfs-deck-picker-custom-textarea"></textarea></div>
                <div class="_7sfs-deck-picker-tab-text">\${custom_description}</div>
                </div>

                <div class="_7sfs-deck-picker-tab-buttons">
                <button class="deck-picker-group-button _7sfs-deck-picker-tab-selected" onclick="gameui.deckPickerGroupShowTab(1)">Core</button>
                <button class="deck-picker-group-button" onclick="gameui.deckPickerGroupShowTab(2)">Tooth & Claw</button>
                </div>

                <div class="_7sfs-deck-picker-tab-buttons">
                <button class="deck-picker-button" onclick="gameui.deckPickerShowTab(gameui.deckPickerGroup + 1)">Castille</button>
                <button class="deck-picker-button" onclick="gameui.deckPickerShowTab(gameui.deckPickerGroup + 2)">Eisen</button>
                <button class="deck-picker-button" onclick="gameui.deckPickerShowTab(gameui.deckPickerGroup + 3)">Montaigne</button>
                <button class="deck-picker-button" onclick="gameui.deckPickerShowTab(gameui.deckPickerGroup + 4)">Ussura</button>
                <button class="deck-picker-button" onclick="gameui.deckPickerShowTab(gameui.deckPickerGroup + 5)">Vodacce</button>
                </div>

                <div class="_7sfs-deck-picker-tab-buttons">
                <button class="deck-picker-button" onclick="gameui.deckPickerShowTab(11)">Custom Deck Code</button>
                <button id="btnDeckSelect" class="deck-picker-button" disabled onclick="gameui.deckPickerDeckSelected()">\${select_description}</button>
                </div>
            </div>
            </div>
        `;

        window.jstpl_player_board = `
        <div>
            <div id="\${id}-score-reknown" class="_7sfs-score-reknown">\${reknown}</div>
            <div id="\${id}-score-crewcap" class="_7sfs-crew-cap _7sfs-score-crew-cap">\${crewcap}</div>
            <div id="\${id}-score-panache" class="_7sfs-panache _7sfs-score-panache">\${panache}</div>
            <div id="\${id}-score-hand-count" class="_7sfs-hand-count">\${handCount}</div>
            <div id="\${id}-score-turn-order" class="_7sfs-score-turn-order">\${turnOrder}</div>
            <div id="\${id}-score-seal-first-player" class="_7sfs-first-player-hidden"></div>
            <div id="\${id}-score-seal"></div>
        </div>
        `;
        
        window.jstpl_home = `
        <div id="\${id}" class="_7sfs-home-container _7sfs-home-\${faction}">
            <div class="_7sfs-home-panel">
                <div id="\${id}-crewcap" class="_7sfs-crew-cap _7sfs-home-crew-cap">\${crewcap}</div>
                <div id="\${id}-discard" data-player-id="\${id}" class="_7sfs-home-discard"></div>
                <div id="\${id}-panache" class="_7sfs-panache _7sfs-home-panache">\${panache}</div>
                <div id="\${id}-locker" data-player-id="\${id}" class="_7sfs-home-locker"></div>
                <div></div>
                <div class="_7sfs-seal-home _7sfs-seal-\${faction}-home"></div>
                <div>
                <div class="_7sfs-home-player-color" style="--player-color:#\${player_color}"></div>
                <div id="\${id}-scheme-anchor"></div>
                <div id="\${id}-first-player"></div>
                </div>
            </div>
            <div id="\${id}-home-anchor" class="_7sfs-home-endcap"></div>
        </div>
        `;
        
        window.jstpl_character = `
        <div id="\${id}" style="--attachment-count:\${attachmentCount}">
            <div id="\${id}_image" class="_7sfs-card _7sfs-home-\${faction}" style="--card_image:url('\${image}')">
                <div id="\${id}_resolve_value" class="_7sfs-card-resolve">\${resolve}</div>
                <div id="\${id}_wealth_cost" class="_7sfs-card-wealth-cost _7sfs-city-character-wealth-cost">\${cost}</div>
                <div class="_7sfs-card-stat-box _7sfs-card-combat-box">
                    <div id="\${id}_combat_value" class="_7sfs-card-combat-value">\${combat}</div>
                    <div class="_7sfs-card-combat-image"></div>
                </div>
                <div class="_7sfs-card-stat-box _7sfs-card-finesse-box">
                    <div id="\${id}_finesse_value" class="_7sfs-card-finesse-value">\${finesse}</div>
                    <div class="_7sfs-card-finesse-image"></div>
                </div>
                <div class="_7sfs-card-stat-box _7sfs-card-influence">
                    <div id="\${id}_influence_value" class="_7sfs-card-influence-value">\${influence}</div>
                    <div class="_7sfs-card-influence-image"></div>
                </div>
                <div id="\${id}-player-color" style="--player-color:#\${player_color}" class="_7sfs-character-player-color"></div>
            </div>
        </div>
        `;
        
        window.jstpl_card_attachment = `
        <div id="\${id}" style="--attachment-index:\${attachmentIndex}">
            <div id="\${id}_image" class="_7sfs-card _7sfs-home-\${faction}" style="--card_image:url('\${image}')">
                <div id="\${id}_resolve" class="_7sfs-card-resolve">\${resolve}</div>
                <div id="\${id}_wealth_cost" class="_7sfs-card-wealth-cost _7sfs-city-attachment-wealth-cost">\${cost}</div>
                <div id="\${id}_combat_box" class="_7sfs-card-stat-box _7sfs-card-combat-box">
                    <div class="_7sfs-card-combat-value _7sfs-attachment-combat-value">\${combat}</div>
                    <div class="_7sfs-card-combat-image"></div>
                </div>
                <div id="\${id}_finesse_box" class="_7sfs-card-stat-box _7sfs-card-finesse-box">
                    <div class="_7sfs-card-finesse-value _7sfs-attachment-finesse-value">\${finesse}</div>
                    <div class="_7sfs-card-finesse-image"></div>
                </div>
                <div id="\${id}_influence_box" class="_7sfs-card-stat-box _7sfs-card-influence">
                    <div class="_7sfs-card-influence-value _7sfs-attachment-influence-value">\${influence}</div>
                    <div class="_7sfs-card-influence-image"></div>
                </div>
            </div>
        </div>
        `;
        
        window.jstpl_status_bar_wealth_cost_chip = `
        <span class="_7sfs-card-wealth-cost _7sfs-status-bar-wealth-cost">\${cost}</span>
        `;

        window.jstpl_status_bar_threat_chip = `
        <span class="_7sfs-wound-chip _7sfs-status-bar-wound-chip">\${threat}</span>
        `;

        window.jstpl_hand_wealth_cost_chip = `
        <div id="\${id}_wealth_cost" class="_7sfs-card-wealth-cost _7sfs-hand-wealth-cost">\${cost}</div>
        `;
        
        window.jstpl_card_hidden = `
        <div id="\${id}">
            <div id="\${id}_image" class="_7sfs-card _7sfs-card-back" style="--card_image:url('\${image}')">
                <div id="\${id}-player-color" style="--player-color:#\${player_color}" class="_7sfs-character-player-color"></div>
            </div>
        </div>
        `;

        window.jstpl_card_hidden_attachment = `
        <div id="\${id}" style="--attachment-index:\${attachmentIndex}">
            <div id="\${id}_image" class="_7sfs-card _7sfs-card-back" style="--card_image:url('\${image}')">
                <div id="\${id}-player-color" style="--player-color:#\${player_color}" class="_7sfs-character-player-color"></div>
            </div>
        </div>
        `;
        
        window.jstpl_card_event = `
        <div id="\${id}">
            <div id="\${id}_image" class="_7sfs-card" style="--card_image:url('\${image}')">
            </div>
        </div>
        `;
        
        window.jstpl_card_scheme = `
        <div id="\${id}">
            <div id="\${id}_image" class="_7sfs-scheme" style="--card_image:url('\${image}')"></div>
            <div class="_7sfs-card-stat-box _7sfs-scheme-initiative-box">
                <div class="_7sfs-scheme-initiative-value">\${initiative}</div>
                <div class="_7sfs-scheme-initiative-image"></div>
            </div>
            <div class="_7sfs-card-stat-box _7sfs-scheme-panache-box">
                <div class="_7sfs-scheme-panache-value">\${panache}</div>
                <div class="_7sfs-scheme-panache-image"></div>
            </div>
            <div id="\${id}-player-color" style="--player-color:#\${player_color}" class="_7sfs-scheme-player-color"></div>
        </div>
        `;
        
        window.jstpl_reknown_chip = `
        <div id="\${id}" class="_7sfs-reknown-chip _7sfs-card-reknown-chip">\${amount}</div>
        `;
        
        window.jstpl_generic_chip = `
        <div id="\${id}" class="\${class}"></div>
        `;
        
        window.jstpl_discard_card = `
        <img class="_7sfs-discard-pile-card" src="\${image}" />
        </div>
        `;
        
        window.jstpl_location_control_chip = `
        <div id="\${id}-location-control-chip" style="--player-color:#\${player_color}" class="_7sfs-location-control-chip _7sfs-location-controller-player-color"></div>
        `;
        
        window.jstpl_forum_parley_gone_wrong_intervene_list = `
        <div id="forum-parley-intervene-list" class="_7sfs-forum-intervene-list"></div>
        `;

        window.jstpl_sirens_scream_used_list = `
        <div id="sirens-scream-used-list" class="_7sfs-card-player-list"></div>
        `;

        window.jstpl_crabs_in_a_bucket_used_list = `
        <div id="crabs-in-a-bucket-used-list" class="_7sfs-card-player-list"></div>
        `;

        window.jstpl_location_action_used_list = `
        <div id="location-action-used-list-\${actionId}" class="_7sfs-card-player-list _7sfs-location-action-used-list"></div>
        `;

        window.jstpl_cats_embargo_card_name = `
        <div id="cats-embargo-card-name" class="_7sfs-card-player-list"></div>
        `;
        
        window.jstpl_number_order_chip = `
        <div id="\${id}-number_order_chip" class="_7sfs-number-order-chip">\${id}</div>
        `;
        
        window.jstpl_duel_table = `
        <div id="duel_wrapper">
        <div id="duel_scroll_top"><div id="duel_scroll_top_inner"></div></div>
        <div id="duel">
        <table id="duel_table" class="duel">
        <tr id="duel_header_row">
            <th>Duel Round</th>
            <th>Actor</th>
            <th>Starting Threat Pool</th>
            <th colspan="2">Combat Card</th>
            <th colspan="2">Maneuver</th>
            <th colspan="2">Technique</th>
            <th>Ending Threat Pool</th>
            <th>Wounds</th>
        </tr>
        </table>
        </div>
        </div>
        `;
        
        window.jstpl_duel_round = `
        <tr>
            <td class="_7sfs-duel-round-indicator">\${round}</td>
            <td><div class="_7sfs-duel-round-actor" id="duel_round_\${round}_actor"></div></td><td>
            <table class="_7sfs-threat-table">
                <tr id="duel_round_\${round}_starting_challenger_threat_row">
                    <td><div id="duel_round_\${round}_challenger_name">\${challengerName}</div></td>
                    <td><div id="duel_round_\${round}_starting_challenger_threat" class="_7sfs-threat-chip">\${startingChallengerThreat}</div></td>
                </tr>
                <tr id="duel_round_\${round}_starting_defender_threat_row">
                    <td><div id="duel_round_\${round}_defender_name">\${defenderName}</div></td>
                    <td><div id="duel_round_\${round}_starting_defender_threat" class="_7sfs-threat-chip">\${startingDefenderThreat}</div></td>
                </tr>
            </table>
            </td>
            <td id="duel_round_\${round}_combat">Not Chosen</td>
            <td id="duel_round_\${round}_combat_stats">
                <table class="_7sfs-ability-table">
                    <tr><td><div class="_7sfs-combat-chip _7sfs-riposte-chip"><span id="duel_round_\${round}_combat_riposte" class="_7sfs-chip-value">\${combatRiposte}</span></div></td></tr>
                    <tr><td><div class="_7sfs-combat-chip _7sfs-parry-chip"><span id="duel_round_\${round}_combat_parry" class="_7sfs-chip-value">\${combatParry}</span></div></td></tr>
                    <tr><td><div class="_7sfs-combat-chip _7sfs-thrust-chip"><span id="duel_round_\${round}_combat_thrust" class="_7sfs-chip-value">\${combatThrust}</span></div></td></tr>
                </table>
            </td>
            <td id="duel_round_\${round}_maneuver">\${maneuver}</td>
            <td id="duel_round_\${round}_maneuver_stats">
                <table class="_7sfs-ability-table">
                    <tr><td><div class="_7sfs-combat-chip _7sfs-riposte-chip"><span id="duel_round_\${round}_maneuver_riposte" class="_7sfs-chip-value">\${maneuverRiposte}</span></div></td></tr>
                    <tr><td><div class="_7sfs-combat-chip _7sfs-parry-chip"><span id="duel_round_\${round}_maneuver_parry" class="_7sfs-chip-value">\${maneuverParry}</span></div></td></tr>
                    <tr><td><div class="_7sfs-combat-chip _7sfs-thrust-chip"><span id="duel_round_\${round}_maneuver_thrust" class="_7sfs-chip-value">\${maneuverThrust}</span></div></td></tr>
                </table>
            </td>
            <td id="duel_round_\${round}_technique">\${technique}</td>
            <td id="duel_round_\${round}_technique_stats">
                <table class="_7sfs-ability-table">
                    <tr><td><div class="_7sfs-combat-chip _7sfs-riposte-chip"><span id="duel_round_\${round}_technique_riposte" class="_7sfs-chip-value">\${techniqueRiposte}</span></div></td></tr>
                    <tr><td><div class="_7sfs-combat-chip _7sfs-parry-chip"><span id="duel_round_\${round}_technique_parry" class="_7sfs-chip-value">\${techniqueParry}</span></div></td></tr>
                    <tr><td><div class="_7sfs-combat-chip _7sfs-thrust-chip"><span id="duel_round_\${round}_technique_thrust" class="_7sfs-chip-value">\${techniqueThrust}</span></div></td></tr>
                </table>
            </td>
            <td>
            <table class="_7sfs-threat-table" id="duel_round_\${round}_ending_threat_table">
                <tr id="duel_round_\${round}_ending_challenger_threat_row">
                    <td>\${challengerName}</td>
                    <td><div id="duel_round_\${round}_ending_challenger_threat" class="_7sfs-threat-chip">\${endingChallengerThreat}</div></td>
                </tr>
                <tr id="duel_round_\${round}_ending_defender_threat_row">
                    <td>\${defenderName}</td>
                    <td><div id="duel_round_\${round}_ending_defender_threat" class="_7sfs-threat-chip">\${endingDefenderThreat}</div></td>
                </tr>
            </table>
            </td>
            <td><div id="duel_round_\${round}_wounds" class="_7sfs-duel-wound-chip">\${wounds}</div></td>
        </tr>
        `;
        
        window.jstpl_row_combat_card = `
        <div id="duel_round_\${round}_combat_card_\${id}" class="_7sfs-duel-row-combat-card" style="--card_image:url('\${image}')">
        </div>
        `;
       
        
        return declare('seventhseacityoffivesails.templates', null, {

            mainBoardhtml: `
                <div id="choose_container" class="whiteblock _7sfs-hand hidden">
                    <div class="_7sfs-hand-label"><span id="choose_container_name"></span></div>
                    <div id="chooseList" class="hidden">
                    </div>
                </div>

                <!-- Faction hand placeholder - contains the hand when not floating -->
                <div id="factionHand-placeholder" class="whiteblock _7sfs-hand _7sfs-hand-placeholder hidden">
                    <div class="_7sfs-hand-label">
                        <span>Your Faction Hand</span>
                        <span id="faction_hand_info"></span>
                    </div>
                    <!-- The hand wrapper - floats when scrolled -->
                    <div id="factionHand-wrapper" class="_7sfs-floating-hand-wrapper">
                        <div id="factionHand-container" class="_7sfs-floating-hand-container">
                            <div id="factionHand" class="_7sfs-floating-hand-cards">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Begin City -->
                <div id="city">
                    <div id="city-ul-tower">
                        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
                            <rect width="30" height="30" class="_7sfs-city-tower" />
                        </svg>
                    </div>
                    <div id="city-discard"></div>
                    <div id="city-locker"></div>
                    <div id="day-indicator"></div>
                    <div id="city-day-phase"></div>

                    <div id="city-ur-tower">
                        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
                            <rect width="30" height="30" class="_7sfs-city-tower" />
                        </svg>
                    </div>
                    <div id="city-oles-inn" class="_7sfs-city-location">
                        <div id="oles-inn-frontcap" class="_7sfs-city-endcap"></div>
                        <div class="_7sfs-city-location-contents">
                            <div id="oles-inn-reknown" class="_7sfs-reknown-chip _7sfs-city-reknown-chip"></div>
                            <div id="oles-inn-image" class="_7sfs-city-image" style="--city-image:url(https://dtdb.co/images/7s5s/en/01004.jpg)"></div>
                            <div id="oles-inn-endcap" class="_7sfs-city-endcap"></div>
                        </div>
                    </div>
                    <div></div>
                    <div id="city-docks" class="_7sfs-city-location">
                        <div id="dock-frontcap" class="_7sfs-city-endcap"></div>
                        <div class="_7sfs-city-location-contents">
                            <div id="dock-reknown" class="_7sfs-reknown-chip _7sfs-city-reknown-chip"></div>
                            <div id="dock-image" class="_7sfs-city-image" style="--city-image:url(https://dtdb.co/images/7s5s/en/01003.jpg)"></div>
                            <div id="dock-endcap" class="_7sfs-city-endcap"></div>
                        </div>
                    </div>
                    <div></div>
                    <div id="city-forum" class="_7sfs-city-location">
                        <div id="forum-frontcap" class="_7sfs-city-endcap"></div>
                        <div class="_7sfs-city-location-contents">
                            <div id="forum-reknown" class="_7sfs-reknown-chip _7sfs-city-reknown-chip"></div>
                            <div id="forum-image" class="_7sfs-city-image" style="--city-image:url(https://dtdb.co/images/7s5s/en/01001.jpg)"></div>
                            <div id="forum-endcap" class="_7sfs-city-endcap"></div>
                        </div>
                    </div>
                    <div></div>
                    <div id="city-bazaar" class="_7sfs-city-location">
                        <div id="bazaar-frontcap" class="_7sfs-city-endcap"></div>
                        <div class="_7sfs-city-location-contents">
                            <div id="bazaar-reknown" class="_7sfs-reknown-chip _7sfs-city-reknown-chip"></div>
                            <div id="bazaar-image" class="_7sfs-city-image" style="--city-image:url(https://dtdb.co/images/7s5s/en/01002.jpg)"></div>
                            <div id="bazaar-endcap" class="_7sfs-city-endcap"></div>
                        </div>
                    </div>
                    <div></div>
                    <div id="city-governors-garden" class="_7sfs-city-location">
                        <div id="garden-frontcap" class="_7sfs-city-endcap"></div>
                        <div class="_7sfs-city-location-contents">
                            <div id="garden-reknown" class="_7sfs-reknown-chip _7sfs-city-reknown-chip"></div>
                            <div id="garden-image" class="_7sfs-city-image" style="--city-image:url(https://dtdb.co/images/7s5s/en/01005.jpg)"></div>
                            <div id="garden-endcap" class="_7sfs-city-endcap"></div>
                        </div>
                    </div>
                    <div id="city-ll-tower">
                        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
                            <rect width="30" height="30" class="_7sfs-city-tower" />
                        </svg>
                    </div>
                    <div id="city-lr-tower">
                        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
                            <rect width="30" height="30" class="_7sfs-city-tower" />
                        </svg>
                    </div>
                </div>
                <!-- End  City -->

                <div id="home_wrapper">
                    <div id="home_anchor"></div>
                </div>

                <div id="approachDeck-container" class="whiteblock _7sfs-hand">
                    <div class="_7sfs-hand-label">Your Approach Deck</div>
                    <div id="approachDeck">
                    </div>
                </div>

                <div id="hand_anchor"></div>
            `,

    })
});
