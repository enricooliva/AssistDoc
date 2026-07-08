# Research: Responsive Mobile Layout

## Decision 1: Use Bootstrap and ng-bootstrap for responsive behavior

- **Decision**: Build the responsive shell with Bootstrap grid/utilities and ng-bootstrap overlay primitives instead of introducing a new CSS-heavy layout system.
- **Rationale**: The current frontend already uses Bootstrap, Bootstrap Icons, and `@ngx-formly/bootstrap`, so this keeps the feature aligned with the existing UI stack and reduces custom styling drift.
- **Alternatives considered**:
  - Add a custom responsive layout layer with more SCSS: rejected because it would duplicate behavior already available in Bootstrap and make the shell harder to maintain.
  - Introduce a separate mobile-only page: rejected because it would fragment the current navigation and create inconsistent behavior across screen sizes.

## Decision 2: Keep the existing shell and chat feature slices

- **Decision**: Implement the responsive changes directly in `app-shell` and `chat-page` instead of creating a new layout module.
- **Rationale**: The feature is a refinement of current screens, not a new user flow. Keeping the same component boundaries preserves consistency with the current implementation and reduces test impact.
- **Alternatives considered**:
  - Extract a new responsive shell component: rejected because it adds indirection without adding user value.
  - Refactor the chat page into a separate responsive module: rejected because the current component already owns the relevant UI state.

## Decision 3: Make the lateral menu an overlay on constrained screens

- **Decision**: When the viewport is too small for a persistent side rail, the lateral menu should appear as a dismissible overlay.
- **Rationale**: This preserves content space on mobile and matches the user’s request that the menu not consume the available width when dimensions are insufficient.
- **Alternatives considered**:
  - Collapse the menu into a narrow permanent rail: rejected because it still reduces the available chat area and can be hard to use on phones.
  - Hide the menu entirely on mobile: rejected because users still need navigation access.

## Decision 4: Keep chat history contained in its own scroll region

- **Decision**: The conversation list should scroll within the chat layout instead of expanding the full page height or overflowing the content area.
- **Rationale**: This keeps the composer and message area visible and prevents the history list from pushing important content off-screen.
- **Alternatives considered**:
  - Let the whole page scroll naturally: rejected because the long list reduces usability on smaller viewports.
  - Move chat history into a separate route: rejected because it adds an unnecessary navigation step.

## Decision 5: Preserve the current visual language

- **Decision**: Reuse the current Bootstrap-based spacing, borders, and component patterns, with minimal custom CSS adjustments only where needed for containment.
- **Rationale**: The feature should feel like a refinement of the existing product, not a redesign.
- **Alternatives considered**:
  - Restyle the pages with a new visual system: rejected because it would conflict with the current implementation and expand the scope.
