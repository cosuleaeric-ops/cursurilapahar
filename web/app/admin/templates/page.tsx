import { sql } from "@/lib/db";
import TemplatesEditor from "./TemplatesEditor";
import { type Template } from "./actions";
import { TPL_CSS } from "./styles";

export const dynamic = "force-dynamic";

export default async function TemplatesPage({ searchParams }: { searchParams: Promise<{ saved?: string }> }) {
  const { saved } = await searchParams;
  const rows = (await sql`SELECT value FROM settings WHERE key = 'templates'`) as { value: unknown }[];
  const templates = Array.isArray(rows[0]?.value) ? (rows[0].value as Template[]) : [];

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: TPL_CSS }} />
      <h1 className="wp-page-title">Templates</h1>

      {saved && <div className="notice notice-success">Template-urile au fost salvate.</div>}

      <div className="card">
        <div className="card-title">📋 Mesaje template</div>
        <p className="tpl-intro">
          Apar ca butoane pe dashboard - un click copiază textul în clipboard. Dă click pe un template ca să-l editezi.
          Le poți edita atât tu, cât și Andy.
        </p>
        <TemplatesEditor templates={templates} />
      </div>
    </>
  );
}
