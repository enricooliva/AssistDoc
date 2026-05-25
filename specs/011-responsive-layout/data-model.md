# Data Model: Responsive Mobile Layout

## Overview

This feature does not introduce new persisted business entities. It changes how existing screens present the app shell and chat history on smaller viewports.

## UI State

### Shell Navigation State

- **Purpose**: Tracks whether the lateral menu is visible as an overlay on constrained screens.
- **Fields**:
  - `isOpen`
  - `isOverlayMode`
  - `viewportCategory`
- **Behavior**:
  - `isOverlayMode` is true when screen space is insufficient for a persistent side rail.
  - `isOpen` is user-controlled and should be dismissible without leaving the current route.

### Chat Layout State

- **Purpose**: Tracks how the chat page keeps the conversation list contained inside its layout.
- **Fields**:
  - `historyScrollPosition`
  - `availableViewportHeight`
  - `contentOverflowMode`
- **Behavior**:
  - The conversation list should remain inside a bounded scroll region.
  - The message area and composer should remain accessible without horizontal overflow.

## Existing Components Affected

### AppShellComponent

- **Responsibility**: Presents the top-level navigation, user actions, and lateral menu.
- **Layout impact**: Must switch between persistent sidebar and overlay menu behavior depending on viewport size.

### ChatPageComponent

- **Responsibility**: Presents chat history, message content, filters, citations, and the composer.
- **Layout impact**: Must keep the conversation list visually contained and preserve access to the main message area.

## Validation Rules

- Mobile viewports must not produce horizontal scrolling from navigation or chat content.
- The overlay menu must close cleanly and return focus to the main content flow.
- The conversation list must remain usable when it contains more items than can fit on screen.

## Notes

- No database migrations or API schema updates are required.
- This feature is intentionally limited to presentation state and viewport behavior.
