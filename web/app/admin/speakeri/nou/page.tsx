import { createSpeaker } from "../actions";
import SpeakerForm from "../SpeakerForm";
import styles from "../speakeri.module.css";

export default function NewSpeakerPage() {
  return (
    <>
      <h1 className={styles.h1}>Speaker nou</h1>
      <SpeakerForm action={createSpeaker} />
    </>
  );
}
