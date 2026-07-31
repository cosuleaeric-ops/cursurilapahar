"use client";

import jsQR from "jsqr";
import { useEffect, useRef, useState } from "react";

type Rezultat = { ok: boolean; msg: string; eticheta?: string; nume?: string | null };

export default function Scanner({ eventId }: { eventId: number }) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [pornit, setPornit] = useState(false);
  const [eroare, setEroare] = useState("");
  const [rez, setRez] = useState<Rezultat | null>(null);
  // Camera vede același cod de zeci de ori pe secundă — ținem minte ultimul
  // trimis ca să nu batem serverul cu aceeași scanare.
  const ultimul = useRef("");
  const ocupat = useRef(false);

  async function trimite(payload: Record<string, unknown>) {
    if (ocupat.current) return;
    ocupat.current = true;
    try {
      const res = await fetch("/api/checkin", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ eventId, ...payload }),
      });
      setRez((await res.json()) as Rezultat);
      if (navigator.vibrate) navigator.vibrate(60);
    } catch {
      setRez({ ok: false, msg: "Fără conexiune" });
    } finally {
      ocupat.current = false;
    }
  }

  useEffect(() => {
    if (!pornit) return;
    let stream: MediaStream | null = null;
    let raf = 0;
    let oprit = false;

    (async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
        const video = videoRef.current;
        if (!video) return;
        video.srcObject = stream;
        await video.play();

        const tick = () => {
          if (oprit) return;
          const canvas = canvasRef.current;
          if (video.readyState === video.HAVE_ENOUGH_DATA && canvas) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext("2d", { willReadFrequently: true });
            if (ctx) {
              ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
              const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
              const cod = jsQR(img.data, img.width, img.height);
              if (cod?.data && cod.data !== ultimul.current) {
                ultimul.current = cod.data;
                void trimite({ token: cod.data });
                // același bilet poate fi rescanat după 3 secunde
                setTimeout(() => (ultimul.current = ""), 3000);
              }
            }
          }
          raf = requestAnimationFrame(tick);
        };
        raf = requestAnimationFrame(tick);
      } catch {
        setEroare("Nu am acces la cameră. Verifică permisiunea din browser sau folosește codul de mai jos.");
        setPornit(false);
      }
    })();

    return () => {
      oprit = true;
      cancelAnimationFrame(raf);
      stream?.getTracks().forEach((t) => t.stop());
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pornit]);

  return (
    <div className="scan">
      <div className={`scan-box${pornit ? " on" : ""}`}>
        <video ref={videoRef} playsInline muted />
        <canvas ref={canvasRef} style={{ display: "none" }} />
        {!pornit && (
          <button type="button" className="scan-start" onClick={() => setPornit(true)}>
            Pornește camera
          </button>
        )}
      </div>

      {eroare && <p className="scan-err">{eroare}</p>}

      {rez && (
        <div className={`scan-rez ${rez.ok ? "ok" : "nu"}`}>
          <strong>{rez.ok ? "Intrare permisă" : rez.msg}</strong>
          <span>
            {rez.ok ? rez.msg : ""}
            {rez.eticheta ? ` ${rez.eticheta}` : ""}
            {rez.nume ? ` · ${rez.nume}` : ""}
          </span>
        </div>
      )}

      <form
        className="scan-manual"
        onSubmit={(e) => {
          e.preventDefault();
          const fd = new FormData(e.currentTarget);
          void trimite({ serie: String(fd.get("serie") ?? ""), numar: Number(fd.get("numar")) });
        }}
      >
        <div className="f">
          <label>Seria</label>
          <input name="serie" maxLength={3} required style={{ width: 70, textTransform: "uppercase" }} />
        </div>
        <div className="f">
          <label>Numărul</label>
          <input name="numar" type="number" min={1} required style={{ width: 90 }} />
        </div>
        <button type="submit">Verifica</button>
      </form>
    </div>
  );
}
