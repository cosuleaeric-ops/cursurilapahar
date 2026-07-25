// Regulile comune ale formularelor publice, portate din assets/js/main.js + api/contact.php.
// Fișier pur (fără DB / fetch), ca să poată fi folosit și pe client, și în server actions.

/** main.js:128 isValidEmail — cere punct și TLD de minimum 2 caractere („a@b.c" e respins). */
export const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

/** Răspunsul are aceeași formă ca JSON-ul din api/contact.php / api/subscribe.php. */
export type FormResult = { success: true } | { success: false; message: string };

/** api/contact.php:44 — eticheta din log: ucfirst(str_replace('_', ' ', $key)). */
export function phpLabel(key: string): string {
  const s = key.replace(/_/g, " ");
  return s.charAt(0).toUpperCase() + s.slice(1);
}

/** api/contact.php:45-47 — strip_tags, iar newline-urile devin spațiu (o singură linie per câmp). */
export function phpValue(value: string): string {
  return value
    .replace(/[\r\n]+/g, " ")
    .replace(/<[^>]*>/g, "")
    .trim();
}

/**
 * api/contact.php:41-49 — se salvează TOATE cheile trimise de formular, inclusiv cele
 * goale, cu etichete PHP-style; grupurile de checkbox se unesc cu ", ".
 */
export function phpPayload(formData: FormData): Record<string, string> {
  const out: Record<string, string> = {};
  for (const key of Array.from(new Set(formData.keys()))) {
    if (key === "form_type" || key.startsWith("$ACTION")) continue;
    out[phpLabel(key)] = formData
      .getAll(key)
      .map((v) => phpValue(String(v)))
      .join(", ");
  }
  return out;
}
