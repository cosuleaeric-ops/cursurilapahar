"use client";

import Link from "next/link";
import { useState } from "react";
import { deleteCourse, saveDiscount, toggleActive } from "./actions";
import { courseMeta } from "./meta";

// Port din clp_render_admin_courses_table() (lib/courses_admin.php).

export type CourseRow = {
  id: number;
  title: string;
  speaker_name: string | null;
  location: string | null;
  date_display: string;
  date_raw: string | null;
  livetickets_url: string | null;
  image_url: string | null;
  active: boolean;
  clicks: number;
  discount_percent: number | null;
  discount_ends_local: string | null;
  discount_active: boolean;
};

const IconEdit = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <path d="M12 20h9" />
    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
  </svg>
);
const IconPercent = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <line x1="19" y1="5" x2="5" y2="19" />
    <circle cx="6.5" cy="6.5" r="2.5" />
    <circle cx="17.5" cy="17.5" r="2.5" />
  </svg>
);
const IconTrash = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <polyline points="3 6 5 6 21 6" />
    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
    <path d="M10 11v6" />
    <path d="M14 11v6" />
    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
  </svg>
);

export default function CoursesTable({ list }: { list: CourseRow[] }) {
  // toggleDiscountRow() comută fiecare rând independent, deci pot fi deschise mai multe.
  const [openDiscount, setOpenDiscount] = useState<Set<number>>(new Set());
  const toggle = (id: number) =>
    setOpenDiscount((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });

  return (
    <table className="wp-table">
      <thead>
        <tr>
          <th style={{ width: 72 }}>Imagine</th>
          <th>Titlu</th>
          <th>Dată</th>
          <th style={{ width: 100 }}>Status</th>
          <th style={{ width: 56, whiteSpace: "nowrap" }}>clicks</th>
          <th style={{ width: 96 }}>Acțiuni</th>
        </tr>
      </thead>
      <tbody>
        {list.map((c) => (
          <RowPair key={c.id} c={c} open={openDiscount.has(c.id)} onToggle={() => toggle(c.id)} />
        ))}
      </tbody>
    </table>
  );
}

function RowPair({ c, open, onToggle }: { c: CourseRow; open: boolean; onToggle: () => void }) {
  const hasDisc = c.discount_percent != null && c.discount_ends_local != null;
  const meta = courseMeta(c.speaker_name, c.location);

  return (
    <>
      <tr>
        <td>
          {c.image_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img className="course-thumb" src={c.image_url} alt="" />
          ) : (
            <div className="course-thumb-empty" />
          )}
        </td>
        <td style={{ fontWeight: 600 }}>
          <div className="course-title-cell">
            <div className="course-title-line">
              {c.livetickets_url ? (
                <a href={c.livetickets_url} target="_blank" rel="noopener" className="course-title-link">
                  {c.title}
                </a>
              ) : (
                c.title
              )}
              {meta && (
                <span style={{ fontWeight: 400, color: "var(--text-muted)" }}> · {meta}</span>
              )}
            </div>
            {hasDisc && (
              <span className={`discount-tag ${c.discount_active ? "discount-tag--active" : "discount-tag--expired"}`}>
                −{c.discount_percent}%{c.discount_active ? "" : " (expirată)"}
              </span>
            )}
          </div>
        </td>
        <td style={{ color: "var(--text-muted)" }}>{c.date_display}</td>
        <td>
          {!c.livetickets_url ? (
            <span
              className="btn btn-sm status-inactive"
              style={{ cursor: "default", opacity: 0.85 }}
              title="Adaugă link LiveTickets ca să apară pe site"
            >
              Draft
            </span>
          ) : (
            <form action={toggleActive} style={{ display: "inline" }}>
              <input type="hidden" name="id" value={c.id} />
              <button type="submit" className={`btn btn-sm ${c.active ? "status-active" : "status-inactive"}`}>
                {c.active ? "Activ" : "Inactiv"}
              </button>
            </form>
          )}
        </td>
        <td
          style={{
            textAlign: "center",
            fontVariantNumeric: "tabular-nums",
            ...(c.clicks ? { fontWeight: 600 } : { color: "var(--text-muted)" }),
          }}
        >
          {c.clicks}
        </td>
        <td>
          <div className="row-actions">
            <Link href={`/admin/cursuri?edit=${c.id}`} className="action-icon-btn" title="Editează" aria-label="Editează">
              <IconEdit />
            </Link>
            <button type="button" className="action-icon-btn" title="Reducere" aria-label="Reducere" onClick={onToggle}>
              <IconPercent />
            </button>
            <form
              action={deleteCourse}
              onSubmit={(e) => {
                if (!confirm("Ștergi cursul?")) e.preventDefault();
              }}
              style={{ display: "inline" }}
            >
              <input type="hidden" name="id" value={c.id} />
              <button type="submit" className="action-icon-btn action-icon-btn--danger" title="Șterge" aria-label="Șterge">
                <IconTrash />
              </button>
            </form>
          </div>
        </td>
      </tr>
      <tr className="discount-edit-row" style={{ display: open ? "table-row" : "none" }}>
        <td colSpan={6}>
          <form action={saveDiscount} className="discount-form">
            <input type="hidden" name="id" value={c.id} />
            <label>
              Reducere (%):
              <input
                type="number"
                name="discount_percent"
                min={1}
                max={100}
                defaultValue={c.discount_percent ?? ""}
                style={{ width: 90 }}
              />
            </label>
            <label>
              Expiră la (ora București):
              <input type="datetime-local" name="discount_ends_at" defaultValue={c.discount_ends_local ?? ""} />
            </label>
            <button type="submit" className="btn btn-sm btn-primary">
              Salvează reducerea
            </button>
            {hasDisc && (
              <button
                type="submit"
                name="clear"
                value="1"
                className="btn btn-sm btn-danger"
                onClick={(e) => {
                  if (!confirm("Ștergi reducerea?")) e.preventDefault();
                }}
              >
                Șterge reducerea
              </button>
            )}
          </form>
        </td>
      </tr>
    </>
  );
}
