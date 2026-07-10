# Tasks: Responsive Mobile Layout

**Input**: Design documents from `/specs/011-responsive-layout/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, quickstart.md

**Tests**: Tests are mandatory. Generate test tasks before implementation tasks for every applicable story and shared foundation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and baseline dependency support for the responsive layout work

- [X] T001 Add `@ng-bootstrap/ng-bootstrap` to `frontend/package.json` and refresh `frontend/package-lock.json` so the shell can use ng-bootstrap overlay primitives

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared support that MUST be ready before the user story work starts

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 [P] Create a shared mobile viewport helper in `frontend/tests/e2e/support/responsive-layout.ts` and import it from `frontend/tests/e2e/responsive-layout.spec.ts` so responsive Playwright checks use the same screen setup

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Mobile Menu Overlay (Priority: P1) 🎯 MVP

**Goal**: Make the lateral navigation behave as a dismissible overlay on constrained screens while preserving the current shell behavior

**Independent Test**: Open the app at a mobile viewport, open the lateral menu, and confirm it overlays the content and closes cleanly without losing the current route

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T003 [P] [US1] Add failing Angular unit coverage for overlay menu state and narrow-screen switching in `frontend/src/app/core/layout/app-shell.component.spec.ts`
- [X] T004 [P] [US1] Add failing Playwright coverage for opening and dismissing the lateral menu overlay on mobile widths in `frontend/tests/e2e/responsive-layout.spec.ts`

### Implementation for User Story 1

- [X] T005 [US1] Update `frontend/src/app/core/layout/app-shell.component.ts` to wire ng-bootstrap overlay behavior and keep the existing route and user actions intact
- [X] T006 [US1] Update `frontend/src/app/core/layout/app-shell.component.html` to render the lateral menu as an overlay-aware Bootstrap structure with the current Italian labels and controls
- [X] T007 [US1] Update `frontend/src/app/core/layout/app-shell.component.scss` to preserve the current shell styling while preventing horizontal overflow on constrained screens

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Contained Chat Layout (Priority: P2)

**Goal**: Keep the chat conversation list contained inside the visible layout so it scrolls internally instead of overflowing the page

**Independent Test**: Open the chat page on a small screen, load a long conversation list, and confirm the list remains scrollable within its own area while the rest of the page stays usable

### Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T008 [P] [US2] Add failing Angular unit coverage for contained conversation-list layout and no-horizontal-overflow behavior in `frontend/src/app/features/chat/chat-page.component.spec.ts`
- [X] T009 [P] [US2] Extend `frontend/tests/e2e/responsive-layout.spec.ts` with a long conversation-list overflow scenario on a mobile viewport

### Implementation for User Story 2

- [X] T010 [US2] Update `frontend/src/app/features/chat/chat-page.component.html` to keep the history rail, message area, citations, and composer inside Bootstrap layout containers
- [X] T011 [US2] Update `frontend/src/app/features/chat/chat-page.component.scss` to make the history list scroll internally and stop the chat page from exceeding the viewport

**Checkpoint**: At this point, User Story 2 should be fully functional and testable independently

---

## Phase 5: User Story 3 - Stable Mobile Interaction (Priority: P3)

**Goal**: Keep the mobile layout readable and stable while users switch between navigation and chat content or rotate the viewport

**Independent Test**: Use the app on a phone-sized viewport, rotate the viewport, and confirm menu dismissal, focus flow, and chat readability stay stable without clipped controls or horizontal scrolling

### Tests for User Story 3 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T012 [P] [US3] Extend `frontend/tests/e2e/responsive-layout.spec.ts` with viewport rotation, overlay dismissal, and stable reflow coverage
- [X] T013 [P] [US3] Add responsive regression assertions in `frontend/src/app/core/layout/app-shell.component.spec.ts` and `frontend/src/app/features/chat/chat-page.component.spec.ts` for accessible labels and focus-friendly behavior

### Implementation for User Story 3

- [X] T014 [US3] Refine `frontend/src/app/core/layout/app-shell.component.html` and `frontend/src/app/core/layout/app-shell.component.scss` to keep overlay dismissal obvious and touch targets usable on small screens
- [X] T015 [US3] Refine `frontend/src/app/features/chat/chat-page.component.html` and `frontend/src/app/features/chat/chat-page.component.scss` to preserve readable spacing and accessible chat controls in portrait and landscape

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T016 [P] Validate the final responsive behavior against `specs/011-responsive-layout/quickstart.md` and update the quickstart if final verification notes change
- [X] T017 [P] Run the responsive layout test suite and update `frontend/tests/e2e/responsive-layout.spec.ts`, `frontend/src/app/core/layout/app-shell.component.spec.ts`, and `frontend/src/app/features/chat/chat-page.component.spec.ts` if any final assertions need tightening

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - May build on the same shell conventions but should remain independently testable
- **User Story 3 (P3)**: Can start after Foundational (Phase 2) - May refine the same responsive behaviors but should remain independently testable

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Shared dependency work before feature-specific adjustments
- Core layout structure before styling polish
- Story complete before moving to the next priority

### Parallel Opportunities

- Setup dependency updates and e2e helper scaffolding can run in parallel with other non-overlapping prep work
- All tests for a user story marked [P] can run in parallel
- Shell and chat implementation tasks touch different files and can be split across developers once the tests are in place
- User stories can be implemented independently after the foundational phase completes

---

## Parallel Example: User Story 1

```bash
# Launch the User Story 1 tests together:
Task: "Add failing Angular unit coverage for overlay menu state and narrow-screen switching in frontend/src/app/core/layout/app-shell.component.spec.ts"
Task: "Add failing Playwright coverage for opening and dismissing the lateral menu overlay on mobile widths in frontend/tests/e2e/responsive-layout.spec.ts"

# Then split the implementation across files:
Task: "Update frontend/src/app/core/layout/app-shell.component.ts to wire ng-bootstrap overlay behavior and keep the existing route and user actions intact"
Task: "Update frontend/src/app/core/layout/app-shell.component.html to render the lateral menu as an overlay-aware Bootstrap structure with the current Italian labels and controls"
Task: "Update frontend/src/app/core/layout/app-shell.component.scss to preserve the current shell styling while preventing horizontal overflow on constrained screens"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently → Deploy/Demo
4. Add User Story 3 → Test independently → Deploy/Demo
5. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1
   - Developer B: User Story 2
   - Developer C: User Story 3
3. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
