// Copiat verbatim din <style> din admin/todos/index.php (nu e în admin.css).
export const TODOS_CSS = `
.todos-grid { display: flex; flex-direction: column; gap: 26px; }
.todo-list-head { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.todo-list-circle { width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
.todo-list-name { font-size: 17px; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
.todo-items { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.todo-item { display: flex; align-items: center; gap: 11px; padding: 5px 8px 5px 3px; border-radius: 8px; transition: background .1s; }
.todo-item:hover { background: var(--bg); }
.todo-assign--eric6 { background: #eff6ff; }
.todo-assign--eric6 .todo-assign-name { color: #2563eb; }
.todo-assign--andy { background: #f0fdf4; }
.todo-assign--andy .todo-assign-name { color: #16a34a; }
.todo-check { flex-shrink: 0; margin: 0; display: flex; }
.todo-check input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }
.todo-text { font-size: 15px; color: var(--text); line-height: 1.4; }
.todo-text.done { text-decoration: line-through; color: var(--text-muted); }
.todo-link { color: var(--accent); text-decoration: underline; }
.todo-link:hover { color: var(--accent-hover); }

/* assignment pill (Basecamp-style) */
.todo-assign { display: inline-flex; align-items: center; gap: 7px; background: var(--bg); border-radius: 999px; padding: 3px 12px 3px 4px; white-space: nowrap; }
.todo-av { position: relative; width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: 700; overflow: hidden; }
.todo-av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.todo-assign-name { font-size: 13px; color: var(--text-muted); font-weight: 500; }
.todo-item > form { margin: 0; }
.todo-item > form:last-child { margin-left: auto; }

/* assignee chooser in add form */
.todo-add-assign { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.todo-add-assign-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
.todo-assign-pick { cursor: pointer; display: inline-flex; }
.todo-assign-pick input { position: absolute; opacity: 0; width: 0; height: 0; }
.todo-assign-pick .todo-assign { border: 2px solid transparent; cursor: pointer; transition: border-color .12s, background .12s; }
.todo-assign-pick input:checked + .todo-assign { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.todo-del { opacity: 0; flex-shrink: 0; background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 18px; line-height: 1; padding: 0 4px; transition: opacity .15s, color .15s; }
.todo-item:hover .todo-del { opacity: 1; }
.todo-del:hover { color: var(--danger); }
.todo-empty { color: var(--text-muted); font-size: 14px; padding: 5px 3px; }

/* completed collapsible */
.todo-completed { margin-top: 0; }
.todo-completed > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 6px 3px; font-size: 13px; color: var(--text-muted); user-select: none; outline: none; }
.todo-completed > summary::-webkit-details-marker { display: none; }
.todo-completed > summary:hover { color: var(--text); }
.todo-completed-caret { font-size: 10px; color: var(--text-muted); transition: transform .15s; display: inline-block; }
.todo-completed[open] .todo-completed-caret { transform: rotate(90deg); }
.todo-completed-items { display: flex; flex-direction: column; }
.todo-done-day { font-size: 12px; font-weight: 600; color: var(--text-muted); letter-spacing: .02em; margin: 12px 3px 3px; }
.todo-completed > .todo-done-day:first-of-type { margin-top: 6px; }

.todo-add { margin-top: 6px; }
.todo-add-link { background: none; border: none; cursor: pointer; color: var(--accent); font-size: 15px; padding: 5px 3px; display: inline-flex; align-items: center; gap: 11px; text-decoration: none; }
.todo-add-link:hover { text-decoration: underline; }
.todo-add-checkmark { width: 18px; height: 18px; border: 1.5px solid var(--border-strong); border-radius: 4px; flex-shrink: 0; }
.todos-head { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
.todos-head .wp-page-title { margin-bottom: 0; }
.todo-add-icon { width: 30px; height: 30px; border-radius: 999px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; background: none; color: var(--accent); border: none; cursor: pointer; font-size: 24px; line-height: 1; padding: 0 0 3px; transition: color .15s, background .15s, transform .12s; }
.todo-add-icon:hover { background: var(--accent-soft); }
.todo-add-icon:active { transform: scale(.9); }
.todo-add-form { display: none; flex-direction: column; gap: 10px; margin: 0 3px 10px; padding: 0; }
.todo-add-form.open { display: flex; }
.todo-add-actions { display: flex; align-items: center; gap: 8px; }
.todo-add-input { width: 100%; padding: 10px 13px; border: 1px solid var(--border-strong); border-radius: 8px; font-size: 14px; background: #fff; color: var(--text); transition: border-color .15s, box-shadow .15s; }
.todo-add-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.todo-add-submit { padding: 10px 16px; background: var(--accent); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; white-space: nowrap; transition: background .15s; }
.todo-add-submit:hover { background: var(--accent-hover); }
.todo-add-cancel { background: #fff; border: 1px solid var(--border-strong); color: var(--text); border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; padding: 10px 16px; transition: background .15s, border-color .15s; }
.todo-add-cancel:hover { background: var(--bg-warm); }
`;
