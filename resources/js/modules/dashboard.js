import axios from 'axios';

function normalizeMonsters(rawMonsters = []) {
    return (rawMonsters || []).map((monster) => ({
        ...monster,
        level: Number(monster.level || 0),
        current_hp: Number(monster.current_hp || 0),
        max_hp: Number(monster.max_hp || 0),
        team_slot: monster.team_slot ? Number(monster.team_slot) : null,
    }));
}

function setStatus(container, message, type = 'success') {
    const status = container.querySelector('[data-team-status]');
    if (!status) return;

    status.textContent = message || '';
    status.classList.toggle('hidden', !message);
    status.classList.toggle('text-green-700', type === 'success');
    status.classList.toggle('text-red-700', type === 'error');
}

function renderTeamSlots(container, monsters) {
    const slotsContainer = container.querySelector('[data-team-slots]');
    if (!slotsContainer) return;

    slotsContainer.innerHTML = '';

    const fragment = document.createDocumentFragment();

    for (let i = 1; i <= 6; i += 1) {
        const occupant = monsters.find((monster) => monster.team_slot === i);
        const card = document.createElement('div');
        card.className = 'border rounded p-3 flex flex-col gap-2';
        card.dataset.slotCard = '';
        card.dataset.slot = String(i);
        card.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="font-semibold">Slot ${i}</div>
                ${occupant ? '<span class="text-xs text-teal-700 font-semibold">In Team</span>' : '<span class="text-xs text-gray-500">Empty</span>'}
            </div>
            ${occupant ? `
                <div class="text-sm text-gray-700">
                    <p class="font-semibold">${occupant.nickname || occupant.species?.name || 'Monster'}</p>
                    <p>Species: ${occupant.species?.name || 'Unknown'}</p>
                    <p>Level ${occupant.level} • HP ${occupant.current_hp}/${occupant.max_hp}</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded" data-action="clear-slot" data-slot="${i}">Clear Slot</button>
                </div>
            ` : '<p class="text-sm text-gray-500">Choose a monster below to fill this slot.</p>'}
        `;

        fragment.appendChild(card);
    }

    slotsContainer.appendChild(fragment);
}

function renderOwnedMonsters(container, monsters) {
    const list = container.querySelector('[data-monster-list]');
    const count = container.querySelector('[data-monster-count]');

    if (count) {
        count.textContent = monsters.length;
    }

    if (!list) return;

    list.innerHTML = '';

    if (!monsters.length) {
        const empty = document.createElement('p');
        empty.className = 'text-gray-600';
        empty.textContent = 'No monsters captured yet. Resolve encounters to grow your party.';
        list.appendChild(empty);
        return;
    }

    const fragment = document.createDocumentFragment();

    monsters.forEach((monster) => {
        const card = document.createElement('div');
        card.className = 'border rounded p-3 flex flex-col gap-2';
        card.dataset.monsterCard = '';
        card.dataset.monsterId = monster.id;
        card.innerHTML = `
            <div class="flex justify-between gap-3">
                <div>
                    <p class="font-semibold">${monster.nickname || monster.species?.name || 'Monster'}</p>
                    <p class="text-sm text-gray-600">Species: ${monster.species?.name || 'Unknown'}</p>
                </div>
                <div class="text-sm text-gray-500">${monster.team_slot ? `Slot ${monster.team_slot}` : 'Not in team'}</div>
            </div>
            <p class="text-sm text-gray-700">Level ${monster.level} • HP ${monster.current_hp}/${monster.max_hp}</p>
            <div class="flex flex-wrap gap-2 items-center">
                <label class="text-sm text-gray-600" for="monster-slot-${monster.id}">Team Slot</label>
                <select
                    id="monster-slot-${monster.id}"
                    class="border rounded px-2 py-1 text-sm"
                    data-slot-select
                    data-monster-id="${monster.id}"
                >
                    ${Array.from({ length: 6 }, (_, index) => index + 1)
                        .map((slot) => `<option value="${slot}" ${monster.team_slot === slot ? 'selected' : ''}>Slot ${slot}</option>`)
                        .join('')}
                </select>
                <button type="button" class="px-3 py-1 bg-teal-600 hover:bg-teal-500 text-white rounded text-sm" data-action="assign-slot" data-monster-id="${monster.id}">
                    ${monster.team_slot ? 'Update Slot' : 'Add to Team'}
                </button>
                ${monster.team_slot ? `<button type="button" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded text-sm" data-action="remove-from-team" data-monster-id="${monster.id}" data-slot="${monster.team_slot}">Remove</button>` : ''}
            </div>
        `;

        fragment.appendChild(card);
    });

    list.appendChild(fragment);
}

function updateUi(container, monsters) {
    renderTeamSlots(container, monsters);
    renderOwnedMonsters(container, monsters);
}

function initDashboard() {
    const container = document.getElementById('monster-dashboard');
    if (!container) return;

    const setUrl = container.dataset.teamSetUrl;
    const clearUrl = container.dataset.teamClearUrl;
    const userId = Number(container.dataset.userId || 0);

    let monsters = normalizeMonsters(JSON.parse(container.dataset.monsters || '[]'));

    const handleResponse = (response) => {
        const updated = response?.data?.monsters;
        if (Array.isArray(updated)) {
            monsters = normalizeMonsters(updated);
            updateUi(container, monsters);
        }
    };

    updateUi(container, monsters);

    container.addEventListener('click', async (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;

        const action = target.dataset.action;
        if (!action) return;

        if (action === 'assign-slot') {
            const monsterId = Number(target.dataset.monsterId || 0);
            const select = container.querySelector(`select[data-slot-select][data-monster-id="${monsterId}"]`);
            const slot = Number(select?.value || 0);

            if (!monsterId || !slot || !setUrl) {
                return;
            }

            try {
                const response = await axios.post(setUrl, { player_monster_id: monsterId, slot }, { headers: { Accept: 'application/json' } });
                setStatus(container, 'Team updated successfully.');
                handleResponse(response);
            } catch (error) {
                const message = error.response?.data?.message || error.response?.data?.errors?.slot?.[0] || 'Unable to update the team right now.';
                setStatus(container, message, 'error');
            }
        } else if (action === 'remove-from-team' || action === 'clear-slot') {
            const slot = Number(target.dataset.slot || 0);
            if (!slot || !clearUrl) {
                return;
            }

            try {
                const response = await axios.post(clearUrl, { slot }, { headers: { Accept: 'application/json' } });
                setStatus(container, 'Slot cleared.');
                handleResponse(response);
            } catch (error) {
                const message = error.response?.data?.message || 'Unable to clear that slot right now.';
                setStatus(container, message, 'error');
            }
        }
    });

    if (window.Echo && userId) {
        window.Echo.private(`users.${userId}`).listen('.UserMonstersUpdated', (event) => {
            if (!event?.monsters) return;
            monsters = normalizeMonsters(event.monsters);
            updateUi(container, monsters);
        });
    }
}

initDashboard();
