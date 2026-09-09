import React, { useEffect, useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "react-query";
import { X, Send, MessageCircle, Loader2 } from "lucide-react";
import { messageApi } from "@/services/api";
import { Message } from "@/types";
import { formatDateTime } from "@/utils/helpers";
import { useAuthContext } from "@/contexts/AuthContext";
import { toast } from "react-toastify";

const POLL_INTERVAL_MS = 5000;

interface ChatDrawerProps {
  livraisonId: number;
  otherName?: string;
  isOpen: boolean;
  onClose: () => void;
}

export const ChatDrawer: React.FC<ChatDrawerProps> = ({
  livraisonId,
  otherName,
  isOpen,
  onClose,
}) => {
  const { user } = useAuthContext();
  const queryClient = useQueryClient();
  const bottomRef = useRef<HTMLDivElement>(null);
  const [contenu, setContenu] = useState("");

  const { data: messages, isLoading } = useQuery(
    ["messages", livraisonId],
    () => messageApi.getByLivraison(livraisonId).then((r) => r.data as Message[]),
    {
      enabled: isOpen && !!livraisonId,
      refetchInterval: isOpen ? POLL_INTERVAL_MS : false,
    }
  );

  const sendMutation = useMutation(
    (text: string) => messageApi.send(livraisonId, text),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(["messages", livraisonId]);
        setContenu("");
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message || "Erreur d'envoi");
      },
    }
  );

  const markReadMutation = useMutation(
    () => messageApi.markAsRead(livraisonId),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(["messages", livraisonId]);
      },
    }
  );

  useEffect(() => {
    if (isOpen && messages && user) {
      const hasUnread = messages.some((m) => m.expediteur.id !== user.id && !m.lu);
      if (hasUnread) {
        markReadMutation.mutate();
      }
    }
  }, [isOpen, messages, user]);

  useEffect(() => {
    if (bottomRef.current) {
      bottomRef.current.scrollIntoView({ behavior: "smooth" });
    }
  }, [messages]);

  const handleSend = (e: React.FormEvent) => {
    e.preventDefault();
    const text = contenu.trim();
    if (!text) return;
    sendMutation.mutate(text);
  };

  const isMe = (msg: Message) => msg.expediteur.id === user?.id;

  return (
    <>
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40"
          onClick={onClose}
          aria-hidden="true"
        />
      )}
      <div
        className={`fixed top-0 right-0 h-full w-full sm:w-[420px] bg-white shadow-2xl z-50 transform transition-transform duration-300 flex flex-col ${
          isOpen ? "translate-x-0" : "translate-x-full"
        }`}
      >
        <div className="flex items-center justify-between p-4 border-b border-earth-200">
          <div className="flex items-center gap-2">
            <MessageCircle className="w-5 h-5 text-primary-600" />
            <h2 className="font-semibold text-earth-900">
              {otherName ? `Discussion avec ${otherName}` : "Discussion"}
            </h2>
          </div>
          <button onClick={onClose} className="p-1 rounded-lg hover:bg-earth-100">
            <X className="w-5 h-5 text-earth-500" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-4 space-y-3">
          {isLoading ? (
            <div className="flex justify-center py-10">
              <Loader2 className="w-6 h-6 text-primary-600 animate-spin" />
            </div>
          ) : messages && messages.length > 0 ? (
            messages.map((msg) => (
              <div
                key={msg.id}
                className={`flex ${isMe(msg) ? "justify-end" : "justify-start"}`}
              >
                <div
                  className={`max-w-[80%] rounded-2xl px-4 py-2 text-sm ${
                    isMe(msg)
                      ? "bg-primary-600 text-white rounded-br-none"
                      : "bg-earth-100 text-earth-900 rounded-bl-none"
                  }`}
                >
                  <p>{msg.contenu}</p>
                  <p
                    className={`text-xs mt-1 ${
                      isMe(msg) ? "text-primary-100" : "text-earth-500"
                    }`}
                  >
                    {formatDateTime(msg.date_envoi)}
                  </p>
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-10 text-earth-500 text-sm">
              Aucun message pour le moment.
              <br />
              Démarrez la conversation.
            </div>
          )}
          <div ref={bottomRef} />
        </div>

        <form
          onSubmit={handleSend}
          className="p-4 border-t border-earth-200 flex items-center gap-2"
        >
          <input
            type="text"
            value={contenu}
            onChange={(e) => setContenu(e.target.value)}
            placeholder="Écrivez un message..."
            className="flex-1 input-field"
            disabled={sendMutation.isLoading}
          />
          <button
            type="submit"
            disabled={sendMutation.isLoading || !contenu.trim()}
            className="btn-primary p-3"
          >
            {sendMutation.isLoading ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Send className="w-4 h-4" />
            )}
          </button>
        </form>
      </div>
    </>
  );
};
