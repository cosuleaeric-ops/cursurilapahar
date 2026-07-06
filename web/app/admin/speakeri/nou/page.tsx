import { createSpeaker } from "../actions";
import SpeakerForm from "../SpeakerForm";

export default function NewSpeakerPage() {
  return (
    <>
      <h1 className="wp-page-title">Speaker nou</h1>
      <SpeakerForm action={createSpeaker} />
    </>
  );
}
