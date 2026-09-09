import React, { useState } from "react";
import { Link } from "react-router-dom";
import { useQuery } from "react-query";
import { Truck, CheckCircle, Clock, MapPin, MessageCircle, Navigation, ShieldAlert, ShieldCheck } from "lucide-react";
import { livraisonApi, messageApi } from "@/services/api";
import { useAuthContext } from "@/contexts/AuthContext";
import { formatPrice, deliveryStatusLabel } from "@/utils/helpers";
import { ChatDrawer } from "@/components/Messages/ChatDrawer";
import { Livreur } from "@/types";
import { toast } from "react-toastify";

const STATUT_VERIFICATION_LABELS: Record<string, string> = {
  en_attente: "Dossier en attente de validation",
  verifie: "Dossier en cours de vérification",
  valide: "Dossier validé",
  refuse: "Dossier refusé",
};

export const LivreurDashboard: React.FC = () => {
  const { user } = useAuthContext();
  const [chatLivraisonId, setChatLivraisonId] = useState<number | null>(null);
  const livreurUser = user as Livreur | null;
  const statutVerification = livreurUser?.statut_verification ?? "en_attente";
  const dossierValide = statutVerification === "valide";
  const { data: livraisons, refetch: refetchMines } = useQuery(["livraisons-livreur", user?.id], () => livraisonApi.getByLivreur(user!.id).then(r => r.data), { enabled: !!user });
  const { data: disponibles, refetch: refetchDisponibles } = useQuery(["livraisons-disponibles"], () => livraisonApi.getDisponibles().then(r => r.data), { enabled: !!user && dossierValide, refetchInterval: 15000 });
  const { data: unreadCounts } = useQuery(
    ["messages-unread-livreur", user?.id],
    async () => {
      const counts: Record<number, number> = {};
      for (const l of (livraisons || [])) {
        try {
          const messages = await messageApi.getByLivraison(l.id).then(r => r.data);
          counts[l.id] = messages.filter((m: any) => m.expediteur.id !== user?.id && !m.lu).length;
        } catch {
          counts[l.id] = 0;
        }
      }
      return counts;
    },
    { enabled: !!user && (livraisons || []).length > 0, refetchInterval: 10000 }
  );

  const handleAccepter = async (id: number) => {
    try {
      await livraisonApi.accepter(id);
      toast.success("Mission acceptée !");
      refetchMines();
      refetchDisponibles();
    } catch (error: any) {
      // La mission a pu être prise par un autre livreur entre-temps.
      toast.error(error?.response?.data?.message || "Cette mission n'est plus disponible.");
      refetchDisponibles();
    }
  };

  const handleConfirmerLivraison = async (id: number) => {
    try {
      await livraisonApi.confirmerLivraison(id, { photo_livraison: "" });
      toast.success("Livraison confirmée. L'acheteur validera la réception de la commande.");
      refetchMines();
    } catch (error: any) {
      toast.error(error?.response?.data?.message || "Impossible de confirmer la livraison.");
    }
  };

  const missionsDisponibles = disponibles || [];
  const missionsEnCours = (livraisons || []).filter((l: any) => l.statut === "en_cours");
  const missionsTerminees = (livraisons || []).filter((l: any) => l.statut === "livree");
  const activeChatLivraison = missionsEnCours.find((l: any) => l.id === chatLivraisonId);

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-earth-900 mb-6">Espace Livreur</h1>
      {!dossierValide && (
        <div className={`mb-6 p-4 rounded-xl flex items-start gap-3 ${statutVerification === "refuse" ? "bg-red-50 border border-red-200" : "bg-amber-50 border border-amber-200"}`}>
          <div className={`mt-0.5 ${statutVerification === "refuse" ? "text-red-500" : "text-amber-500"}`}>
            {statutVerification === "refuse" ? <ShieldAlert className="w-5 h-5" /> : <ShieldCheck className="w-5 h-5" />}
          </div>
          <div>
            <p className="font-semibold text-earth-900 text-sm">{STATUT_VERIFICATION_LABELS[statutVerification]}</p>
            <p className="text-sm text-earth-600 mt-0.5">
              {statutVerification === "refuse"
                ? livreurUser?.motif_refus
                  ? `Motif : ${livreurUser.motif_refus}. Corrigez votre dossier puis contactez l'administrateur.`
                  : "Votre dossier a été refusé. Contactez l'administrateur pour plus de détails."
                : "Un administrateur vérifie actuellement votre dossier. Les missions deviendront disponibles dès sa validation."}
            </p>
          </div>
        </div>
      )}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div className="card p-4"><div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3"><Truck className="w-5 h-5 text-blue-700" /></div><p className="text-2xl font-bold text-earth-900">{missionsDisponibles.length}</p><p className="text-sm text-earth-500">Missions disponibles</p></div>
        <div className="card p-4"><div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mb-3"><Clock className="w-5 h-5 text-primary-700" /></div><p className="text-2xl font-bold text-earth-900">{missionsEnCours.length}</p><p className="text-sm text-earth-500">En cours</p></div>
        <div className="card p-4"><div className="w-10 h-10 bg-secondary-100 rounded-lg flex items-center justify-center mb-3"><CheckCircle className="w-5 h-5 text-secondary-700" /></div><p className="text-2xl font-bold text-earth-900">{missionsTerminees.length}</p><p className="text-sm text-earth-500">Terminées</p></div>
      </div>
      {dossierValide && missionsDisponibles.length > 0 && (
        <div className="mb-8">
          <h2 className="text-lg font-semibold text-earth-900 mb-4">Nouvelles missions</h2>
          <div className="space-y-4">
            {missionsDisponibles.map((livraison: any) => (
              <div key={livraison.id} className="card flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center"><MapPin className="w-6 h-6 text-primary-600" /></div>
                  <div>
                    <p className="font-semibold text-earth-900">Livraison #{livraison.id}</p>
                    <p className="text-sm text-earth-500">{(livraison.commande?.lignes || []).length} articles · {formatPrice(livraison.frais || 0)}</p>
                    <p className="text-sm text-earth-500 flex items-center gap-1"><MapPin className="w-3.5 h-3.5" /> {livraison.adresse_livraison || "Non précisé"}</p>
                  </div>
                </div>
                <button onClick={() => handleAccepter(livraison.id)} className="btn-primary">Accepter</button>
              </div>
            ))}
          </div>
        </div>
      )}
      {missionsEnCours.length > 0 && (
        <div>
          <h2 className="text-lg font-semibold text-earth-900 mb-4">Missions en cours</h2>
          <div className="space-y-4">
            {missionsEnCours.map((livraison: any) => (
              <div key={livraison.id} className="card">
                <div className="flex items-center justify-between mb-3">
                  <div><p className="font-semibold text-earth-900">Livraison #{livraison.id}</p><span className="badge-info">{deliveryStatusLabel(livraison.statut)}</span></div>
                  <span className="font-semibold text-primary-600">{formatPrice(livraison.frais || 0)}</span>
                </div>
                <div className="flex items-center justify-between border-t border-earth-200 pt-3">
                  <p className="text-sm text-earth-500 flex items-center gap-1"><MapPin className="w-3.5 h-3.5" /> {livraison.adresse_livraison || "Non précisé"}</p>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => handleConfirmerLivraison(livraison.id)}
                      className="btn-primary text-sm"
                    >
                      Confirmer la livraison
                    </button>
                    <Link
                      to={`/delivery-tracker/${livraison.id}`}
                      className="btn-outline text-sm flex items-center gap-1"
                    >
                      <Navigation className="w-4 h-4" /> Suivi
                    </Link>
                    <button
                      onClick={() => setChatLivraisonId(livraison.id)}
                      className="relative p-2 rounded-lg hover:bg-earth-100 text-primary-600"
                      aria-label="Ouvrir la discussion"
                    >
                      <MessageCircle className="w-5 h-5" />
                      {(unreadCounts?.[livraison.id] ?? 0) > 0 && (
                        <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                          {(unreadCounts?.[livraison.id] ?? 0) > 9 ? "9+" : unreadCounts?.[livraison.id]}
                        </span>
                      )}
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      <ChatDrawer
        livraisonId={chatLivraisonId ?? 0}
        otherName={activeChatLivraison?.commande?.acheteur ? `${activeChatLivraison.commande.acheteur.prenom} ${activeChatLivraison.commande.acheteur.nom}` : undefined}
        isOpen={chatLivraisonId !== null}
        onClose={() => setChatLivraisonId(null)}
      />
    </div>
  );
};
