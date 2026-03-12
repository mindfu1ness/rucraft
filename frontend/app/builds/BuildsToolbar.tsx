"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { AddBuildModal } from "./AddBuildModal";

export function BuildsToolbar() {
  const router = useRouter();
  const { user } = useAuth();
  const [addModalOpen, setAddModalOpen] = useState(false);

  return (
    <>
      {user != null && (
        <div className="skins-toolbar">
          <div className="skins-actions">
            <button type="button" className="skins-action-btn" onClick={() => setAddModalOpen(true)}>
              Добавить
            </button>
          </div>
        </div>
      )}
      <AddBuildModal open={addModalOpen} onClose={() => setAddModalOpen(false)} onSuccess={() => router.refresh()} />
    </>
  );
}
