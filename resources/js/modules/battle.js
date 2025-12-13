
import axios from 'axios';


// --- PvP Turn Timer (60s) ---
// Updates #pvp-turn-fill width + #pvp-turn-label text.
// Expects state fields: turn_started_at, turn_ends_at, server_now, next_actor_id, viewer_user_id/user_id.
let __pvpTimerInterval = null;

function refreshPvpTimer(state = {}) {
    const fill =
        document.getElementById('pvp-turn-fill') ||
        document.querySelector('[data-pvp-turn-timer-bar]');
    const label =
        document.getElementById('pvp-turn-label') ||
        document.querySelector('[data-pvp-turn-timer-label]');

    if (!fill) return;

    // clear previous
    if (__pvpTimerInterval) {
        clearInterval(__pvpTimerInterval);
        __pvpTimerInterval = null;
    }

    const startedAtRaw = state.turn_started_at ?? state.battle?.turn_started_at ?? null;
    const endsAtRaw = state.turn_ends_at ?? state.battle?.turn_ends_at ?? null;
    const serverNowRaw = state.server_now ?? state.battle?.server_now ?? null;

    const startedAt = startedAtRaw ? new Date(startedAtRaw).getTime() : NaN;
    const endsAt = endsAtRaw ? new Date(endsAtRaw).getTime() : NaN;

    // If we don't have valid timestamps, reset UI
    if (!Number.isFinite(startedAt) || !Number.isFinite(endsAt) || endsAt <= startedAt) {
        fill.style.width = '0%';
        if (label) label.textContent = '';
        return;
    }

    // stable server/client offset
    const serverNow = serverNowRaw ? new Date(serverNowRaw).getTime() : NaN;
    const offsetMs = Number.isFinite(serverNow) ? (serverNow - Date.now()) : 0;

    const viewerId =
        state?.viewer_user_id ??
        state?.viewer_id ??
        state?.user_id ??
        Number(document.querySelector('[data-user-id]')?.dataset?.userId) ??
        window.__viewerUserId ??
        window.viewerUserId ??
        null;



    const nextActorId =
        state?.next_actor_id ??
        state?.state?.next_actor_id ??
        state?.battle?.next_actor_id ??
        state?.state?.battle?.next_actor_id ??
        null;


    function tick() {
        const now = Date.now() + offsetMs;
        const total = Math.max(1, endsAt - startedAt);
        const remaining = Math.max(0, endsAt - now);
        const pct = Math.max(0, Math.min(100, (remaining / total) * 100));

        fill.style.width = `${pct}%`;

        if (label) {
            let who = 'Turn';
            if (viewerId !== null && nextActorId !== null) {
                who = String(nextActorId) === String(viewerId) ? 'Your turn' : 'Opponent turn';
            }
            label.textContent = `${who} • ${Math.ceil(remaining / 1000)}s`;
        }

        if (remaining <= 0 && __pvpTimerInterval) {
            clearInterval(__pvpTimerInterval);
            __pvpTimerInterval = null;
        }
    }

    tick();
    __pvpTimerInterval = setInterval(tick, 200);
}



const escapeHtml = (value = '') =>
    `${value}`
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

function getAudio(id) {
    const el = document.getElementById(id);
    return el && typeof el.play === 'function' ? el : null;
}

let mainClickSound = null;
let moveClickSound = null;
let lastNextActorUserId = null;
let pvpInputLocked = false;

function initBattleSounds() {
    mainClickSound = getAudio('battle-click-main') || getAudio('battle-click-sound') || null;
    moveClickSound = getAudio('battle-click-move') || mainClickSound;
}

function playSound(audioEl) {
    if (!audioEl) return;
    try {
        audioEl.currentTime = 0;
        audioEl.play();
    } catch (e) {
        // ignore autoplay/gesture errors
    }
}

function isPvpMode() {
    const root =
        document.getElementById('wild-battle-page') ||
        document.querySelector('[data-mode="pvp"]');

    return root?.dataset?.mode === 'pvp';
}


function setPvpInputLocked(locked) {
    pvpInputLocked = Boolean(locked);

    const overlay = document.getElementById('pvp-wait-overlay');
    if (overlay) {
        overlay.classList.toggle('is-hidden', !locked);
    }

    document
        .querySelectorAll('.js-battle-main-action, .js-battle-move, .js-switch-monster')
        .forEach((el) => {
            el.disabled = locked;
            el.classList.toggle('is-disabled', locked);
        });
}


const turnChangeSound = () => getAudio('pvp-turn-change-sound');

/**
 * MINIMAL CHANGE:
 * - Accept an optional explicitViewerId (we pass viewerId from initBattleLive/applyUpdate)
 * - Add stronger viewerId fallback via DOM dataset
 * - Add broader nextActorId fallbacks (covers merged timerState shapes)
 */
