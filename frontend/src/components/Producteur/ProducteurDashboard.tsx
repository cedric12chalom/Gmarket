import React, { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "react-query";
import { AlertCircle, DollarSign, Package, Plus, TrendingUp, Send } from "lucide-react";
import { toast } from "react-toastify";
import { commandeApi, lotApi, produitApi, demandeProduitApi } from "@/services/api";
import { useAuthContext } from "@/contexts/AuthContext";
import { formatPrice, formatDate, removalUrgency } from "@/utils/helpers";
import { Produit } from "@/types";
import { FreshnessBadge } from "@/components/Catalogue/FreshnessBadge";
import { PositionPicker } from "@/components/GeoMap/PositionPicker";

const emptyForm = {
  produit_id: "",
  quantite_disponible: "",
  prix_producteur: "",
  date_recolte: new Date().toISOString().slice(0, 10),
  duree_conservation: "",
  variete_id: "",
  lieu_production: "",
  latitude: "",
  longitude: "",
};

export const ProducteurDashboard: React.FC = () => {
  const { user } = useAuthContext();
  const queryClient = useQueryClient();
  const [form, setForm] = useState({
    ...emptyForm,
    latitude: user?.latitude != null ? String(user.latitude) : "",
    longitude: user?.longitude != null ? String(user.longitude) : "",
  });

  const lotsQuery = useQuery(["lots-producteur", user?.id], () => lotApi.getByProducteur(user!.id).then(r => r.data), { enabled: !!user });
  const commandesQuery = useQuery(["commandes-producteur", user?.id], () => commandeApi.getByProducteur(user!.id).then(r => r.data), { enabled: !!user });
  const produitsQuery = useQuery("produits", () => produitApi.getAll().then(r => r.data), { enabled: !!user });
  const demandesQuery = useQuery(["demandes-produit", user?.id], () => demandeProduitApi.getAll().then(r => r.data), { enabled: !!user });

  const lots = lotsQuery.data || [];
  const commandes = commandesQuery.data || [];
  const produits = produitsQuery.data || [];
  const demandes = demandesQuery.data || [];
  const selectedProduit = useMemo(
    () => produits.find((produit: Produit) => String(produit.id) === form.produit_id),
    [form.produit_id, produits]
  );

  const createLot = useMutation((data: typeof emptyForm) => lotApi.create({
    produit_id: Number(data.produit_id),
    quantite_disponible: Number(data.quantite_disponible),
    prix_producteur: Number(data.prix_producteur),
    date_recolte: data.date_recolte,
    duree_conservation: Number(data.duree_conservation),
    variete_id: data.variete_id ? Number(data.variete_id) : null,
    lieu_production: data.lieu_production || null,
    latitude: data.latitude !== "" ? Number(data.latitude) : null,
    longitude: data.longitude !== "" ? Number(data.longitude) : null,
  }), {
    onSuccess: () => {
      toast.success("Lot ajoute avec succes");
      setForm({
        ...emptyForm,
        latitude: user?.latitude != null ? String(user.latitude) : "",
        longitude: user?.longitude != null ? String(user.longitude) : "",
      });
      queryClient.invalidateQueries(["lots-producteur", user?.id]);
    },
    onError: (error: any) => {
      // The global axios interceptor already shows a toast for this, but we
      // surface it here too in case that ever changes, so a failed
      // creation is never silent.
      const message = error?.response?.data?.message || "Impossible d'ajouter ce lot.";
      toast.error(message);
    },
  });

  const [demandeForm, setDemandeForm] = useState({ nom_produit: "", categorie_suggeree: "", description: "", unite: "" });
  const createDemande = useMutation((data: typeof demandeForm) => demandeProduitApi.create(data), {
    onSuccess: () => {
      toast.success("Demande envoyée aux administrateurs.");
      setDemandeForm({ nom_produit: "", categorie_suggeree: "", description: "", unite: "" });
      queryClient.invalidateQueries(["demandes-produit", user?.id]);
    },
  });

  const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
  };

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    createLot.mutate(form);
  };

  const stats = [
    { label: "Lots actifs", value: lots.filter((l: any) => l.statut === "disponible").length, icon: Package, color: "bg-blue-100 text-blue-700" },
    { label: "Ventes", value: commandes.length, icon: TrendingUp, color: "bg-primary-100 text-primary-700" },
    { label: "Revenus", value: formatPrice(commandes.reduce((sum: number, c: any) => sum + (c.montant_total || 0), 0)), icon: DollarSign, color: "bg-secondary-100 text-secondary-700" },
    { label: "Alertes stock", value: lots.filter((l: any) => (l.quantite_disponible - l.quantite_reservee) < 5).length, icon: AlertCircle, color: "bg-red-100 text-red-700" },
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-earth-900 mb-6">Espace Producteur</h1>
      {(lotsQuery.isError || commandesQuery.isError || produitsQuery.isError) && (
        <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          Certaines donnees n'ont pas pu etre chargees. Verifiez que l'API Symfony est demarree puis reessayez.
        </div>
      )}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {stats.map((stat) => (
          <div key={stat.label} className="card p-4">
            <div className={`w-10 h-10 rounded-lg flex items-center justify-center mb-3 ${stat.color}`}><stat.icon className="w-5 h-5" /></div>
            <p className="text-2xl font-bold text-earth-900">{stat.value}</p>
            <p className="text-sm text-earth-500">{stat.label}</p>
          </div>
        ))}
      </div>
      <div className="grid lg:grid-cols-[minmax(0,1fr)_380px] gap-6">
      <div className="card">
        <h3 className="font-semibold text-earth-900 mb-4">Mes lots</h3>
        <div className="space-y-3">
          {(lots || []).map((lot: any) => {
            const urgency = removalUrgency(lot.jours_avant_retrait);
            return (
              <div key={lot.id} className="p-3 bg-earth-50 rounded-lg">
                <div className="flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <p className="font-medium text-earth-900 text-sm">
                      {lot.produit?.nom || "Produit"}{lot.variete ? ` - ${lot.variete.nom}` : ""}
                    </p>
                    <p className="text-xs text-earth-500">{(lot.quantite_disponible - lot.quantite_reservee)} {lot.produit?.unite || "unité"} dispo</p>
                  </div>
                  <div className="flex items-center gap-3 flex-shrink-0">
                    <FreshnessBadge index={lot.indice_fraicheur} size="sm" />
                    <span className="text-sm font-semibold text-primary-600">{formatPrice(lot.prix_producteur)}</span>
                  </div>
                </div>
                <div className="mt-2 pt-2 border-t border-earth-200 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-earth-600">
                  <span>Recolte : {lot.date_recolte ? formatDate(lot.date_recolte) : "—"}</span>
                  <span>Expiration : {lot.date_expiration ? formatDate(lot.date_expiration) : "—"}</span>
                  <span className="font-semibold" style={{ color: urgency.color }}>{urgency.label}</span>
                </div>
              </div>
            );
          })}
        </div>
      </div>
        <form onSubmit={handleSubmit} className="card space-y-4">
          <div className="flex items-center gap-2">
            <Plus className="w-5 h-5 text-primary-600" />
            <h3 className="font-semibold text-earth-900">Ajouter un lot</h3>
          </div>

          <label className="block">
            <span className="text-sm font-medium text-earth-700">Produit</span>
            <select
              name="produit_id"
              value={form.produit_id}
              onChange={(event) => setForm((current) => ({ ...current, produit_id: event.target.value, variete_id: "" }))}
              required
              className="input-field mt-1"
            >
              <option value="">Selectionner un produit</option>
              {produits.map((produit: Produit) => (
                <option key={produit.id} value={produit.id}>
                  {produit.nom} ({formatPrice(produit.prix_min)} - {formatPrice(produit.prix_max)})
                </option>
              ))}
            </select>
          </label>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label className="block">
              <span className="text-sm font-medium text-earth-700">Quantite</span>
              <input name="quantite_disponible" type="number" min="0.01" step="0.01" value={form.quantite_disponible} onChange={handleChange} required className="input-field mt-1" />
            </label>
            <label className="block">
              <span className="text-sm font-medium text-earth-700">Prix</span>
              <input name="prix_producteur" type="number" min={selectedProduit?.prix_min ?? 0} max={selectedProduit?.prix_max ?? undefined} step="0.01" value={form.prix_producteur} onChange={handleChange} required className="input-field mt-1" />
              {selectedProduit && (
                <span className="text-xs text-earth-400 mt-1 block">
                  Entre {formatPrice(selectedProduit.prix_min)} et {formatPrice(selectedProduit.prix_max)}
                </span>
              )}
            </label>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label className="block">
              <span className="text-sm font-medium text-earth-700">Recolte</span>
              <input name="date_recolte" type="date" value={form.date_recolte} onChange={handleChange} required className="input-field mt-1" />
            </label>
            <label className="block">
              <span className="text-sm font-medium text-earth-700">Conservation</span>
              <input name="duree_conservation" type="number" min="1" value={form.duree_conservation} onChange={handleChange} required className="input-field mt-1" />
            </label>
          </div>

          <label className="block">
            <span className="text-sm font-medium text-earth-700">Variete</span>
            <select
              name="variete_id"
              value={form.variete_id}
              onChange={handleChange}
              disabled={!selectedProduit || (selectedProduit.varietes || []).length === 0}
              className="input-field mt-1 disabled:bg-earth-100 disabled:text-earth-400"
            >
              <option value="">Aucune variete precisee</option>
              {(selectedProduit?.varietes || []).map((variete: { id: number; nom: string }) => (
                <option key={variete.id} value={variete.id}>
                  {variete.nom}
                </option>
              ))}
            </select>
          </label>

          <PositionPicker
            value={{
              latitude: form.latitude !== "" ? Number(form.latitude) : null,
              longitude: form.longitude !== "" ? Number(form.longitude) : null,
            }}
            onChange={({ latitude, longitude }) => {
              setForm((current) => ({
                ...current,
                latitude: latitude != null ? String(latitude) : "",
                longitude: longitude != null ? String(longitude) : "",
              }));
            }}
            description={form.lieu_production || ""}
            onDescriptionChange={(v) => setForm((current) => ({ ...current, lieu_production: v }))}
            descriptionLabel="Lieu de production (description)"
            descriptionPlaceholder="Ex. Plantation de Mbankomo, bord de la nationale…"
          />
          <p className="text-xs text-earth-400">Laissez la position vide pour utiliser celle du producteur.</p>

          <button type="submit" disabled={createLot.isLoading || produits.length === 0} className="btn-primary w-full">
            {createLot.isLoading ? "Ajout en cours..." : "Publier le lot"}
          </button>
          {produits.length === 0 && !produitsQuery.isLoading && (
            <p className="text-xs text-earth-500">Aucun produit disponible. Un administrateur doit d'abord creer les produits.</p>
          )}
        </form>
      </div>

      <div className="mt-8 card">
        <div className="flex items-center gap-2 mb-4">
          <Send className="w-5 h-5 text-primary-600" />
          <h3 className="font-semibold text-earth-900">Demander l'ajout d'un produit</h3>
        </div>
        <p className="text-sm text-earth-500 mb-4">
          Le produit souhaité n'est pas dans le catalogue ? Soumettez une demande à l'administration.
        </p>
        <div className="grid sm:grid-cols-2 gap-3 mb-3">
          <label className="block">
            <span className="text-sm font-medium text-earth-700">Nom du produit *</span>
            <input
              type="text"
              value={demandeForm.nom_produit}
              onChange={(e) => setDemandeForm({ ...demandeForm, nom_produit: e.target.value })}
              required
              className="input-field mt-1"
              placeholder="Ex. Manioc rouge"
            />
          </label>
          <label className="block">
            <span className="text-sm font-medium text-earth-700">Catégorie suggérée</span>
            <input
              type="text"
              value={demandeForm.categorie_suggeree}
              onChange={(e) => setDemandeForm({ ...demandeForm, categorie_suggeree: e.target.value })}
              className="input-field mt-1"
              placeholder="Ex. Racines et tubercules"
            />
          </label>
        </div>
        <div className="grid sm:grid-cols-2 gap-3 mb-3">
          <label className="block">
            <span className="text-sm font-medium text-earth-700">Unité</span>
            <input
              type="text"
              value={demandeForm.unite}
              onChange={(e) => setDemandeForm({ ...demandeForm, unite: e.target.value })}
              className="input-field mt-1"
              placeholder="Ex. kg, botte, pièce"
            />
          </label>
          <label className="block">
            <span className="text-sm font-medium text-earth-700">Description</span>
            <input
              type="text"
              value={demandeForm.description}
              onChange={(e) => setDemandeForm({ ...demandeForm, description: e.target.value })}
              className="input-field mt-1"
              placeholder="Variétés, conditions de culture..."
            />
          </label>
        </div>
        <button
          onClick={() => {
            if (!demandeForm.nom_produit.trim()) { toast.error("Le nom du produit est requis."); return; }
            createDemande.mutate(demandeForm);
          }}
          disabled={createDemande.isLoading || !demandeForm.nom_produit.trim()}
          className="btn-primary"
        >
          {createDemande.isLoading ? "Envoi..." : "Envoyer la demande"}
        </button>

        {demandes.length > 0 && (
          <div className="mt-6">
            <h4 className="text-sm font-semibold text-earth-700 mb-2">Mes demandes</h4>
            <div className="space-y-2">
              {demandes.map((d: any) => (
                <div key={d.id} className="flex items-center justify-between p-3 bg-earth-50 rounded-lg text-sm">
                  <div>
                    <span className="font-medium text-earth-900">{d.nom_produit}</span>
                    {d.categorie_suggeree && <span className="text-earth-500 ml-2">({d.categorie_suggeree})</span>}
                  </div>
                  <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                    d.statut === 'approuvee' ? 'bg-emerald-100 text-emerald-700'
                    : d.statut === 'refusee' ? 'bg-red-100 text-red-700'
                    : 'bg-amber-100 text-amber-700'
                  }`}>
                    {d.statut === 'en_attente' ? 'En attente' : d.statut === 'approuvee' ? 'Approuvée' : 'Refusée'}
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
