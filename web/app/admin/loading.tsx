// Ecranul care apare instant la click pe un link din meniu, cât timp pagina
// își aduce datele. Fără el, browserul rămâne pe pagina veche și pare blocat.
export default function Loading() {
  return (
    <>
      <div className="sk sk-title" />
      <div className="sk-grid">
        <div className="sk sk-block" />
        <div className="sk sk-block" />
        <div className="sk sk-block" />
      </div>
    </>
  );
}