function applyPvpTurnUi(state = {}, explicitViewerId = null) {
    const root =
        document.getElementById('wild-battle-page') ||
        document.querySelector('[data-mode="pvp"]') ||
        document.getElementById('battle-root');

    if (!root || root.dataset.mode !== 'pvp') {
        lastNextActorUserId = null;
        return;
    }

    const viewerId =
        explicitViewerId ??
        state?.viewer_user_id ??
        state?.viewer_id ??
        state?.user_id ??
        Number(root?.dataset?.userId) ??
        Number(document.querySelector('[data-user-id]')?.dataset?.userId) ??
        window.__viewerUserId ??
        window.viewerUserId ??
        null;

    const nextActorId =
        state?.next_actor_id ??
        state?.state?.next_actor_id ??
        state?.battle?.next_actor_id ??
        state?.state?.battle?.next_actor_id ??
        state?.next_actor_user_id ??
        state?.battle?.next_actor_user_id ??
        null;

    if (!viewerId || !nextActorId) return;

    if (lastNextActorUserId !== null && String(lastNextActorUserId) !== String(nextActorId)) {
        playSound(turnChangeSound());
    }
    lastNextActorUserId = nextActorId;

    const isYourTurn = String(nextActorId) === String(viewerId);
    setPvpInputLocked(!isYourTurn);

    const subtitle = document.querySelector('#pvp-wait-overlay .pvp-wait-overlay__subtitle');
    if (subtitle) subtitle.textContent = isYourTurn ? 'Your turn' : 'Their turn';
}


export function wireBattleSounds(root) {
    if (!root) return;

    initBattleSounds();

    root.addEventListener('click', (event) => {
        const target = event.target;
        if (!target) return;

        const moveBtn = target.closest('.js-battle-move');
        if (moveBtn) {
            playSound(moveClickSound);

            moveBtn.classList.add('is-pressed');
            setTimeout(() => {
                moveBtn.classList.remove('is-pressed');
            }, 120);
            return;
        }

        const mainAction = target.closest('.js-battle-main-action');
        if (mainAction) {
            playSound(mainClickSound);
        }
    });
}

const hpPercent = (monster) => {
    if (!monster) return 0;
    const max = Math.max(1, monster.max_hp || 0);
    return Math.max(0, Math.min(100, Math.floor(((monster.current_hp || 0) / max) * 100)));
};

const resolveMonsterDisplay = (monster, role = 'you') => {
    const placeholder = Boolean(monster?.is_placeholder) && role === 'opponent';
    const fainted = monster?.is_fainted ?? ((monster?.current_hp || 0) <= 0);
    const name = placeholder ? (fainted ? 'Fainted monster' : 'Unknown monster') : monster?.name || 'Unknown';
    const hpText = placeholder
        ? fainted
            ? 'Fainted'
            : 'Ready'
        : `HP ${monster?.current_hp ?? 0}${monster?.max_hp != null ? ` / ${monster.max_hp}` : ''}`;

    return {
        placeholder,
        fainted,
        name,
        hpText,
    };
};

const formatTypes = (monster) => {
    const types = monster?.type_names || [];

    return types.length ? types.join(', ') : 'Neutral';
};

const renderSide = (side, role) => {
    if (!side) {
        return `<p class="text-sm text-gray-500">No combatant available.</p>`;
    }

    const active = side.monsters?.[side.active_index ?? 0];
    const bench = (side.monsters || []).filter((_, idx) => idx !== (side.active_index ?? 0));
    const hp = hpPercent(active);
    const hpText = active ? `HP ${active.current_hp} / ${active.max_hp}` : 'HP 0 / 0';
    const status = active?.status?.name ? `Status: ${escapeHtml(active.status.name)}` : '';
    const statusClass = role === 'you' ? 'text-amber-300' : 'text-amber-700';
    const typeText = active ? formatTypes(active) : 'Neutral';
    const nameClass = role === 'you' ? '' : 'text-gray-900';
    const labelClass = role === 'you' ? 'text-slate-300' : 'text-gray-600';
    const bgBar = role === 'you' ? 'bg-emerald-400' : 'bg-rose-400';
    const barTrack = role === 'you' ? 'bg-slate-700' : 'bg-gray-200';
    const benchLabels = bench
        .map((monster) => {
            const { name, hpText, fainted, placeholder } = resolveMonsterDisplay(monster, role);
            const displayText = placeholder && role === 'opponent' ? `${name} (${hpText})` : `${monster?.name || name} (${hpText})`;

            return `<span class="px-2 py-1 rounded-full ${role === 'you' ? 'bg-slate-800 border border-slate-700' : 'bg-gray-200 border border-gray-300'} ${fainted ? 'line-through opacity-70' : ''}">${escapeHtml(displayText)}</span>`;
        })
        .join('');

    return `
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold ${nameClass}" data-monster-name="${role}">${escapeHtml(active?.name || 'No fighter')}</h2>
                <p class="text-sm ${labelClass}" data-monster-types="${role}">Types: ${escapeHtml(typeText)}</p>
                <p class="text-sm ${statusClass} ${active?.status ? '' : 'hidden'}" data-monster-status="${role}">${escapeHtml(status)}</p>
            </div>
            <div class="w-48" data-monster-hp-container="${role}">
                <div class="text-right text-xs ${labelClass}" data-monster-hp-text="${role}">${escapeHtml(hpText)}</div>
                <div class="w-full ${barTrack} rounded-full h-3">
                    <div class="h-3 rounded-full ${bgBar}" data-monster-hp-bar="${role}" style="width: ${hp}%"></div>
                </div>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 text-xs" data-bench-list="${role}">
            ${benchLabels}
        </div>
    `;
};

