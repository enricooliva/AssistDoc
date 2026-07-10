# Implementation Plan: Responsive Mobile Layout

**Branch**: `011-responsive-layout` | **Date**: 2026-05-25 | **Spec**: [spec.md](/home/enricooliva/Workspace/AssistDoc/specs/011-responsive-layout/spec.md)
**Input**: Feature specification from `/specs/011-responsive-layout/spec.md`

## Summary

Improve the existing AssistDoc shell and chat screens for mobile use by turning the lateral menu into an overlay on constrained screens and keeping the chat conversation list contained and scrollable. The implementation should stay consistent with the current Bootstrap-based UI, prefer Bootstrap and ng-bootstrap primitives over new custom CSS, and preserve the app’s existing desktop behavior.

## Technical Context

**Language/Version**: TypeScript 5.x, Angular 18 in the current frontend package  
**Primary Dependencies**: Bootstrap 5, Bootstrap Icons, `@ngx-formly/bootstrap`, ng-bootstrap for overlay/navigation primitives, existing Angular router and standalone components  
**Storage**: N/A for this feature; layout state is local to the UI  
**Testing**: Angular unit tests, component tests, Playwright end-to-end tests  
**Target Platform**: Web application, responsive for mobile and desktop browsers  
**Project Type**: Angular SPA frontend feature  
**Performance Goals**: Layout must remain usable on small screens without horizontal scrolling or blocked navigation  
**Constraints**: Preserve current visual language, keep Italian UI text, avoid introducing a separate mobile-only page, and minimize custom CSS in favor of Bootstrap/ng-bootstrap  
**Scale/Scope**: Two existing UI areas only: the app shell lateral menu and the chat page conversation layout

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- Specification exists and contains no unresolved `[NEEDS CLARIFICATION]`.
- Planned scope traces to documented requirements and user stories.
- Architecture preserves REST API-first boundaries and does not bypass APIs.
- Backend design follows `Controller -> Service -> Repository -> Model`.
- Business rules, workflow transitions, and transaction boundaries live in Services.
- Contracts are identified for every API, including schemas, validation, auth, and error formats.
- Test-first coverage is planned before implementation starts: API success/failure, authorization, workflow transition, component, and critical Playwright E2E tests as applicable.
- Security design enforces stateless JWT auth, SSO outside local, and RBAC with endpoint-level role declarations.
- Frontend plan preserves Angular SPA module boundaries and Italian (`it-IT`) localization requirements.
- Any added complexity is explicitly justified with a simpler alternative and a rollback strategy.

## Project Structure

### Documentation (this feature)

```text
specs/011-responsive-layout/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── checklists/
    └── requirements.md
```

### Source Code (repository root)

```text
frontend/
├── src/
│   ├── app/
│   │   ├── core/layout/app-shell.component.ts
│   │   ├── core/layout/app-shell.component.html
│   │   ├── core/layout/app-shell.component.scss
│   │   └── features/chat/
│   │       ├── chat-page.component.ts
│   │       ├── chat-page.component.html
│   │       └── chat-page.component.scss
│   └── styles.scss
└── tests/
    └── e2e/
```

**Structure Decision**: Keep the work inside the existing Angular shell and chat feature slices. Use Bootstrap layout and utility classes first, use ng-bootstrap for the mobile overlay behavior, and touch component SCSS only where the current implementation already needs layout-specific containment or spacing rules.

## Complexity Tracking

No constitution violations are expected. The simplest solution is to keep the current Angular SPA structure and update the existing shell and chat components rather than introducing a separate mobile route or a parallel responsive subsystem.
