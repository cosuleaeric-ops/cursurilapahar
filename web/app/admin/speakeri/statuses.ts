export const SPEAKER_STATUSES = ["CONTACTAT", "URMEAZĂ", "RECURENT", "MID", "NOPE"] as const;

export const STATUS_COLOR: Record<string, string> = {
  CONTACTAT: "#2271b1",
  "URMEAZĂ": "#7c3aed",
  RECURENT: "#16a34a",
  MID: "#d97706",
  NOPE: "#dc2626",
};

export const STATUS_RANK: Record<string, number> = {
  CONTACTAT: 0,
  "URMEAZĂ": 1,
  RECURENT: 2,
  MID: 3,
  NOPE: 4,
};
