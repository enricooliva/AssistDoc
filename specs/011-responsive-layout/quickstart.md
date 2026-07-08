# Quickstart: Responsive Mobile Layout

## Goal

Verify that the existing app shell and chat page behave correctly on mobile-sized screens without changing backend behavior.

## Prerequisites

- Frontend dependencies installed
- Local development server available
- A browser with responsive device emulation or a physical phone-sized viewport

## Verification Steps

1. Start the frontend application.
2. Open the app at a mobile viewport width.
3. Confirm the lateral menu opens as an overlay when the screen is too narrow for a persistent sidebar.
4. Confirm the overlay can be dismissed and the main content regains full width.
5. Open the chat page and verify the conversation list stays inside the visible layout.
6. Add enough conversations to require scrolling and confirm the list scrolls internally instead of overflowing the page.
7. Rotate the viewport between portrait and landscape and confirm the layout remains readable.

## Acceptance Checks

- The lateral menu remains usable on small screens.
- The chat history never forces horizontal overflow at common mobile widths.
- The message composer and main chat area remain accessible while the history list is long.
- The interaction matches the current Bootstrap-based visual style.

## Regression Areas

- Shell navigation toggle behavior
- Chat page history container height
- Message composer visibility on short screens
- Focus and dismissal behavior of the overlay menu
