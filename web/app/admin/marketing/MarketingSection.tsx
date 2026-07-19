"use client";

import { useState } from "react";
import { addItem, toggleItem, deleteItem } from "./actions";

export type MktItem = { id: number; text: string; link: string; done: boolean };

function ItemRow({ item }: { item: MktItem }) {
  const label = item.text || item.link;
  return (
    <li className={`mkt-item${item.done ? " is-done" : ""}`}>
      <form action={toggleItem} className="mkt-toggle-form">
        <input type="hidden" name="id" value={item.id} />
        <label className="mkt-check">
          <input type="checkbox" checked={item.done} onChange={(e) => e.currentTarget.form?.requestSubmit()} />
          <span className="mkt-check-box"></span>
        </label>
      </form>
      <div className="mkt-item-body">
        {item.link ? (
          <a href={item.link} target="_blank" rel="noopener" className="mkt-item-text mkt-item-text-link">
            {label}
          </a>
        ) : item.text ? (
          <span className="mkt-item-text">{item.text}</span>
        ) : null}
      </div>
      <form
        action={deleteItem}
        className="mkt-delete-form"
        onSubmit={(e) => {
          if (!confirm("Ștergi această idee?")) e.preventDefault();
        }}
      >
        <input type="hidden" name="id" value={item.id} />
        <button type="submit" className="mkt-delete" title="Șterge" aria-label="Șterge">
          &times;
        </button>
      </form>
    </li>
  );
}

export default function MarketingSection({
  id,
  title,
  items,
}: {
  id: number;
  title: string;
  items: MktItem[];
}) {
  const [showAdd, setShowAdd] = useState(false);
  const [showDone, setShowDone] = useState(false);
  const open = items.filter((i) => !i.done);
  const done = items.filter((i) => i.done);

  return (
    <section className="mkt-section">
      <div className="mkt-section-head">
        <h2 className="mkt-section-title">{title}</h2>
        <button type="button" className="mkt-add-toggle" aria-expanded={showAdd} onClick={() => setShowAdd(!showAdd)}>
          +
        </button>
      </div>

      <form action={addItem} className="mkt-add-form" hidden={!showAdd}>
        <input type="hidden" name="section_id" value={id} />
        <span className="mkt-check-box mkt-check-box--ghost" aria-hidden="true"></span>
        <div className="mkt-add-fields">
          <input type="text" name="text" placeholder="Ideea de postare…" autoComplete="off" />
          <input type="text" name="link" placeholder="Link (opțional)" autoComplete="off" inputMode="url" />
        </div>
      </form>

      <ul className="mkt-list">
        {open.map((item) => (
          <ItemRow key={item.id} item={item} />
        ))}
      </ul>

      {done.length > 0 && (
        <>
          <button type="button" className="mkt-show-done" onClick={() => setShowDone(!showDone)}>
            {showDone ? "Ascunde postările finalizate" : `Arată postările finalizate (${done.length})`}
          </button>
          <ul className="mkt-list mkt-done-list" hidden={!showDone}>
            {done.map((item) => (
              <ItemRow key={item.id} item={item} />
            ))}
          </ul>
        </>
      )}
    </section>
  );
}
