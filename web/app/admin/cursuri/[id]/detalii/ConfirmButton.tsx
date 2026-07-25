"use client";

// Echivalentul lui onsubmit="return confirm(...)" din view.php: dacă userul
// anulează dialogul, formularul nu se mai trimite.
export default function ConfirmButton({
  message,
  className,
  style,
  title,
  children,
}: {
  message: string;
  className?: string;
  style?: React.CSSProperties;
  title?: string;
  children: React.ReactNode;
}) {
  return (
    <button
      type="submit"
      className={className}
      style={style}
      title={title}
      onClick={(e) => {
        if (!confirm(message)) e.preventDefault();
      }}
    >
      {children}
    </button>
  );
}
