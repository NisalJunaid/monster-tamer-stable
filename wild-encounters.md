# Wild Encounters & Battle System – Developer Guide  
*Dev Space Game – Internal Documentation*

---

## Overview

Wild encounters are triggered based on the player’s geolocation and the zone they enter. When an encounter is issued, the player receives an **Encounter Ticket**, which can be viewed on the Encounters page or queried via API.

Wild battles are turn-based engagements between the player’s monster party and a single wild monster. Battles operate through a persistent `battle_state` stored on the encounter ticket and updated after each move.

This document outlines all relevant components: routes, controllers, services, events, Blade views, and frontend JS.

---

# 1. Routes

## 1.1 Web Routes (`routes/web.php`)

These routes power the browser-based UI for encounters and wild battles:

| Method | URI                                       | Controller                             | Purpose                    |
|--------|-------------------------------------------|----------------------------------------|----------------------------|
| GET    | `/encounters`                            | `Web\EncounterController@index`       | Encounters list UI         |
| POST   | `/encounters/location`                   | `Web\EncounterController@update`      | Update location, issue tickets |
| POST   | `/encounters/{ticket}/resolve`           | `Web\EncounterController@resolve`     | Resolve capture            |
| GET    | `/encounters/{ticket}/battle`            | `Web\WildBattleController@show`       | Wild battle UI             |
| POST   | `/encounters/{ticket}/battle/move`       | `Web\WildBattleController@move`       | Perform a move             |
| POST   | `/encounters/{ticket}/battle/switch`     | `Web\WildBattleController@switchActive` | Switch monsters          |
| POST   | `/encounters/{ticket}/battle/tame`       | `Web\WildBattleController@tame`       | Capture (tame) attempt     |
| POST   | `/encounters/{ticket}/battle/run`        | `Web\WildBattleController@run`        | Flee / end battle          |

---

## 1.2 API Routes (`routes/api.php`)

These routes are for programmatic access (mobile client, external consumers, etc.):

| Method | URI                                                | Controller                  | Purpose                    |
|--------|----------------------------------------------------|-----------------------------|----------------------------|
| POST   | `/api/location/update`                            | `LocationController@update` | Update geolocation, issue tickets |
| GET    | `/api/encounters/current`                         | `EncounterController@current` | Returns active encounters |
| POST   | `/api/encounters/{ticket}/resolve-capture`        | `EncounterController@resolveCapture` | Capture resolution |
| GET    | `/api/encounters/{ticket}/battle`                 | `API\WildBattleController@show` | Get battle state      |
| POST   | `/api/encounters/{ticket}/battle/move`            | `API\WildBattleController@move` | Player move           |
| POST   | `/api/encounters/{ticket}/battle/switch`          | `API\WildBattleController@switchActive` | Switch active monster |
| POST   | `/api/encounters/{ticket}/battle/tame`            | `API\WildBattleController@tame` | Tame (capture) attempt |
| POST   | `/api/encounters/{ticket}/battle/run`             | `API\WildBattleController@run` | End battle            |

---

# 2. Controllers

## 2.1 Encounter Flow Controllers

### Web\EncounterController

**File:** `app/Http/Controllers/Web/EncounterController.php`

Handles the encounters UI workflow:

- `index(Request $request)`  
  - Renders the encounters list view with:
    - Current player location
    - Active encounter tickets
- `update(LocationUpdateRequest $request)`  
  - Validates & updates player location
  - Ensures encounter tickets exist via `EncounterService`
  - Broadcasts updated wild encounters
  - Returns JSON or redirects (depending on request)
- `resolve(Request $request, EncounterTicket $ticket)`  
  - Resolves capture attempts via `EncounterService::resolveCapture()`
  - Redirects with capture result message

---

### API\EncounterController

**File:** `app/Http/Controllers/EncounterController.php`

- `current(Request $request)`  
  - Returns current encounter tickets for the user:
    - `encounters`: collection of tickets
    - `encounter`: the current/first ticket
- `resolveCapture(Request $request, EncounterTicket $ticket)`  
  - Attempts a capture using `EncounterService::resolveCapture()`
  - Returns JSON:
    - `success`
    - `roll`
    - `threshold`

---

### LocationController (API)

**File:** `app/Http/Controllers/LocationController.php`

- `update(Request $request)`  
  - Validates player geolocation input
  - Updates `PlayerLocation`
  - Ensures encounters via `EncounterService`
  - Broadcasts new encounters
  - Returns JSON:
    - `location`
    - `encounters` (all)
    - `encounter` (current)

---

## 2.2 Wild Battle Controllers

### Web\WildBattleController

**File:** `app/Http/Controllers/Web/WildBattleController.php`

- `show(Request $request, EncounterTicket $ticket)`  
  - Starts or loads the wild battle via `WildBattleService::start()`
  - Renders Blade view `encounters.battle` with:
    - `ticket`
    - `battle` (battle state)
- `move(Request $request, EncounterTicket $ticket)`  
  - Validates selected move style (`style`)
  - Calls `WildBattleService::actMove()`
  - Returns JSON:
    - Updated `battle`
    - Updated `ticket`
- `switchActive(Request $request, EncounterTicket $ticket)`  
  - Validates selected party monster
  - Calls `WildBattleService::actSwitch()`
  - Returns updated JSON state
- `tame(Request $request, EncounterTicket $ticket)`  
  - Calls `WildBattleService::attemptTame()`
  - Returns JSON:
    - `battle`
    - `ticket`
    - `chance`
    - `roll`
    - `success`
