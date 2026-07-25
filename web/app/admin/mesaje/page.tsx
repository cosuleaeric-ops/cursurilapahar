import { getSession } from "@/lib/auth";
import { CATS, loadGroupedMessages } from "@/lib/messages";
import MessagesBoard from "./MessagesBoard";

export const dynamic = "force-dynamic";

export default async function MesajePage() {
  const session = await getSession();
  const { byCat, tabCounts } = await loadGroupedMessages();

  return (
    <>
      <h1 className="wp-page-title">Mesaje</h1>
      <MessagesBoard cats={CATS} byCat={byCat} counts={tabCounts} isOwner={session?.role === "owner"} />
    </>
  );
}
