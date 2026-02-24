# Safari Badge Assertion Flow — Plan

ASSUMPTIONS I'M MAKING:
1. The issue is limited to macOS Safari (desktop), and Chrome behavior is the desired UX baseline.
2. The problem is in the front-end “Add to Backpack” flow implemented in `lib/js/badges-tab.js`.
3. We can change front-end UI/JS behavior (not limited to server-side fixes).
→ Correct me now or I’ll proceed with these.

## Goal
Ensure Safari users can reliably copy the badge assertion URL after clicking “Add to Backpack,” with a UX that does not depend on Safari’s native `alert()` dialog button labeling or clipboard restrictions.

## Current Behavior (from screenshots + code)
- The JS flow in `lib/js/badges-tab.js`:
  - Issues an assertion via `POST /wp-json/b2tbadges/v1/issue-assertion`.
  - Tries `navigator.clipboard.writeText(assertionUrl)`.
  - Shows `alert(...)` with instructions.
- Chrome shows an “OK” button in the alert dialog and copy succeeds.
- Safari shows a “Close” link instead of “OK” and copy fails.

## Working Theory
Safari has stricter clipboard requirements:
- `navigator.clipboard.writeText()` requires a **user gesture** and **secure context**.
- The clipboard call is in an async success handler, which may no longer be considered a user gesture in Safari.
- Safari’s native alert button label (“Close”) is just a UI difference; the real issue is the clipboard call failing.

## Plan of Attack

### 1. Reproduce and instrument
- In Safari:
  - Click “Add to Backpack” and watch the console for clipboard errors or permissions issues.
  - Add temporary logging in `lib/js/badges-tab.js` around `navigator.clipboard.writeText` to verify whether it rejects.
- Confirm whether `navigator.clipboard` exists and whether `writeText()` rejects or is undefined.

### 2. Implement Safari-safe copy flow
Replace the current `alert()`-only UX with an explicit user-driven copy action:

**Option A (preferred): custom modal with Copy button**
- Add a small modal (HTML + CSS) injected once, with:
  - Read-only input showing the assertion URL
  - “Copy” button that triggers `navigator.clipboard.writeText` synchronously on click
  - Fallback “Select” button to highlight the text for manual copy
- Benefits:
  - Ensures the copy happens in a user gesture context.
  - Removes reliance on Safari’s native alert dialog.

**Option B (fallback): use `prompt()`**
- If clipboard API isn’t available or fails:
  - Show `prompt('Copy this URL:', assertionUrl)` which allows manual copy in Safari.
- This is a minimal change and still user-gesture-friendly.

### 3. Add robust clipboard fallback
- If `navigator.clipboard` is unavailable or rejected:
  - Create a hidden `<textarea>` or `<input>`.
  - `select()` the value.
  - `document.execCommand('copy')` as a legacy fallback.
- If that fails, fall back to manual copy UI.

### 4. Tighten the “issue assertion” response handling
- Ensure we always show the assertion URL in UI after issuance, even if copy fails.
- If `assertion.id` is missing, show a useful error message (already present, but keep it).

### 5. UX consistency across browsers
- Keep the message copy consistent:
  - “Badge issued.”
  - “Copy assertion URL.”
  - “Paste into Badgr/Canvas Credentials.”
- Avoid relying on browser-native alert labels.

### 6. Test matrix
- Safari (macOS): confirm copy works via modal button and manual fallback.
- Chrome: confirm the new flow still works.
- Firefox: ensure no regression (clipboard permissions can be different).

## Detailed TODO List

**Phase 1: Discovery & Repro**
- [ ] Reproduce the Safari failure on the same environment and capture console errors.
- [ ] Verify whether `navigator.clipboard` exists in Safari for this page.
- [ ] Confirm whether `navigator.clipboard.writeText()` rejects and capture the exact error.
- [ ] Verify that the page is served over HTTPS (clipboard requirement).

**Phase 2: UX Decision**
- [ ] Decide on the primary UX: custom modal vs `prompt()` fallback.
- [ ] Decide where to define the modal markup: inline injection vs Handlebars template.
- [ ] Decide where to place styling: existing LESS file vs new `_modal.less` import.

**Phase 3: Implementation (JS)**
- [ ] Implement a `copyToClipboard()` helper with promise handling and fallbacks.
- [ ] Add a modal open/close flow that is triggered after a successful assertion response.
- [ ] Ensure the copy action is bound to a user gesture (Copy button click).
- [ ] Preserve existing error messaging when assertion issuance fails.

**Phase 4: Implementation (UI/CSS)**
- [ ] Add modal markup with URL display, Copy, and Select buttons.
- [ ] Style the modal for readability and ensure a high `z-index`.
- [ ] Ensure modal is keyboard-accessible (focusable buttons, ESC to close if feasible).

**Phase 5: Fallbacks**
- [ ] Implement legacy `execCommand('copy')` fallback when Clipboard API is unavailable.
- [ ] Add manual selection flow if clipboard copy fails.
- [ ] Ensure user always sees the assertion URL even on failure.

**Phase 6: Regression Testing**
- [ ] Test Safari: copy success, fallback path, and manual copy path.
- [ ] Test Chrome: copy success and no regressions to UI.
- [ ] Test Firefox: verify Clipboard API behavior and fallback logic.

**Phase 7: Cleanup**
- [ ] Remove any temporary console logging added for debugging.
- [ ] Verify no unused helper functions remain.

## Concrete Implementation Steps
1. Update `lib/js/badges-tab.js`:
   - Replace `alert(...)` block with a custom modal workflow.
   - Add `copyToClipboard()` utility with promise + fallback.
2. Add minimal modal HTML template:
   - Option 1: Append inline HTML in `badges-tab.js` on first run.
   - Option 2: Add a Handlebars template for the modal in `lib/hbs/handlebars-templates.hbs`.
3. Add CSS in `lib/less/_layout.less` (or a new `_modal.less`) and rebuild `lib/css/main.css`.
4. Re-test Safari and Chrome.

## Suggested Acceptance Criteria
- Safari:
  - After clicking “Add to Backpack,” user sees a modal with the assertion URL and a “Copy” button.
  - Copy succeeds (clipboard contains URL).
  - If clipboard fails, URL is still visible and selectable for manual copy.
- Chrome:
  - Same UX as Safari, copy succeeds.

## Risks / Notes
- Clipboard API requires HTTPS in production. If you test on HTTP locally, Safari may block clipboard access.
- Elementor or other overlays might interfere with modal z-index; plan to set a high `z-index`.

## Next Action If You Approve
If this plan looks right, I’ll implement the modal + clipboard fallback and update the CSS/LESS accordingly.
