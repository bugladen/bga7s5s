{OVERALL_GAME_HEADER}

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&display=swap" rel="stylesheet">

<!-- 
--------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    seventhseacityoffivesails_seventhseacityoffivesails.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<script type="text/javascript">

var jstpl_player_board = `
<div class="cp_board">
    <div id="\${id}-score-reknown" class="score-reknown">\${reknown}</div>
    <div id="\${id}-score-crewcap" class="crew-cap score-crew-cap">\${crewcap}</div>
    <div id="\${id}-score-panache" class="panache score-panache">\${panache}</div>
    <div id="\${id}-score-seal"></div>
</div>
`;

var jstpl_home=`
<div id="\${id}" class="home-container home-\${faction}">
    <div class="home-panel">
        <div id="\${id}-crewcap" class="crew-cap home-crew-cap">\${crewcap}</div>
        <div id="\${id}-discard" class="home-discard"></div>
        <div id="\${id}-panache" class="panache home-panache">\${panache}</div>
        <div id="\${id}-locker" class="home-locker"></div>
        <div></div>
        <div class="seal-home seal-\${faction}-home"></div>
        <div>
        <div class="home-player-color" style="--player-color:#\${player_color}"></div>
        <div id="\${id}-scheme-anchor"></div>
        <div id="\${id}-first-player"></div>
        </div>
    </div>
    <div id="\${id}-home-anchor" class="home-endcap"></div>
</div>
`;

var jstpl_character=`
<div id="\${id}">
    <div class="card home-\${faction}" style="--card_image:url('\${image}')">
        <div class="card-resolve">\${resolve}</div>
        <div id="\${id}-wealth-cost" class="card-wealth-cost city-character-wealth-cost">\${cost}</div>
        <div class="card-stat-box card-combat-box">
            <div class="card-combat-value">\${combat}</div>
            <div class="card-combat-image"></div>
        </div>
        <div class="card-stat-box card-finesse-box">
            <div class="finesse-value card-finesse-value">\${finesse}</div>
            <div class="card-finesse-image"></div>
        </div>
        <div class="card-stat-box card-influence">
            <div class="card-influence-value">\${influence}</div>
            <div class="card-influence-image"></div>
        </div>
        <div id="\${id}-player-color" style="--player-color:#\${player_color}" class="character-player-color"></div>
    </div>
</div>
`;

var jstpl_card_attachment=`
<div id="\${id}">
    <div class="card home-\${faction}" style="--card_image:url('\${image}')">
        <div class="card-resolve">\${resolve}</div>
        <div id="\${id}-wealth-cost" class="card-wealth-cost">\${cost}</div>
        <div class="card-stat-box card-combat-box">
            <div class="card-combat-value attachment-combat-value">\${combat}</div>
            <div class="card-combat-image"></div>
        </div>
        <div class="card-stat-box card-finesse-box">
            <div class="card-finesse-value attachment-finesse-value">\${finesse}</div>
            <div class="card-finesse-image"></div>
        </div>
        <div class="card-stat-box card-influence">
            <div class="card-influence-value attachment-influence-value">\${influence}</div>
            <div class="card-influence-image"></div>
        </div>
    </div>
</div>
`;

var jstpl_card_event=`
<div id="\${id}">
    <div class="card" style="--card_image:url('\${image}')">
    </div>
</div>
`;

var jstpl_card_scheme=`
<div id="\${id}">
    <div class="scheme" style="--card_image:url('\${image}')"></div>
    <div class="card-stat-box scheme-initiative-box">
        <div class="scheme-initiative-value">\${initiative}</div>
        <div class="scheme-initiative-image"></div>
    </div>
    <div class="card-stat-box scheme-panache-box">
        <div class="scheme-panache-value">\${panache}</div>
        <div class="scheme-panache-image"></div>
    </div>
</div>
`;

var jstpl_reknown_chip = `
<div id="\${id}" class="reknown-chip card-reknown-chip">\${amount}</div>
`;

</script>  

<!-- Begin City -->
<div id="city">
    <div id="city-ul-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
    <div id="city-discard"></div>
    <div id="day-indicator"></div>
    <div id="city-day-phase"></div>

    <div id="city-ur-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
    <div id="city-oles-inn" class="city-location">
        <div id="oles-inn-reknown" class="reknown-chip city-reknown-chip">0</div>
        <div id="oles-inn-image" class="city-image"style="--city-image:url(img/cards/7s5s/004.jpg)"></div>
        <div id="oles-inn-endcap" class="city-endcap"></div>
    </div>
    <div></div>
    <div id="city-docks" class="city-location">
        <div id="dock-reknown" class="reknown-chip city-reknown-chip">0</div>
        <div id="dock-image" class="city-image" style="--city-image:url(img/cards/7s5s/003.jpg)"></div>
        <div id="dock-endcap" class="city-endcap"></div>
    </div>
    <div></div>
    <div id="city-forum" class="city-location">
        <div id="forum-reknown" class="reknown-chip city-reknown-chip">0</div>
        <div id="forum-image" class="city-image" style="--city-image:url(img/cards/7s5s/001.jpg)"></div>
        <div id="forum-endcap" class="city-endcap"></div>
    </div>
    <div></div>
    <div id="city-bazaar" class="city-location">
        <div id="bazaar-reknown" class="reknown-chip city-reknown-chip">0</div>
        <div id="bazaar-image" class="city-image" style="--city-image:url(img/cards/7s5s/002.jpg)"></div>
        <div id="bazaar-endcap" class="city-endcap"></div>
    </div>
    <div></div>
    <div id="city-governors-garden" class="city-location">
        <div id="governors-garden-reknown" class="reknown-chip city-reknown-chip">0</div>
        <div id="governors-garden-image" class="city-image" style="--city-image:url(img/cards/7s5s/005.jpg)"></div>
        <div id="governors-garden-endcap" class="city-endcap"></div>
    </div>
    <div id="city-ll-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
    <div id="city-lr-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
</div>
<!-- End  City -->

<div id="home_anchor"></div>

<div class="whiteblock hand">
    <div><b>Your Approach Deck</b></div>
    <div id="approachDeck">
    </div>
</div>

{OVERALL_GAME_FOOTER}
