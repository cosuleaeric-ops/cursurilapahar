"use server";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { destroySession, getRealSession, VIEW_AS_COOKIE } from "@/lib/auth";

export async function logout(): Promise<void> {
  await destroySession();
  redirect("/login");
}

/** Owner-ul poate vedea adminul ca alt user (cookie clp_view_as, ca în PHP). */
export async function switchUser(formData: FormData): Promise<void> {
  const real = await getRealSession();
  if (!real || real.role !== "owner") redirect("/admin");
  const target = String(formData.get("target_username") ?? "").trim();
  const store = await cookies();
  if (!target || target === real.username) {
    store.delete(VIEW_AS_COOKIE);
  } else {
    const rows = (await sql`SELECT username FROM users WHERE username = ${target}`) as { username: string }[];
    if (rows[0]) {
      store.set(VIEW_AS_COOKIE, target, {
        httpOnly: true,
        secure: process.env.NODE_ENV === "production",
        sameSite: "strict",
        path: "/",
        maxAge: 7200,
      });
    }
  }
  redirect("/admin");
}
