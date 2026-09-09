import React, { useState } from "react";
import { useParams } from "react-router-dom";
import { useQuery } from "react-query";
import { Package, Truck, CreditCard, MapPin, Calendar, Leaf, MessageCircle, Phone, AlertTriangle, CheckCircle, Loader2, X } from "lucide-react";
import { commandeApi, livraisonApi, messageApi, litigeApi } from "@/services/api";
import { formatPrice, formatDateTime, orderStatusLabel, orderStatusColor, deliveryStatusLabel } from "@/utils/helpers";
import { ChatDrawer } from "@/components/Messages/ChatDrawer";
import { Message } from "@/types";
import { useAuthContext } from "@/contexts/AuthContext";
import { toast } from "react-toastify";

export const CommandeDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { user: currentUser } = useAuthContext();
  const [chatOpen, setChatOpen] = useState(false);
  const [litigeOpen, setLitigeOpen] = useState(false);
  const [litigeForm, setLitigeForm] = useState({ motif: "", description: "" });

  const { data: commande, isLoading, refetch: refetchCommande } = useQuery(["commande", id], () => commandeApi.getById(Number(id)).then(r => r.data));
  const { data: livraison, refetch: refetchLivraison } = useQuery(
    ["livraison-commande", id],
    () => livraisonApi.getByCommande(Number(id)).then(r => r.data),
    { enabled: !!commande && commande.mode_recuperation === "livraison" }
  );
  const { data: unreadCount } = useQuery(
    ["messages-unread-commande", livraison?.id],
    async () => {
      const messages = await messageApi.getByLivraison(livraison.id).then(r => r.data as Message[]);
      return messages.filter((m: Message) => !m.lu && m.expediteur.id !== commande?.acheteur?.id).length;
    },
    { enabled: !!livraison && !!livraison.livreur, refetchInterval: 10000 }
  );

  const [litigeSubmitting, setLitigeSubmitting] = useState(false);

  const handleCreateLitige = async (data: { commande_id: number; motif: string; description?: string }) => {
    setLitigeSubmitting(true);
    try {
      await litigeApi.create(data);
      setLitigeOpen(false);
      setLitigeForm({ motif: "", description: "" });
      toast.success("Litige ouvert");
    } catch (err: any) {
      toast.error(err?.response?.data?.message || "Erreur");
    } finally {
      setLitigeSubmitting(false);
    }
  };

  const [receptionSubmitting, setReceptionSubmitting] = useState(false);

  const handleConfirmerReception = async () => {
    setReceptionSubmitting(true);
    try {
      await commandeApi.confirmerReception(commande.id);
      toast.success("Commande marquée comme livrée, merci !");
      refetchLivraison();
      refetchCommande();
    } catch (err: any) {
      toast.error(err?.response?.data?.message || "Erreur");
    } finally {
      setReceptionSubmitting(false);
    }
  };

  if (isLoading) return <div className="flex justify-center py-20"><div className="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin" /></div>;
  if (!commande) return <div className="text-center py-20 text-earth-500">Commande non trouvée</div>;

  const steps = [
    { label: "Commande passée", done: true },
    { label: "Confirmée", done: ["confirmee", "payee", "en_cours", "livree"].includes(commande.statut) },
    { label: "Payée", done: ["payee", "en_cours", "livree"].includes(commande.statut) },
    { label: "En livraison", done: !!livraison && ["en_cours", "livree"].includes(livraison.statut) },
    { label: "Livrée", done: commande.statut === "livree" },
  ];

  const livreur = livraison?.livreur;
  const livreurPhone = livreur?.telephone_service || livreur?.telephone || "";
  const chatActif = livreur?.chat_actif ?? false;

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-earth-900">Commande #{commande.id.toString().padStart(4, "0")}</h1>
        <span className={orderStatusColor(commande.statut)}>{orderStatusLabel(commande.statut)}</span>
      </div>
      <div className="card mb-6">
        <div className="flex items-center justify-between relative">
          <div className="absolute top-1/2 left-0 right-0 h-0.5 bg-earth-200 -translate-y-1/2" />
          {steps.map((step, i) => (
            <div key={i} className="relative z-10 flex flex-col items-center gap-2">
              <div className={`w-8 h-8 rounded-full flex items-center justify-center ${step.done ? "bg-primary-600 text-white" : "bg-earth-200 text-earth-400"}`}>{i + 1}</div>
              <span className={`text-xs ${step.done ? "text-primary-700 font-medium" : "text-earth-400"}`}>{step.label}</span>
            </div>
          ))}
        </div>
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="card">
          <h3 className="font-semibold text-earth-900 mb-4 flex items-center gap-2"><Package className="w-5 h-5 text-primary-600" /> Articles</h3>
          <div className="space-y-4">
            {(commande.lignes || []).map((ligne: any) => (
              <div key={ligne.id} className="flex gap-4 p-3 bg-earth-50 rounded-xl">
                {ligne.lot?.produit?.photo ? (
                  <img src={ligne.lot.produit.photo} alt={ligne.lot.produit.nom || 'Produit'} className="w-16 h-16 rounded-lg object-cover" />
                ) : (
                  <div className="w-16 h-16 bg-white rounded-lg flex items-center justify-center"><Leaf className="w-6 h-6 text-earth-300" /></div>
                )}
                <div className="flex-1">
                  <p className="font-medium text-earth-900">{ligne.lot?.produit?.nom || "Produit"}</p>
                  <p className="text-sm text-earth-500">{ligne.quantite} {ligne.lot?.produit?.unite || "unité"} × {formatPrice(ligne.prix_unitaire)}</p>
                  <p className="text-sm font-semibold text-primary-600 mt-1">{formatPrice(ligne.sous_total)}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
        <div className="space-y-6">
          <div className="card">
            <h3 className="font-semibold text-earth-900 mb-4 flex items-center gap-2"><CreditCard className="w-5 h-5 text-primary-600" /> Paiement</h3>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between text-earth-500"><span>Sous-total</span><span>{formatPrice(commande.montant_produits || 0)}</span></div>
              <div className="flex justify-between text-earth-500"><span>Commission</span><span>{formatPrice(commande.commission || 0)}</span></div>
              <div className="flex justify-between text-earth-500"><span>Livraison</span><span>{formatPrice(commande.frais_livraison || 0)}</span></div>
              <div className="border-t border-earth-200 pt-2 flex justify-between text-lg font-bold"><span>Total</span><span className="text-primary-600">{formatPrice(commande.montant_total)}</span></div>
            </div>
          </div>
          <div className="card">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-semibold text-earth-900 flex items-center gap-2"><Truck className="w-5 h-5 text-primary-600" /> Livraison</h3>
              {livreur && (
                <div className="flex items-center gap-2">
                  {livreurPhone && (
                    <a
                      href={`tel:${livreurPhone}`}
                      className="flex items-center gap-1.5 text-sm text-primary-600 hover:underline"
                      aria-label="Appeler le livreur"
                    >
                      <Phone className="w-4 h-4" />
                      {livreurPhone}
                    </a>
                  )}
                  {chatActif && (
                    <button
                      onClick={() => setChatOpen(true)}
                      className="relative p-2 rounded-lg hover:bg-earth-100 text-primary-600"
                      aria-label="Ouvrir la discussion"
                    >
                      <MessageCircle className="w-5 h-5" />
                      {(unreadCount ?? 0) > 0 && (
                        <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                          {unreadCount! > 9 ? "9+" : unreadCount}
                        </span>
                      )}
                    </button>
                  )}
                </div>
              )}
            </div>
            <div className="space-y-3 text-sm">
              <p className="flex items-center gap-2 text-earth-600"><MapPin className="w-4 h-4 text-primary-500" />Mode: Livraison à domicile</p>
              <p className="flex items-center gap-2 text-earth-600"><Calendar className="w-4 h-4 text-primary-500" />Date: {formatDateTime(commande.date_commande)}</p>
              {livraison && (
                <>
                  <p className="flex items-center gap-2 text-earth-600"><span className="font-medium">Statut:</span> {deliveryStatusLabel(livraison.statut)}</p>
                  {livreur && (
                    <p className="flex items-center gap-2 text-earth-600"><span className="font-medium">Livreur:</span> {livreur.prenom} {livreur.nom}</p>
                  )}
                </>
              )}
            </div>
            <button onClick={() => setLitigeOpen(true)} className="mt-3 w-full btn-outline text-sm flex items-center justify-center gap-2">
              <AlertTriangle className="w-4 h-4 text-red-500" /> Signaler un problème
            </button>
            {livraison && livraison.statut === "livree" && (
              livraison.date_reception_confirmee ? (
                <p className="mt-3 flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2">
                  <CheckCircle className="w-4 h-4" /> Réception confirmée le {formatDateTime(livraison.date_reception_confirmee)}
                </p>
              ) : (
                currentUser?.id === commande.acheteur?.id && (
                  <button
                    onClick={handleConfirmerReception}
                    disabled={receptionSubmitting}
                    className="mt-3 w-full btn-primary text-sm flex items-center justify-center gap-2"
                  >
                    {receptionSubmitting ? <Loader2 className="w-4 h-4 animate-spin" /> : <CheckCircle className="w-4 h-4" />} Valider la réception de la commande
                  </button>
                )
              )
            )}
          </div>
        </div>
      </div>

      {litigeOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setLitigeOpen(false)}>
          <div className="bg-white rounded-2xl p-6 w-full max-w-md mx-4 shadow-2xl" onClick={(e) => e.stopPropagation()}>
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold text-lg text-earth-900">Signaler un problème</h3>
              <button onClick={() => setLitigeOpen(false)}><X className="w-5 h-5" /></button>
            </div>
            <form onSubmit={(e) => { e.preventDefault(); handleCreateLitige({ commande_id: commande.id, motif: litigeForm.motif, description: litigeForm.description }); }} className="space-y-4">
              <div>
                <label className="label">Motif</label>
                <select className="input-field" value={litigeForm.motif} onChange={(e) => setLitigeForm({ ...litigeForm, motif: e.target.value })} required>
                  <option value="">Sélectionner</option>
                  <option value="produit_abime">Produit abîmé</option>
                  <option value="livraison_retard">Livraison en retard</option>
                  <option value="produit_non_conforme">Produit non conforme</option>
                  <option value="quantite_incorrecte">Quantité incorrecte</option>
                  <option value="autre">Autre</option>
                </select>
              </div>
              <div>
                <label className="label">Description</label>
                <textarea className="input-field" rows={3} value={litigeForm.description} onChange={(e) => setLitigeForm({ ...litigeForm, description: e.target.value })} />
              </div>
              <button type="submit" className="btn-primary w-full" disabled={litigeSubmitting}>
                {litigeSubmitting ? <Loader2 className="w-4 h-4 animate-spin inline" /> : null} Envoyer
              </button>
            </form>
          </div>
        </div>
      )}

      <ChatDrawer
        livraisonId={livraison?.id ?? 0}
        otherName={livraison?.livreur ? `${livraison.livreur.prenom} ${livraison.livreur.nom}` : undefined}
        isOpen={chatOpen}
        onClose={() => setChatOpen(false)}
      />
    </div>
  );
};