const renderMoves = (moves = []) => {
    return moves
        .map(
            (move) => `
                <form method="POST" data-battle-action-form>
                    <input type="hidden" name="_token" value="${escapeHtml(document.head.querySelector('meta[name="csrf-token"]')?.content || '')}" />
                    <input type="hidden" name="type" value="move">
                    <input type="hidden" name="slot" value="${move.slot}">
                    <button class="w-full px-3 py-3 rounded-lg border border-gray-200 hover:border-emerald-400 hover:shadow text-left js-battle-move js-battle-main-action" data-move-slot="${move.slot}">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">${escapeHtml(move.name)}</span>
                            <span class="text-xs uppercase text-gray-500">Slot ${move.slot}</span>
                        </div>
                        <p class="text-sm text-gray-600">${escapeHtml(move.category ? move.category.charAt(0).toUpperCase() + move.category.slice(1) : 'Physical')} • ${escapeHtml(move.type || 'Neutral')} • Power ${move.power}</p>
                    </button>
                </form>
            `,
        )
        .join('');
};

const renderTeamList = (monsters = [], activeId = null, role = 'you') => {
    if (!monsters.length) {
        return '<p class="text-sm text-gray-500">No team members.</p>';
    }

    return monsters
        .map((monster) => {
            const isActive = monster.id === activeId;
            const { name, hpText, fainted, placeholder } = resolveMonsterDisplay(monster, role);
            const badge = isActive
                ? '<span class="ml-2 text-emerald-300 text-xs bg-emerald-900/40 px-2 py-0.5 rounded-full">Active</span>'
                : '';
            const levelText = placeholder ? 'Lv ?' : `Lv ${monster.level ?? '?'}`;
            const displayName = placeholder && role === 'opponent' ? name : monster.name || name;

            return `
                <div class="flex items-center justify-between rounded px-3 py-2 ${
                    isActive
                        ? role === 'you'
                            ? 'ring-2 ring-emerald-400 bg-slate-800/60'
                            : 'ring-2 ring-rose-300 bg-white'
                        : role === 'you'
                          ? 'bg-slate-800/40'
                            : 'bg-gray-100'
                }">
                    <div class="flex items-center">
                        <span class="${fainted ? 'line-through opacity-70' : ''}">${escapeHtml(displayName)} (${escapeHtml(levelText)})</span>
                        ${badge}
                    </div>
                    <span class="${role === 'you' ? 'text-slate-200' : 'text-gray-700'}">${escapeHtml(hpText)}</span>
                </div>
            `;
        })
        .join('');
};

const isStateRenderable = (state, viewerId) => {
    if (!state || typeof state !== 'object') return false;

    const participants = state.participants;
    if (!participants) return false;

    const participant = participants[viewerId];
    if (!participant) return false;

    const monsters = participant.monsters;
    if (!Array.isArray(monsters) || monsters.length === 0) return false;

    const activeIndex = Number.isInteger(participant.active_index) ? participant.active_index : 0;
    const activeMonster = monsters[activeIndex];
    if (!activeMonster) return false;

    return 'moves' in activeMonster;
};

