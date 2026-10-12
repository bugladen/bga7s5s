/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Holiday.js — seasonal client-only decorations (Halloween, etc.)
 *
 * WHY separate mixin: keeps Setup / deck-picker free of seasonal branches; flip
 * window.CITY_HALLOWEEN and redeploy when the season ends.
 */

// WHY: Seasonal city-tower / map / ambience swap. Set to 1 for Halloween; 0 restores
// normal art. Client-only — no server/gamedatas involvement.
window.CITY_HALLOWEEN = 1;

define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.holiday', null, {

    isHolidayHalloween: function()
    {
        return window.CITY_HALLOWEEN === 1;
    },

    // WHY: Class on #city keeps tower DOM ids intact for city-deck FLIP origins.
    // Class on body also covers home-discard / hand-count outside #city.
    // Call early in setup(), right after main board HTML is inserted.
    setupHolidayClasses: function()
    {
        if (!this.isHolidayHalloween()) {
            return;
        }
        dojo.addClass('city', '_7sfs-city-halloween');
        dojo.addClass(document.body, '_7sfs-halloween');
    },

    // WHY: After unused locations (Ole's / Garden) are destroyed so we only
    // seed sides that remain on the board for this player count.
    setupHolidayFeatures: function()
    {
        if (!this.isHolidayHalloween()) {
            return;
        }
        this.addTippyTooltip('city-halloween-horseman', `<div class='_7sfs-basic-tooltip'>${_('Happy Halloween!')}</div>`);
        this.setupHalloweenGhosts();
        this.setupHalloweenChase();
    },

    // WHY: Deck-picker banner gets a festival blurb when Halloween is on.
    decorateHolidayDeckPickerBanner: function(bannerDescription)
    {
        if (!this.isHolidayHalloween()) {
            return bannerDescription;
        }
        return '<div style="background-color: rgba(45, 45, 45, 0.9); color: #FF7518; border: 2px solid #FF7518; padding: 10px; border-radius: 5px;">' +
            _('Samhein, Vaticine Holy Days, Festival of Flames, Halloween...') +
            '<br>' +
            _('Whatever you want to call it, it\'s currently being celebrated in the City of Five Sails!') +
            '</div><br>' +
            bannerDescription;
    },

    // WHY: CITY_HALLOWEEN ambient decoration only. Client-only; no server state.
    // One ghost layer per location side (my-cards / other-cards), 1–5 sprites each,
    // wandering via CSS left/top transitions. Layer z-index stays under cards.
    setupHalloweenGhosts: function()
    {
        const GHOST_SIZE = 50;
        const MAX_GHOSTS = 5;

        dojo.query('._7sfs-city-my-cards, ._7sfs-city-other-cards').forEach((side) => {
            const layer = dojo.create('div', { 'class': '_7sfs-halloween-ghost-layer' }, side, 'first');
            const count = 1 + Math.floor(Math.random() * MAX_GHOSTS);

            for (let i = 0; i < count; i++) {
                const ghost = dojo.create('div', { 'class': '_7sfs-halloween-ghost' }, layer);
                // Stagger bob so they don't pulse in lockstep
                ghost.style.animationDelay = `${(Math.random() * 3.5).toFixed(2)}s`;
                ghost.style.animationDuration = `${(2.8 + Math.random() * 2.4).toFixed(2)}s`;
                this.wanderHalloweenGhost(ghost, layer, GHOST_SIZE);
            }
        });
    },

    wanderHalloweenGhost: function(ghost, layer, size)
    {
        const place = (animate) => {
            const width = layer.clientWidth;
            const height = layer.clientHeight;
            // WHY: setup() runs before layout; retry until the side has a real box.
            if (width < size || height < size) {
                setTimeout(() => place(false), 100);
                return;
            }

            const maxX = Math.max(0, width - size);
            const maxY = Math.max(0, height - size);
            const x = Math.random() * maxX;
            const y = Math.random() * maxY;
            // Slow drift — 5–12s per leg so motion reads as ambient, not busy
            const durationMs = 5000 + Math.floor(Math.random() * 7000);

            ghost.style.transition = animate
                ? `left ${durationMs}ms linear, top ${durationMs}ms linear`
                : 'none';
            ghost.style.left = `${x}px`;
            ghost.style.top = `${y}px`;

            setTimeout(() => place(true), animate ? durationMs : 50);
        };

        place(false);
    },

    // WHY: CITY_HALLOWEEN ambient chase along the city top edge between the day-phase
    // chip and UR tower. Client-only. One left transition on the chase group (horseman
    // position); villagers stay just ahead via offsets. At a boundary they turn one at
    // a time (lead villager first), then the return leg starts — not a lockstep flip.
    setupHalloweenChase: function()
    {
        const chase = $('city-halloween-chase');
        const horseman = $('city-halloween-horseman');
        const villager1 = $('city-halloween-villager-1');
        const villager2 = $('city-halloween-villager-2');
        const city = $('city');
        if (!chase || !horseman || !villager1 || !villager2 || !city) {
            return;
        }

        const HORSE = 31;
        // Native art faces left — scaleX(-1) when traveling toward the tower (right).
        const FACE_RIGHT = 'scaleX(-1)';
        const FACE_LEFT = 'scaleX(1)';
        // WHY: Stagger so the lead villager (furthest ahead) "hits the wall" and turns
        // first, then the other, then the horseman — reads as individuals, not a unit.
        // Kept slower than the crossing so the pivot reads as a deliberate scramble.
        const TURN_STAGGER_MS = 650;
        const TURN_SLIDE_MS = 850;

        const relLeft = (el, cityRect, borderLeft, fallback) => {
            if (!el) {
                return fallback;
            }
            const rect = el.getBoundingClientRect();
            // WHY: #city-day-phase-row is display:none until turnPhase > 0 — zero rect.
            if (rect.width === 0 && rect.height === 0) {
                return fallback;
            }
            return rect.left - cityRect.left - borderLeft;
        };

        const faceOf = (towardTower) => (towardTower ? FACE_RIGHT : FACE_LEFT);

        // Ahead offsets relative to horseman at left:0 — always just ahead of him.
        const aheadOf = (towardTower) => (towardTower
            ? { v1: '34px', v2: '50px' }
            : { v1: '-20px', v2: '-36px' });

        // Instant formation (boot / no stagger).
        const snapToward = (towardTower) => {
            const face = faceOf(towardTower);
            const ahead = aheadOf(towardTower);
            horseman.style.transform = face;
            villager1.style.transform = face;
            villager2.style.transform = face;
            villager1.style.left = ahead.v1;
            villager2.style.left = ahead.v2;
        };

        // WHY: At the boundary, turn lead villager → other villager → horseman, each
        // flipping and sliding to the new ahead side on their own beat.
        const turnToward = (towardTower, onDone) => {
            const face = faceOf(towardTower);
            const ahead = aheadOf(towardTower);

            villager2.style.transform = face;
            villager2.style.left = ahead.v2;

            setTimeout(() => {
                villager1.style.transform = face;
                villager1.style.left = ahead.v1;
            }, TURN_STAGGER_MS);

            setTimeout(() => {
                horseman.style.transform = face;
            }, TURN_STAGGER_MS * 2);

            // WHY: Wait for the last villager's left slide to finish before the return leg.
            setTimeout(onDone, TURN_STAGGER_MS + TURN_SLIDE_MS);
        };

        const endpoints = () => {
            const cityRect = city.getBoundingClientRect();
            const borderLeft = parseFloat(getComputedStyle(city).borderLeftWidth) || 0;
            const phase = $('city-day-phase');
            const tower = $('city-ur-tower');
            // Fallback 160 matches #city-day-phase-row { left: 160px }.
            // WHY: Park just to the right of the chip (old in-row spot) so endpoint
            // doesn't cover Dawn / High Drama text.
            let phaseX = relLeft(phase, cityRect, borderLeft, 160);
            if (phase) {
                const phaseRect = phase.getBoundingClientRect();
                if (phaseRect.width > 0) {
                    phaseX = phaseRect.right - cityRect.left - borderLeft + 4;
                }
            }
            // WHY: Stop when horseman's right edge meets the tower's left. Villagers
            // sit ahead and may briefly overlap the tower edge — better than cutting
            // the horseman short of his boundary.
            const towerLeft = relLeft(tower, cityRect, borderLeft, Math.max(phaseX + HORSE * 2, cityRect.width - borderLeft));
            return { ready: cityRect.width >= 50, phaseX, towerX: towerLeft - HORSE };
        };

        // towardTower=true → ride to UR tower; false → ride back to phase.
        // Callers must already have snapped/turned the pack to face towardTower.
        const ride = (towardTower) => {
            const { ready, phaseX, towerX } = endpoints();
            if (!ready) {
                setTimeout(() => ride(towardTower), 100);
                return;
            }

            const targetX = towardTower ? towerX : phaseX;
            // Ambient — 3–15s per crossing; random so each pass feels different.
            const durationMs = 3000 + Math.floor(Math.random() * 5000);

            let done = false;
            const onArrive = () => {
                if (done) {
                    return;
                }
                done = true;
                chase.removeEventListener('transitionend', onTransitionEnd);
                // WHY: Turn individuals at the boundary, then start the return leg.
                turnToward(!towardTower, () => ride(!towardTower));
            };
            const onTransitionEnd = (event) => {
                if (event.target !== chase || event.propertyName !== 'left') {
                    return;
                }
                onArrive();
            };

            chase.addEventListener('transitionend', onTransitionEnd);
            chase.style.transition = `left ${durationMs}ms linear`;
            chase.style.left = `${targetX}px`;
            setTimeout(onArrive, durationMs + 50);
        };

        const boot = () => {
            const { ready, phaseX } = endpoints();
            if (!ready) {
                setTimeout(boot, 100);
                return;
            }
            snapToward(true);
            chase.style.transition = 'none';
            chase.style.left = `${phaseX}px`;
            setTimeout(() => ride(true), 50);
        };

        boot();
    },
})
});
