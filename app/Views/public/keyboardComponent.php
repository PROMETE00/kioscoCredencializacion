<?php
function renderKeyboard(string $targetInput = '#identificador', string $id = 'numpad')
{
    ob_start();
?>

<!-- ── Numeric Keypad ─────────────────────────────────── -->
<div class="nk-pad" id="<?= $id ?>">
  <div class="nk-layout">

    <!-- ── Keys area ── -->
    <div class="nk-keys-area">

      <!-- Number keys (default) -->
      <div class="nk-page nk-page--num is-active" data-page="num">
        <button class="nk-key" type="button" data-key="1">1</button>
        <button class="nk-key" type="button" data-key="2">2</button>
        <button class="nk-key" type="button" data-key="3">3</button>
        <button class="nk-key" type="button" data-key="4">4</button>
        <button class="nk-key" type="button" data-key="5">5</button>
        <button class="nk-key" type="button" data-key="6">6</button>
        <button class="nk-key" type="button" data-key="7">7</button>
        <button class="nk-key" type="button" data-key="8">8</button>
        <button class="nk-key" type="button" data-key="9">9</button>
        <button class="nk-key nk-key--zero" type="button" data-key="0">0</button>
      </div>

      <!-- Letter keys -->
      <div class="nk-page nk-page--abc" data-page="abc">
        <?php foreach (range('A', 'Z') as $letter): ?>
          <button class="nk-key" type="button" data-key="<?= $letter ?>"><?= $letter ?></button>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- ── Side actions ── -->
    <div class="nk-side">
      <button class="nk-side-btn nk-side-btn--toggle" type="button" data-action="toggle">
        <svg class="nk-side-ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
        <span class="nk-side-label" data-label-num="ABC" data-label-abc="123">ABC</span>
      </button>

      <button class="nk-side-btn nk-side-btn--del" type="button" data-action="delete">
        <svg class="nk-side-ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="18" y1="9" x2="12" y2="15"/><line x1="12" y1="9" x2="18" y2="15"/></svg>
        <span class="nk-side-label">Borrar</span>
      </button>

      <button class="nk-side-btn nk-side-btn--clear" type="button" data-action="clear">
        <svg class="nk-side-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        <span class="nk-side-label">Limpiar</span>
      </button>
    </div>

  </div>
</div>

<style>
/* ── Numeric Keypad ────────────────────────────────── */
.nk-pad {
  margin-top: 20px;
  padding: 14px;
  border-radius: 16px;
  background: linear-gradient(145deg, #f0f4f8, #e8edf3);
  border: 1px solid #d5dde6;
  box-shadow:
    0 4px 14px rgba(15, 23, 42, .06),
    inset 0 1px 0 rgba(255,255,255,.7);
  user-select: none;
  -webkit-user-select: none;
}

/* Main layout: keys on left, actions on right */
.nk-layout {
  display: flex;
  gap: 10px;
  max-width: 440px;
  margin: 0 auto;
}

/* ── Keys area ── */
.nk-keys-area {
  flex: 1;
  min-width: 0;
}

/* Pages (num / abc) */
.nk-page {
  display: none;
  gap: 8px;
}

.nk-page.is-active {
  display: grid;
}

.nk-page--num {
  grid-template-columns: repeat(3, 1fr);
}

.nk-page--num .nk-key--zero {
  grid-column: 1 / -1;
}

.nk-page--abc {
  grid-template-columns: repeat(6, 1fr);
}

/* ── Individual key ── */
.nk-key {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 56px;
  border: 1px solid #dbe3ed;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  color: #1e293b;
  font-size: 22px;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition:
    transform .1s ease,
    box-shadow .12s ease,
    background .12s ease;
  box-shadow:
    0 2px 4px rgba(15, 23, 42, .06),
    0 1px 0 rgba(15, 23, 42, .04);
  outline: none;
  -webkit-tap-highlight-color: transparent;
}

.nk-key:hover {
  background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
  box-shadow:
    0 4px 8px rgba(15, 23, 42, .08),
    0 1px 0 rgba(15, 23, 42, .04);
}

.nk-key:active,
.nk-key.is-pressed {
  transform: scale(.95);
  background: linear-gradient(180deg, #f1f5f9 0%, #e8edf3 100%);
  box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
}

/* Letter keys are smaller */
.nk-page--abc .nk-key {
  height: 48px;
  font-size: 16px;
  font-weight: 800;
  border-radius: 10px;
}

/* Ripple */
.nk-key::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: radial-gradient(circle, rgba(47, 109, 246, .15) 0%, transparent 70%);
  opacity: 0;
  transition: opacity .25s ease;
  pointer-events: none;
}

.nk-key.is-pressed::after {
  opacity: 1;
}

/* ── Side actions column ── */
.nk-side {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 76px;
  flex-shrink: 0;
}

.nk-side-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  border-radius: 12px;
  border: 1px solid #dbe3ed;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  cursor: pointer;
  transition:
    transform .1s ease,
    box-shadow .12s ease,
    background .12s ease;
  box-shadow:
    0 2px 4px rgba(15, 23, 42, .06),
    0 1px 0 rgba(15, 23, 42, .04);
  outline: none;
  -webkit-tap-highlight-color: transparent;
  padding: 8px 4px;
}

.nk-side-btn:active {
  transform: scale(.95);
}

.nk-side-ico {
  flex-shrink: 0;
}

.nk-side-label {
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .04em;
  line-height: 1.1;
}

/* Toggle (ABC / 123) */
.nk-side-btn--toggle {
  color: #2f6df6;
  border-color: rgba(47, 109, 246, .22);
  background: linear-gradient(180deg, #eef4ff 0%, #e8efff 100%);
}

.nk-side-btn--toggle:hover {
  background: linear-gradient(180deg, #e8efff 0%, #dde6ff 100%);
}

.nk-side-btn--toggle:active {
  background: linear-gradient(180deg, #dde6ff 0%, #cddaff 100%);
}

/* Delete */
.nk-side-btn--del {
  color: #b91c1c;
  border-color: rgba(239, 68, 68, .18);
  background: linear-gradient(180deg, #fff5f5 0%, #fef2f2 100%);
}

.nk-side-btn--del:hover {
  background: linear-gradient(180deg, #fef2f2 0%, #fee2e2 100%);
  border-color: rgba(239, 68, 68, .28);
}

.nk-side-btn--del:active {
  background: linear-gradient(180deg, #fee2e2 0%, #fecaca 100%);
}

/* Clear */
.nk-side-btn--clear {
  color: #475569;
  border-color: rgba(71, 85, 105, .18);
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.nk-side-btn--clear:hover {
  background: linear-gradient(180deg, #f1f5f9 0%, #e8edf3 100%);
}

.nk-side-btn--clear:active {
  background: linear-gradient(180deg, #e8edf3 0%, #dde3ed 100%);
}

/* ── Responsive ── */
@media (pointer: coarse) {
  .nk-key {
    height: 62px;
    font-size: 24px;
  }
  .nk-page--abc .nk-key {
    height: 50px;
    font-size: 17px;
  }
  .nk-side { width: 80px; }
}

@media (max-width: 400px) {
  .nk-pad {
    padding: 10px;
    border-radius: 14px;
  }
  .nk-layout { gap: 6px; }
  .nk-page { gap: 6px; }
  .nk-side { width: 64px; gap: 6px; }
  .nk-key {
    height: 48px;
    font-size: 20px;
    border-radius: 10px;
  }
  .nk-page--abc .nk-key {
    height: 40px;
    font-size: 14px;
  }
  .nk-side-btn { border-radius: 10px; }
}
</style>

<script>
(() => {
  const pad   = document.getElementById('<?= $id ?>');
  const input = document.querySelector('<?= $targetInput ?>');
  if (!pad || !input) return;

  const pages     = pad.querySelectorAll('.nk-page');
  const toggleBtn = pad.querySelector('[data-action="toggle"]');
  const toggleLbl = toggleBtn?.querySelector('.nk-side-label');
  let   mode      = 'num';                    // 'num' | 'abc'

  /* ── Switch page ── */
  const switchPage = (to) => {
    mode = to;
    pages.forEach(p => p.classList.toggle('is-active', p.dataset.page === to));
    if (toggleLbl) {
      toggleLbl.textContent = to === 'num'
        ? toggleLbl.dataset.labelNum    // shows "ABC"
        : toggleLbl.dataset.labelAbc;   // shows "123"
    }
  };

  /* ── Pointer handler (covers touch + mouse) ── */
  pad.addEventListener('pointerdown', (e) => {
    const btn = e.target.closest('.nk-key, .nk-side-btn');
    if (!btn) return;
    e.preventDefault();                       // keep input focused

    btn.classList.add('is-pressed');
    setTimeout(() => btn.classList.remove('is-pressed'), 150);

    const key    = btn.dataset.key;
    const action = btn.dataset.action;

    if (key) {
      /* ── Insert character at cursor ── */
      const start = input.selectionStart ?? input.value.length;
      const end   = input.selectionEnd   ?? input.value.length;
      input.value = input.value.slice(0, start) + key + input.value.slice(end);
      const pos   = start + 1;
      input.setSelectionRange(pos, pos);

    } else if (action === 'delete') {
      /* ── Backspace ── */
      const start = input.selectionStart ?? input.value.length;
      const end   = input.selectionEnd   ?? input.value.length;
      if (start !== end) {
        input.value = input.value.slice(0, start) + input.value.slice(end);
        input.setSelectionRange(start, start);
      } else if (start > 0) {
        input.value = input.value.slice(0, start - 1) + input.value.slice(start);
        input.setSelectionRange(start - 1, start - 1);
      }

    } else if (action === 'clear') {
      input.value = '';

    } else if (action === 'toggle') {
      switchPage(mode === 'num' ? 'abc' : 'num');
      return;                                 // no need to dispatch input
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.focus();
  });
})();
</script>

<?php
    return ob_get_clean();
}