const renderCommands = (state, viewerId) => {
    const participant = state.participants?.[viewerId];
    const isActive = (state?.status || 'active') === 'active';
    const isYourTurn = isActive && (state.next_actor_id ?? null) === viewerId;
    const active = participant?.monsters?.[participant.active_index ?? 0];
    const bench = (participant?.monsters || []).filter((_, idx) => idx !== (participant?.active_index ?? 0));
    const healthyBench = bench.filter((monster) => monster.current_hp > 0);
    const isFainted = (active?.current_hp ?? 0) <= 0;

    const turnLabel = isYourTurn ? 'Your turn' : isActive ? 'Waiting for opponent' : 'Battle complete';
    const turnColor = isYourTurn ? 'text-emerald-600' : 'text-gray-500';

    if (!participant) {
        return `<div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Battle commands</h3><span class="text-sm ${turnColor}" data-turn-indicator>${escapeHtml(turnLabel)}</span></div><p class="text-sm text-gray-600">Battle state unavailable.</p>`;
    }

    if (!isActive) {
        return `<div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Battle commands</h3><span class="text-sm ${turnColor}" data-turn-indicator>${escapeHtml(turnLabel)}</span></div><p class="text-sm text-gray-600">Battle complete.</p>`;
    }

    if (!isYourTurn || !active) {
        return `<div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Battle commands</h3><span class="text-sm ${turnColor}" data-turn-indicator>${escapeHtml(turnLabel)}</span></div><p class="text-sm text-gray-600">Waiting for opponent action...</p>`;
    }

    const moveButtons = renderMoves(active.moves || []);
    const csrf = document.head.querySelector('meta[name="csrf-token"]')?.content || '';
    const validSwapTargets = healthyBench.filter((monster) => Number.isFinite(Number(monster.id)));
    const swapButtons = validSwapTargets.length
        ? `
            <div class="grid sm:grid-cols-2 gap-2" data-swap-options>
                ${validSwapTargets
                    .map(
                        (monster) => `
                            <form method="POST" class="w-full" data-battle-action-form>
                                <input type="hidden" name="_token" value="${escapeHtml(csrf)}" />
                                <input type="hidden" name="type" value="swap">
                                <input type="hidden" name="monster_instance_id" value="${Number(monster.id)}">
                                <button class="w-full px-3 py-2 rounded border border-gray-200 bg-white hover:border-indigo-400 text-left">
                                    <div class="font-semibold">${escapeHtml(monster.name)}</div>
                                    <p class="text-sm text-gray-600">Lv ${monster.level ?? '?'} • HP ${monster.current_hp ?? 0} / ${monster.max_hp ?? 0}</p>
                                </button>
                            </form>
                        `,
                    )
                    .join('')}
            </div>
        `
        : `<p class="text-xs text-gray-500">No healthy teammates to switch into.</p>`;

    if (isFainted) {
        return `
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Battle commands</h3>
                <span class="text-sm ${turnColor}" data-turn-indicator>Switch required</span>
            </div>
            <p class="text-sm text-gray-700">Your active monster has fainted. Choose a healthy teammate to continue the battle.</p>
            ${swapButtons}
        `;
    }

    return `
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Battle commands</h3>
            <span class="text-sm ${turnColor}" data-turn-indicator>${escapeHtml(turnLabel)}</span>
        </div>
        <div class="grid md:grid-cols-2 gap-3">${moveButtons}</div>
        <div class="mt-3">
            <p class="text-xs text-gray-600 mb-2">Switch to another monster:</p>
            ${swapButtons}
        </div>
    `;
};

const renderLog = (log = [], players = {}) => {
    if (!log.length) {
        return '<h2 class="text-xl font-semibold mb-3">Turn Log</h2><p class="text-gray-600">No turns recorded yet.</p>';
    }

    const items = log
        .map(
            (entry) => `
                <div class="border rounded p-3 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold">Turn ${entry.turn} by ${escapeHtml(players[entry.actor_user_id] || `User ${entry.actor_user_id}`)}</p>
                        <span class="text-xs text-gray-500">Action: ${escapeHtml(entry.action?.type || 'unknown')}${entry.action?.type === 'move' ? ` (Slot ${entry.action.slot})` : ''}</span>
                    </div>
                    <ul class="list-disc list-inside text-gray-600">
                        ${(entry.events || [])
                            .map((event) => `<li>${escapeHtml(event.type ? event.type.charAt(0).toUpperCase() + event.type.slice(1) : 'Event')} - ${escapeHtml(JSON.stringify(event))}</li>`)
                            .join('')}
                    </ul>
                </div>
            `,
        )
        .join('');

    return `<h2 class="text-xl font-semibold mb-3">Turn Log</h2><div class="space-y-3 text-sm">${items}</div>`;
};

const parseInitialState = (container) => {
    const stateEl = container.querySelector('[data-battle-initial-state]');
    if (!stateEl) return null;

    try {
        return JSON.parse(stateEl.textContent || '{}');
    } catch (error) {
        console.error('Unable to parse battle initial state', error);

        return null;
    }
};

/* =========================================================
   BAG UI + ITEM USE (no equipped-moves system yet)
   - loads /bag
   - POST /bag/use { item_id, monster_id }
   - updates HP bar/text on success when heal_hp
   - shows server message for teach_move (learned stored server-side)
   ========================================================= */

function getBattleRootEl() {
    return (
        document.getElementById('wild-battle-page') ||
        document.getElementById('battle-root') ||
        document.body
    );
}