- `run(Request $request, EncounterTicket $ticket)`  
  - Calls `WildBattleService::run()`
  - Ends battle and resolves ticket
  - Returns updated JSON

---

### API\WildBattleController

**File:** `app/Http/Controllers/API/WildBattleController.php`

JSON parallel of the web controller:

- `show(Request $request, EncounterTicket $ticket)`
- `move(Request $request, EncounterTicket $ticket)`
- `switchActive(Request $request, EncounterTicket $ticket)`
- `tame(Request $request, EncounterTicket $ticket)`
- `run(Request $request, EncounterTicket $ticket)`

Used by non-Blade clients (e.g. mobile).

---

# 3. Domain Services

## 3.1 EncounterService (Geolocation + Spawning)

**File:** `app/Domain/Geo/EncounterService.php`

Responsibilities:

- Manage encounter tickets:
  - `activeTickets(User $user)`
  - `currentTicket(User $user)`
- Issue/ensure encounter tickets based on zones:
  - `ensureTickets(User $user, PlayerLocation $location)`
  - `ensureTicketsForZone(User $user, Zone $zone)`
- Spawn monsters using deterministic seeded random:
  - `seededInt(...)`
- Compute wild monster HP:
  - `calculateEncounterHp(...)`
- Capture resolution:
  - `resolveCapture(EncounterTicket $ticket, User $user)`
- Broadcasting:
  - `broadcastWildEncounters(User $user)` → fires `WildEncountersUpdated` event

This service is the core of “where/when does an encounter appear”.

---

## 3.2 WildBattleService (Battle Engine)

**File:** `app/Domain/Encounters/WildBattleService.php`

Responsibilities:

### Battle Lifecycle

- `start(User $user, EncounterTicket $ticket)`  
  - Initializes `battle_state` for a ticket
  - Builds player party
  - Builds wild monster stats and starting HP
- `persistBattle(EncounterTicket $ticket, array $battle)`  
  - Persists `battle_state` JSON
  - Syncs `current_hp` / `max_hp` on `EncounterTicket`
- `broadcast(User $user, EncounterTicket $ticket, array $battle)`  
  - Emits `WildBattleUpdated` with full state

### Player Actions

- `actMove(User $user, EncounterTicket $ticket, string $style)`  
  - Applies move damage (monster/martial)
  - Applies type multipliers and random variation
  - Can resolve battle (KO, etc.)
- `actSwitch(User $user, EncounterTicket $ticket, int $playerMonsterId)`  
  - Switches active player monster
  - Can trigger wild’s turn
- `attemptTame(User $user, EncounterTicket $ticket)`  
  - Computes capture chance from:
    - Wild HP %
    - Species capture rate
  - Performs random roll
  - Updates encounter status on success/failure
- `run(User $user, EncounterTicket $ticket)`  
  - Ends battle and resolves ticket as “fled”

### Core Mechanics Helpers

- `calculateDamage(...)`
- `typeMultiplier(...)`

These helpers encapsulate the game’s combat math.

---

# 4. Events & Broadcasting

Events are broadcast on private Echo channels of the form `users.{id}`.

| Event                   | File                                   | Fires When                             | Consumed By             |
|-------------------------|----------------------------------------|----------------------------------------|-------------------------|
| `EncounterIssued`       | `app/Events/EncounterIssued.php`       | A new encounter ticket is created      | (Optional) UI / debug   |
| `WildEncountersUpdated` | `app/Events/WildEncountersUpdated.php` | Encounter list changes for a user      | `resources/js/modules/encounters.js` |
| `WildBattleUpdated`     | `app/Events/WildBattleUpdated.php`     | Wild battle state is updated           | `resources/js/modules/wild_battle.js` |

Typical channel name: `private("users.{userId}")`.

---

# 5. User Interface (Blade Views)

## 5.1 Encounters Page (`resources/views/encounters/index.blade.php`)

Responsibilities:

- Render Encounters page:
  - Location update form
  - “Use Browser Location” button
- Show active encounter cards:
  - Species name
  - Level
  - Zone
  - HP (current / max)
  - Expiry
- Include Battle link template:
  - `data-battle-template` with route to `encounters.battle.show`
- JS hooks:
  - Data attributes for:
    - `data-update-url`
    - `data-user-id`
  - Used by `encounters.js` to:
    - POST location updates
    - Render encounter list from broadcasts

---

## 5.2 Wild Battle Page (`resources/views/encounters/battle.blade.php`)

Responsibilities:

- Render full wild battle UI:

### Player Panel

- Active monster:
  - Name
  - Level
  - Current / max HP with HP bar
- Party list:
  - Switchable monsters

### Wild Panel

- Wild monster:
  - Species
  - Level
  - HP and HP bar
- Flavor text describing encounter

### Action Tabs

- Tabs (wired via data attributes):
  - Move
  - Bag (future use)
  - Switch
  - Tame
  - Run

### Action Panels

- Move:
  - Buttons for each move, with `data-move-style`
- Switch:
  - List of party monsters with `data-switch-list`
- Tame:
  - Tame button; success/failure shown in log/UI
- Run:
  - Ends battle and redirects back to `/encounters`

### Battle Log

- Incremental list of:
  - Player actions
  - Wild actions
  - Battle outcomes

### Bootstrapped Data

The root container (e.g. `#wild-battle-page`) exposes URLs & initial state:

- `data-move-url`
- `data-switch-url`
- `data-tame-url`
- `data-run-url`
- `data-encounters-url`
- `data-user-id`
- `data-ticket-id`

Plus a script tag containing JSON:

```html
<script type="application/json" data-wild-battle-state>
  <!-- initial battle state JSON -->
</script>
