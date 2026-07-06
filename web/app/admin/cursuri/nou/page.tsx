import { createCourse } from "../actions";
import CourseForm from "../CourseForm";
import styles from "../cursuri.module.css";

export default function NewCoursePage() {
  return (
    <>
      <h1 className={styles.h1}>Curs nou</h1>
      <CourseForm action={createCourse} />
    </>
  );
}