function getActiveMonsterIdFromDom() {
    const root = getBattleRootEl();
    const raw = root?.dataset?.activeMonsterId || root?.dataset?.activeId || null;
    const parsed = raw != null ? Number(raw) : NaN;
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

// If DOM doesn’t provide it, try derive from live state
function getActiveMonsterIdFromState(battleState, viewerId) {
    try {
        const p = battleState?.participants?.[viewerId];
        const idx = p?.active_index ?? 0;
        const m = p?.monsters?.[idx];
        const id = m?.id;
        const parsed = id != null ? Number(id) : NaN;
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    } catch {
        return null;
    }
}

function updatePlayerHpUi(currentHp, maxHp) {
    const hpTextEl = document.querySelector('[data-player-hp-text]');
    const hpBarEl = document.querySelector('[data-player-hp-bar]');
    if (hpTextEl) hpTextEl.textContent = `HP ${currentHp} / ${maxHp}`;
    if (hpBarEl) {
        const pct = Math.max(
            0,
            Math.min(100, Math.floor((Number(currentHp) / Math.max(1, Number(maxHp))) * 100)),
        );
        hpBarEl.style.width = `${pct}%`;
    }
}

function ensureBagPlaceholders() {
    const listEl = document.querySelector('[data-bag-items]');
    const statusEl = document.querySelector('[data-bag-status]');
    return { listEl, statusEl };
}

function renderBagItems(items = []) {
    const { listEl } = ensureBagPlaceholders();
    if (!listEl) return;

    if (!items.length) {
        listEl.innerHTML = `<p class="text-sm text-gray-500">Your bag is empty.</p>`;
        return;
    }

    listEl.innerHTML = items
        .map(
            (i) => `
        <div class="flex items-center justify-between border rounded bg-white px-3 py-2">
          <div class="min-w-0">
            <div class="font-semibold text-sm truncate">${escapeHtml(i.name)}</div>
            <div class="text-xs text-gray-500 truncate">${escapeHtml(i.description ?? '')}</div>
            <div class="text-xs text-gray-500">Qty: <span data-bag-qty="${i.item_id}">${Number(i.quantity) || 0}</span></div>
          </div>
          <button
            type="button"
            class="px-3 py-2 rounded bg-slate-900 text-white text-sm hover:bg-slate-800"
            data-use-item="${i.item_id}"
            ${Number(i.quantity) > 0 ? '' : 'disabled'}
          >
            Use
          </button>
        </div>
      `,
        )
        .join('');
}

async function loadBagIntoUi() {
    const { listEl, statusEl } = ensureBagPlaceholders();
    if (!listEl) return;

    try {
        if (statusEl) statusEl.textContent = 'Loading bag...';
        const res = await axios.get('/bag', { headers: { Accept: 'application/json' } });
        const items = res.data?.items || [];
        renderBagItems(items);
        if (statusEl) statusEl.textContent = '';
    } catch (e) {
        if (statusEl) statusEl.textContent = 'Failed to load bag.';
        console.error('loadBagIntoUi error', e);
    }
}

async function useBagItemOnActiveMonster({ itemId, monsterId }) {
    const { statusEl } = ensureBagPlaceholders();

    try {
        if (statusEl) statusEl.textContent = 'Using item...';

        const res = await axios.post(
            '/bag/use',
            { item_id: itemId, monster_id: monsterId },
            { headers: { Accept: 'application/json' } },
        );

        const data = res.data || {};
        const result = data.result || {};

        // Status message
        if (statusEl) statusEl.textContent = result.message || 'Done.';

        // Update qty
        const qtyEl = document.querySelector(`[data-bag-qty="${itemId}"]`);
        if (qtyEl && data?.bag?.quantity != null) {
            qtyEl.textContent = String(data.bag.quantity);
        }

        // Update player HP UI if backend returned monster HP
        if (data?.monster?.current_hp != null && data?.monster?.max_hp != null) {
            updatePlayerHpUi(data.monster.current_hp, data.monster.max_hp);
        } else {
            // If backend doesn’t return monster snapshot, at least refresh bag list so UI stays sane
            // (Optional: you can also call fetchBattleState if you want a full UI refresh)
        }

        return true;
    } catch (e) {
        const msg =
            e?.response?.data?.result?.message ||
            e?.response?.data?.message ||
            'Item use failed.';
        if (statusEl) statusEl.textContent = msg;
        console.error('useBagItemOnActiveMonster error', e);
        return false;
    }
}

function bindBagUiEvents(getActiveMonsterIdFn) {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-use-item]');
        if (!btn) return;

        const itemId = Number(btn.getAttribute('data-use-item'));
        if (!Number.isFinite(itemId) || itemId <= 0) return;

        const monsterId = getActiveMonsterIdFn?.();
        if (!monsterId) {
            const { statusEl } = ensureBagPlaceholders();
            if (statusEl) statusEl.textContent = 'No active monster selected.';
            return;
        }

        useBagItemOnActiveMonster({ itemId, monsterId });
    });
}

/* ========================================================= */

