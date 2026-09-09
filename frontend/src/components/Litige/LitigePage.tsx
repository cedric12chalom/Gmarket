import React, { useState } from "react";
import { useQuery, useQueryClient } from "react-query";
import { litigeApi } from "@/services/api";
import { AlertTriangle, Loader2, Plus, X, User, Truck } from "lucide-react";
import { toast } from "react-toastify";
import type { Litige } from "@/types";

const STATUT_BADGE: Record<string, string> = {
  ouvert: "badge-danger",
  en_traitement: "badge-warning",
  resolu: "badge-success",
  rejete: "badge-info",
};

const MOTIF_LABELS: Record<string, string> = {
  produit_abime: "Produit abim\u00e9",
  livraison_retard: "Livraison en retard",
  produit_non_conforme: "Produit non conforme",
  quantite_incorrecte: "Quantit\u00e9 incorrecte",
  autre: "Autre",
};

export const LitigePage: React.FC = () => {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [form, setForm] = useState({ commande_id: "", motif: "", description: "" });

  const { data: litiges, isLoading } = useQuery(["litiges"], () =>
    litigeApi.getAll().then((r) => r.data)
  );

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await litigeApi.create({
        commande_id: parseInt(form.commande_id, 10),
        motif: form.motif,
        description: form.description,
      });
      queryClient.invalidateQueries(["litiges"]);
      setShowForm(false);
      setForm({ commande_id: "", motif: "", description: "" });
      toast.success("Litige ouvert");
    } catch (err: any) {
      toast.error(err?.response?.data?.message || "Erreur");
    } finally {
      setSubmitting(false);
    }
  };

  const renderParties = (l: Litige) => {
    const acheteur = l.commande?.acheteur;
    const livreur = l.livraison?.livreur;
    return (
      <div className="flex flex-wrap gap-4 mt-3 text-sm">
        {acheteur && (
          <div className="flex items-center gap-2 bg-primary-50 text-primary-800 rounded-lg px-3 py-1.5">
            <User className="w-4 h-4" />
            <span className="font-medium">{acheteur.prenom} {acheteur.nom}</span>
            <span className="text-primary-600">(acheteur{acheteur.ville ? `, ${acheteur.ville}` : ""})</span>
          </div>
        )}
        {livreur && (
          <div className="flex items-center gap-2 bg-green-50 text-green-800 rounded-lg px-3 py-1.5">
            <Truck className="w-4 h-4" />
            <span className="font-medium">{livreur.prenom} {livreur.nom}</span>
            <span className="text-green-600">(livreur{livreur.ville ? `, ${livreur.ville}` : ""})</span>
          </div>
        )}
        {!acheteur && !livreur && (
          <span className="text-earth-400 italic">Aucune partie identifi\u00e9e</span>
        )}
      </div>
    );
  };

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-earth-900 flex items-center gap-2">
          <AlertTriangle className="w-6 h-6 text-red-500" /> Litiges
        </h1>
        <button onClick={() => setShowForm(!showForm)} className="btn-primary flex items-center gap-2">
          <Plus className="w-4 h-4" /> Ouvrir un litige
        </button>
      </div>

      {showForm && (
        <div className="card mb-6">
          <div className="flex justify-between items-center mb-4">
            <h2 className="font-semibold text-earth-900">Nouveau litige</h2>
            <button onClick={() => setShowForm(false)}><X className="w-5 h-5" /></button>
          </div>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="label">ID Commande</label>
              <input type="number" className="input-field" value={form.commande_id}
                onChange={(e) => setForm({ ...form, commande_id: e.target.value })} required />
            </div>
            <div>
              <label className="label">Motif</label>
              <select className="input-field" value={form.motif}
                onChange={(e) => setForm({ ...form, motif: e.target.value })} required>
                <option value="">S\u00e9lectionner</option>
                <option value="produit_abime">Produit ab\u00eem\u00e9</option>
                <option value="livraison_retard">Livraison en retard</option>
                <option value="produit_non_conforme">Produit non conforme</option>
                <option value="quantite_incorrecte">Quantit\u00e9 incorrecte</option>
                <option value="autre">Autre</option>
              </select>
            </div>
            <div>
              <label className="label">Description</label>
              <textarea className="input-field" rows={3} value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })} />
            </div>
            <button type="submit" className="btn-primary" disabled={submitting}>
              {submitting ? <Loader2 className="w-4 h-4 animate-spin inline" /> : null} Envoyer
            </button>
          </form>
        </div>
      )}

      {isLoading ? (
        <div className="flex justify-center py-20">
          <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
        </div>
      ) : !litiges || litiges.length === 0 ? (
        <div className="card text-center py-16">
          <AlertTriangle className="w-12 h-12 text-earth-300 mx-auto mb-4" />
          <p className="text-earth-500">Aucun litige</p>
        </div>
      ) : (
        <div className="space-y-3">
          {litiges.map((l: Litige) => (
            <div key={l.id} className="card">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="font-semibold text-earth-900">
                    {MOTIF_LABELS[l.motif] || l.motif}
                  </p>
                  <p className="text-sm text-earth-500 mt-1">
                    Litige #{l.id} &middot; Commande #{l.commande?.id} &middot;{" "}
                    {new Date(l.date_creation).toLocaleDateString()}
                  </p>
                  {renderParties(l)}
                  {l.description && <p className="text-sm text-earth-600 mt-2">{l.description}</p>}
                  {l.resolution && (
                    <p className="text-sm bg-earth-50 p-2 rounded mt-2">
                      <span className="font-medium">R\u00e9solution :</span> {l.resolution}
                    </p>
                  )}
                  {l.admin_traiteur && (
                    <p className="text-xs text-earth-400 mt-1">
                      Tra\u00eet\u00e9 par {l.admin_traiteur.prenom} {l.admin_traiteur.nom}
                    </p>
                  )}
                </div>
                <span className={`badge ${STATUT_BADGE[l.statut] || "badge-info"}`}>
                  {l.statut}
                </span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};