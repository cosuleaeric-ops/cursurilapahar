"use client";

import { useState } from "react";

// Picker nativ + câmp text sincronizate (înlocuiește Coloris din PHP).
export default function ColorField({ name, label, value }: { name: string; label: string; value: string }) {
  const [val, setVal] = useState(value);
  const pickerVal = /^#[0-9a-fA-F]{6}$/.test(val) ? val : "#000000";
  return (
    <div className="form-group" style={{ margin: 0 }}>
      <label>{label}</label>
      <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
        <input
          type="color"
          value={pickerVal}
          onChange={(e) => setVal(e.target.value)}
          style={{ width: 44, height: 34, padding: 2, border: "1px solid var(--border)", borderRadius: 6, background: "#fff", cursor: "pointer" }}
          aria-label={`Alege ${label}`}
        />
        <input type="text" name={name} value={val} onChange={(e) => setVal(e.target.value)} style={{ flex: 1 }} />
      </div>
    </div>
  );
}
