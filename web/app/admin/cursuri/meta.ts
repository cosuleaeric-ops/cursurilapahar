/**
 * „Speaker · Local" de lângă numele cursului. Din locație se ia doar localul,
 * fără adresă și oraș: „Mojo Club, București" → „Mojo Club".
 */
export function courseMeta(speaker: string | null, location: string | null): string {
  const local = location?.split(",")[0].trim();
  return [speaker?.trim(), local].filter(Boolean).join(" · ");
}
