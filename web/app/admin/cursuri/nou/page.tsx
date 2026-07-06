import { createCourse } from "../actions";
import CourseForm from "../CourseForm";

export default function NewCoursePage() {
  return (
    <>
      <h1 className="wp-page-title">Curs nou</h1>
      <CourseForm action={createCourse} />
    </>
  );
}