export function initBattleLive(root = document) {
    const container = root.querySelector('[data-battle-live]');

    if (!container) {
        return;
    }

    const initial = parseInitialState(container);
    if (!initial) {
        return;
    }

    if (!container.dataset.soundsBound) {
        wireBattleSounds(container);
        container.dataset.soundsBound = '1';
    }

    let battleState = initial.state || {};
    let battleStatus = initial.battle?.status || 'active';
    let winnerId = initial.battle?.winner_user_id || null;
    let players = initial.players || {};
    const viewerId = Number(initial.viewer_id || container.dataset.userId || 0);
    const battleId = container.dataset.battleId;
    const actUrl = container.dataset.actUrl;
    const statusEl = container.querySelector('[data-battle-live-status]');
    const statusTextEl = container.querySelector('[data-battle-status-text]');
    const winnerEl = container.querySelector('[data-battle-winner]');
    const nextActorEl = container.querySelector('[data-next-actor]');
    const modeEl = container.querySelector('[data-battle-mode]');
    const yourSideContainer = container.querySelector('[data-side="you"]');
    const opponentSideContainer = container.querySelector('[data-side="opponent"]');
    const yourTeamList = container.querySelector('[data-team-list="you"]');
    const opponentTeamList = container.querySelector('[data-team-list="opponent"]');
    const commandsContainer = container.querySelector('[data-battle-commands]');
    const commandsBody = commandsContainer?.querySelector('[data-battle-commands-body]');
    const logContainer = container.querySelector('[data-battle-log]');
    const waitingOverlay = container.querySelector('[data-battle-waiting-overlay]');

    const opponentId = initial.battle?.player1_id === viewerId ? initial.battle?.player2_id : initial.battle?.player1_id;
    let pollHandle = null;
    let watchdogHandle = null;
    let awaitingEvent = false;
    let eventReceived = false;
    let subscriptionSucceeded = false;
    let subscriptionErrored = false;
    let lastUpdateAt = Date.now();
    let hasScheduledCompletion = false;
    let currentEchoState = window.__echoConnectionState || (window.Echo ? 'connecting' : 'disconnected');
    let initialEventTimeout = null;
    let waitingForResolution = false;
    let lastHydrationRequest = 0;
    let hydrationRetryTimer = null;

    // ---- Bag UI: compute active monster id dynamically (DOM -> state fallback)
    const getActiveMonsterId = () => {
        const fromDom = getActiveMonsterIdFromDom();
        if (fromDom) return fromDom;
        return getActiveMonsterIdFromState(battleState, viewerId);
    };

    const updateHeader = () => {
        if (statusTextEl) {
            statusTextEl.textContent = battleStatus.charAt(0).toUpperCase() + battleStatus.slice(1);
        }

        if (winnerEl) {
            if (winnerId) {
                winnerEl.classList.remove('hidden');
                winnerEl.textContent = `Winner: ${players[winnerId] || `User ${winnerId}`}`;
            } else {
                winnerEl.classList.add('hidden');
                winnerEl.textContent = '';
            }
        }

        if (nextActorEl) {
            nextActorEl.textContent = battleState.next_actor_id ?? 'Unknown';
        }

        if (modeEl) {
            modeEl.textContent = (initial.battle?.mode || 'ranked').charAt(0).toUpperCase() + (initial.battle?.mode || 'ranked').slice(1);
        }
    };

    const render = () => {
        const yourSide = battleState.participants?.[viewerId];
        const opponentSide = opponentId ? battleState.participants?.[opponentId] : null;
        const yourActiveId = yourSide?.monsters?.[yourSide?.active_index ?? 0]?.id ?? null;
        const opponentActiveId = opponentSide?.monsters?.[opponentSide?.active_index ?? 0]?.id ?? null;

        if (yourSideContainer) {
            yourSideContainer.innerHTML = '<p class="text-xs uppercase tracking-wide text-slate-300">You</p>' + renderSide(yourSide, 'you');
        }

        if (opponentSideContainer) {
            opponentSideContainer.innerHTML = '<p class="text-xs uppercase tracking-wide text-gray-500">Opponent</p>' + renderSide(opponentSide, 'opponent');
        }

        if (yourTeamList) {
            yourTeamList.innerHTML = renderTeamList(yourSide?.monsters || [], yourActiveId, 'you');
        }

        if (opponentTeamList) {
            opponentTeamList.innerHTML = renderTeamList(opponentSide?.monsters || [], opponentActiveId, 'opponent');
        }

        if (commandsBody) {
            commandsBody.innerHTML = renderCommands({ ...battleState, status: battleStatus }, viewerId);
        } else if (commandsContainer) {
            commandsContainer.innerHTML = renderCommands({ ...battleState, status: battleStatus }, viewerId);
        }

        if (logContainer) {
            logContainer.innerHTML = renderLog(battleState.log || [], players);
        }

        wireBattleSounds(container);

        updateHeader();
        toggleControls(waitingForResolution);
    };

    const setStatus = (message) => {
        if (statusEl) {
            statusEl.textContent = message;
        }
    };

    const toggleControls = (disabled) => {
        const controlsRoot = commandsBody || commandsContainer;
        if (!controlsRoot) {
            return;
        }

        controlsRoot.querySelectorAll('button, select').forEach((control) => {
            control.disabled = disabled;
        });
    };

    const setWaitingState = (waiting) => {
        waitingForResolution = waiting;
        toggleControls(waiting);

        const battleRoot = document.getElementById('battle-root');
        if (waitingOverlay && !isPvpMode(battleRoot)) {
            waitingOverlay.classList.toggle('hidden', !waiting);
            waitingOverlay.classList.toggle('is-hidden', !waiting);
        } else if (waitingOverlay && isPvpMode(battleRoot)) {
            setPvpInputLocked(pvpInputLocked || waiting);
        }
    };

    const scheduleCompletion = () => {
        if (hasScheduledCompletion || battleStatus === 'active') {
            return;
        }

        hasScheduledCompletion = true;

        window.setTimeout(() => {
            if (window.location.pathname.startsWith('/pvp')) {
                if (typeof window.refreshPvpPanel === 'function') {
                    window.refreshPvpPanel();
                } else {
                    window.location.href = '/pvp';
                }
            } else {
                window.location.href = '/pvp';
            }
        }, 1500);
    };

    const submitAction = (form) => {
        if (!actUrl) {
            return;
        }

        const formData = new FormData(form);

        axios
            .post(actUrl, formData)
            .then(() => setStatus('Action sent. Waiting for result...'))
            .catch(() => {
                setStatus('Could not submit action right now.');
                setWaitingState(false);
            });
    };

    function applyUpdate(payload, { fromEvent = false } = {}) {
        if (!payload) {
            return;
        }

        lastUpdateAt = Date.now();

        if (fromEvent && awaitingEvent) {
            awaitingEvent = false;
            eventReceived = true;
        }

        if (fromEvent) {
            eventReceived = true;
            console.info('Battle update received via broadcast', {
                battle_id: payload?.battle_id,
                status: payload?.status,
                next_actor_id: payload?.next_actor_id,
            });
            attemptStopPolling();
        }

        if (payload.players) {
            players = payload.players;
        }

        const viewerSpecificState = payload.viewer_state || payload.viewer_states?.[viewerId];
        const incomingState = viewerSpecificState || payload.state;
        const isRenderable = isStateRenderable(incomingState, viewerId);

        if (incomingState && isRenderable) {
            const merged = {
                ...battleState,
                ...incomingState,
                battle: {
                    ...(battleState?.battle || {}),
                    ...(incomingState?.battle || {}),
                },
            };

            if (!incomingState?.battle?.player_monsters && battleState?.battle?.player_monsters) {
                merged.battle.player_monsters = battleState.battle.player_monsters;
            }

            battleState = merged;
        } else if (incomingState && !isRenderable) {
            console.warn('Received non-renderable battle state; preserving existing UI state.');
            const now = Date.now();
            if (now - lastHydrationRequest > 2000) {
                lastHydrationRequest = now;
                fetchBattleState();
            }
        }
        battleStatus = payload.status || payload.battle?.status || battleStatus;
        winnerId = payload.winner_user_id ?? payload.battle?.winner_user_id ?? winnerId;

        render();

        const timerState = {
            ...(payload.state || battleState),

            // MINIMAL CHANGE: ensure viewer_id is always present for applyPvpTurnUi / refreshPvpTimer fallbacks
            viewer_id: viewerId,

            // keep root fields like server_now / viewer_user_id if they exist on payload
            server_now: (payload.server_now ?? (payload.state || battleState)?.server_now),
            viewer_user_id: (payload.viewer_user_id ?? (payload.state || battleState)?.viewer_user_id),
            user_id: (payload.user_id ?? (payload.state || battleState)?.user_id),
            battle: {
                ...((payload.state || battleState)?.battle || {}),
                ...(payload.battle || {}),
            },
        };

        refreshPvpTimer(timerState);

        // MINIMAL CHANGE: pass explicit viewerId so overlay never disappears due to missing viewer id in payload state
        applyPvpTurnUi(timerState, viewerId);

        const isActive = battleStatus === 'active';
        const isYourTurn = isActive && (battleState.next_actor_id ?? null) === viewerId;
        setStatus(isActive ? 'Live: waiting for next move.' : 'Battle finished.');

        if (waitingForResolution && (isYourTurn || !isActive)) {
            setWaitingState(false);
        }

        if (!isActive) {
            scheduleCompletion();
        }
    }

    function fetchBattleState({ allowRetry = true } = {}) {
        return axios
            .get(`/battles/${battleId}/state`, { headers: { Accept: 'application/json' } })
            .then((response) => {
                const data = response.data || {};
                const incoming = {
                    status: data.battle?.status,
                    winner_user_id: data.battle?.winner_user_id,
                    players: data.players,
                };

                if (data.state && !isStateRenderable(data.state, viewerId)) {
                    if (hydrationRetryTimer) {
                        clearTimeout(hydrationRetryTimer);
                    }

                    if (allowRetry) {
                        hydrationRetryTimer = window.setTimeout(() => {
                            hydrationRetryTimer = null;
                            fetchBattleState({ allowRetry: false });
                        }, 250);
                    } else {
                        setStatus('Sync issue — refresh recommended');
                    }

                    applyUpdate(incoming);
                    return;
                }

                hydrationRetryTimer = null;

                applyUpdate({
                    ...incoming,
                    state: data.state,
                });
            })
            .catch((error) => {
                console.error('Battle polling failed', error);
                setStatus('Unable to sync battle right now.');
            });
    }

    const attemptStopPolling = (force = false) => {
        const canStop = force || (subscriptionSucceeded && eventReceived);

        if (!canStop) {
            return;
        }

        if (pollHandle) {
            clearInterval(pollHandle);
            pollHandle = null;
        }

        awaitingEvent = false;
    };

    const startPolling = ({ expectEvent = false } = {}) => {
        if (!battleId || pollHandle) {
            return;
        }

        awaitingEvent = expectEvent;
        setStatus('Live updates (polling)...');
        fetchBattleState();
        pollHandle = window.setInterval(fetchBattleState, 2000);
    };

    const startWatchdog = () => {
        if (watchdogHandle || !battleId) {
            return;
        }

        watchdogHandle = window.setInterval(() => {
            const isEchoConnected = window.Echo && currentEchoState === 'connected';

            if (!isEchoConnected) {
                return;
            }

            if (Date.now() - lastUpdateAt > 8000) {
                startPolling({ expectEvent: true });
            }
        }, 3000);
    };

    container.addEventListener('submit', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLFormElement) || !target.matches('[data-battle-action-form]')) {
            return;
        }

        event.preventDefault();
        if (waitingForResolution) {
            return;
        }

        const waitOverlay = document.getElementById('pvp-wait-overlay');
        if (waitOverlay && !waitOverlay.classList.contains('is-hidden')) {
            return;
        }

        setWaitingState(true);
        setStatus('Submitting action...');
        submitAction(target);
    });

    // ---- Bag UI init: bind once + load items
    if (!window.__bagUiBound) {
        window.__bagUiBound = true;
        bindBagUiEvents(getActiveMonsterId);
    }
    loadBagIntoUi();

    render();
    refreshPvpTimer(battleState);

    // MINIMAL CHANGE: pass explicit viewerId on initial call too
    applyPvpTurnUi(battleState, viewerId);

    if (battleStatus !== 'active') {
        scheduleCompletion();
    }

    const shouldPoll = () => !window.Echo || currentEchoState === 'disconnected' || subscriptionErrored;

    if (window.Echo && battleId) {
        setStatus('Listening for opponent actions...');
        const channel = window.Echo.private(`battles.${battleId}`);
        const subscription = channel?.subscription;

        if (subscription?.bind) {
            subscription.bind('pusher:subscription_succeeded', () => {
                console.info('Battle live subscription succeeded', { channel: subscription.name });
                subscriptionSucceeded = true;
                subscriptionErrored = false;
                setStatus('Live: subscribed to battle channel.');
                attemptStopPolling();
            });

            subscription.bind('pusher:subscription_error', (status) => {
                console.error('Battle live subscription error', status);
                subscriptionErrored = true;
                setStatus('Live updates unavailable (subscription error).');
                startPolling({ expectEvent: true });
            });
        }

        // Subscribed for both wild and PvP battles; BattleUpdated on "battles.{id}" refreshes each viewer's UI state
        channel.listen('.BattleUpdated', (payload) => {
            console.log('BattleUpdated payload keys', Object.keys(payload || {}));
            console.log(
                'BattleUpdated player_monsters?',
                payload?.battle?.player_monsters,
                payload?.player_monsters,
            );
            applyUpdate(payload, { fromEvent: true });
        });
    }

    if (shouldPoll()) {
        startPolling();
    }

    if (!initialEventTimeout && battleStatus === 'active') {
        initialEventTimeout = window.setTimeout(() => {
            if (!eventReceived && battleStatus === 'active') {
                console.warn('No BattleUpdated event received within 6 seconds; starting fallback polling.');
                startPolling({ expectEvent: true });
            }
        }, 6000);
    }

    startWatchdog();

    window.addEventListener('echo:status', (event) => {
        const state = event.detail?.state;
        if (!state) return;

        currentEchoState = state;
        if (state === 'connected') {
            attemptStopPolling();
            setStatus('Listening for opponent actions...');
        } else if (shouldPoll()) {
            startPolling();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => initBattleLive());
