"use client";

import { useFormStatus } from "react-dom";

/**
 * Butonul se dezactivează cât timp acțiunea rulează. Fără asta, pauza pare că nu
 * face nimic (schimbarea durează o secundă-două la Meta) și se apasă de mai multe ori.
 */
export default function SubmitButton({
  label,
  pendingLabel,
  className,
}: {
  label: string;
  pendingLabel: string;
  className?: string;
}) {
  const { pending } = useFormStatus();
  return (
    <button
      type="submit"
      className={className}
      disabled={pending}
      aria-busy={pending}
      style={pending ? { opacity: 0.6, cursor: "wait" } : undefined}
    >
      {pending ? pendingLabel : label}
    </button>
  );
}
