"use server";

import { redirect } from "next/navigation";
import { verifyLogin, createSession } from "@/lib/auth";

export async function login(_prev: string | null, formData: FormData): Promise<string | null> {
  const username = String(formData.get("username") || "").trim();
  const password = String(formData.get("password") || "");
  if (!username || !password) return "Completează ambele câmpuri";

  const user = await verifyLogin(username, password);
  if (!user) return "Utilizator sau parolă greșită";

  await createSession(user);
  redirect("/admin");
}
