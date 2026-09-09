import React, { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Truck, Smartphone, CheckCircle2, XCircle, Loader2, UserCheck, Users } from "lucide-react";
import { useQuery } from "react-query";
import { useCartContext } from "@/contexts/CartContext";
import { commandeApi, livraisonApi, livreurApi, paiementApi, walletApi } from "@/services/api";
import { formatPrice, transportLabel } from "@/utils/helpers";
import { Livreur } from "@/types";
import { toast } from "react-toastify";

type Etape = "form" | "en_attente" | "valide" | "echoue";
type LivreurChoice = "open" | "specific";

const POLL_INTERVAL_MS = 3000;
const POLL_TIMEOUT_MS = 90000;

export const CheckoutForm: React.FC = () => {
  const { items, totalPrice, clearCart } = useCartContext();
  const navigate = useNavigate();
  const [livreurChoice, setLivreurChoice] = useState<LivreurChoice>("open");
  const [selectedLivreurId, setSelectedLivreurId] = useState<number | null>(null);
  const [numero, setNumero] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<"mobile_money" | "solde">("mobile_money");
  const [loading, setLoading] = useState(false);
  const [etape, setEtape] = useState<Etape>("form");
  const commandeIdRef = useRef<number | null>(null);
  const pollTimer = useRef<ReturnType<typeof setInterval> | null>(null);
  const pollDeadline = useRef<number>(0);

  const commission = items.reduce((sum, item) => {
    const cat = item.lot.produit?.categorie;
    if (!cat) return sum;
    return sum + (cat.commission_fixe || 0) + (item.lot.prix_producteur * (cat.commission_pourcent || 0) / 100) * item.quantite;
  }, 0);

  const lignesPayload = useMemo(
    () => items.map(item => ({ lot_id: item.lot.id, quantite: item.quantite })),
    [items]
  );

  const { data: estimation, isFetching: estimationEnCours, isError: estimationErreur } = useQuery(
    ["estimation-frais", lignesPayload, livreurChoice, selectedLivreurId],
    () => commandeApi.estimerFrais({
      lignes: lignesPayload,
      ...(livreurChoice === "specific" && selectedLivreurId !== null ? { livreur_id: selectedLivreurId } : {}),
    }).then(r => r.data),
    { enabled: items.length > 0, retry: false }
  );

  const livraisonImpossible = estimationErreur || (estimation?.plafond_depasse ?? false);
  const fraisLivraison = estimation?.frais_livraison ?? 0;
  const montantTotal = totalPrice + commission + fraisLivraison;

  const requiredTransport = useMemo(() => {
    for (const item of items) {
      const type = item.lot.produit?.categorie?.type_transport;
      if (type) return type;
    }
    return undefined;
  }, [items]);

  const { data: availableLivreurs, isLoading: loadingLivreurs } = useQuery(
    ["livreurs-disponibles", requiredTransport],
    () => livreurApi.getDisponibles({ type_transport: requiredTransport! }).then(r => r.data as Livreur[]),
    { enabled: !!requiredTransport }
  );

  const { data: walletData, isLoading: loadingSolde } = useQuery(
    ["wallet-solde"],
    () => walletApi.getSolde().then(r => r.data as { solde: number }),
    { retry: false }
  );

  useEffect(() => {
    return () => { if (pollTimer.current) clearInterval(pollTimer.current); };
  }, []);

  const soldeDisponible = walletData?.solde ?? 0;
  const soldeSuffisant = soldeDisponible >= montantTotal;

  const finaliserPaiementSucces = async (commandeId: number, livreurId: number | null) => {
    if (livreurId !== null && commandeId !== null) {
      await assignerLivreurApresPaiement(commandeId, livreurId);
    }
    setEtape("valide");
    clearCart();
    toast.success("Paiement confirmé !");
    setTimeout(() => navigate(`/commandes/${commandeId}`), 1500);
  };

  const surveillerPaiement = (paiementId: number, livreurId: number | null) => {
    pollDeadline.current = Date.now() + POLL_TIMEOUT_MS;
    pollTimer.current = setInterval(async () => {
      try {
        const res = await paiementApi.verifier(paiementId);
        const statut = res.data.statut;
        if (statut === "valide") {
          if (pollTimer.current) clearInterval(pollTimer.current);
          if (commandeIdRef.current !== null) {
            await finaliserPaiementSucces(commandeIdRef.current, livreurId);
          }
        } else if (statut === "echoue") {
          if (pollTimer.current) clearInterval(pollTimer.current);
          setEtape("echoue");
        } else if (Date.now() > pollDeadline.current) {
          if (pollTimer.current) clearInterval(pollTimer.current);
          toast.info("Ça prend plus de temps que prévu. Vous pouvez suivre votre commande dans 'Mes commandes'.");
          navigate(`/commandes/${commandeIdRef.current}`);
        }
      } catch {
        // Une erreur réseau ponctuelle ne doit pas interrompre le suivi ; on réessaiera au prochain tick.
      }
    }, POLL_INTERVAL_MS);
  };

  const handleSubmit = async () => {
    if (items.length === 0) { toast.error("Votre panier est vide"); return; }
    if (paymentMethod === "mobile_money") {
      const numeroPropre = numero.replace(/\D/g, "");
      if (numeroPropre.length < 9) { toast.error("Entrez un numéro Mobile Money valide."); return; }
    }
    if (livreurChoice === "specific" && selectedLivreurId === null) {
      toast.error("Veuillez choisir un livreur.");
      return;
    }
    if (paymentMethod === "solde" && !soldeSuffisant) {
      toast.error(`Solde insuffisant — ${formatPrice(soldeDisponible)} disponible, ${formatPrice(montantTotal)} requis.`);
      return;
    }
    if (estimationEnCours) {
      toast.info("Calcul des frais de livraison en cours, patientez.");
      return;
    }
    if (livraisonImpossible) {
      toast.error("Livraison impossible : votre position ou celle du lot est manquante, ou la distance dépasse le plafond autorisé.");
      return;
    }
    setLoading(true);
    try {
      const commandeData: any = { lignes: lignesPayload, mode_recuperation: "livraison" };
      if (livreurChoice === "specific" && selectedLivreurId !== null) {
        commandeData.livreur_id = selectedLivreurId;
      }
      const commandeRes = await commandeApi.create(commandeData);
      commandeIdRef.current = commandeRes.data.id;
      const montant = commandeRes.data.montant_total ?? montantTotal;

      if (paymentMethod === "solde") {
        await paiementApi.create({ commande_id: commandeRes.data.id, montant, methode: "solde" });
        await finaliserPaiementSucces(commandeRes.data.id, selectedLivreurId);
        return;
      }

      const paiementRes = await paiementApi.create({ commande_id: commandeRes.data.id, montant, numero: numero.replace(/\D/g, "") });
      setEtape("en_attente");
      surveillerPaiement(paiementRes.data.id, selectedLivreurId);
    } catch (error: any) {
      toast.error(error?.response?.data?.message || "Erreur lors de la commande");
    } finally {
      setLoading(false);
    }
  };

  const assignerLivreurApresPaiement = async (commandeId: number, livreurId: number) => {
    try {
      const livraisonRes = await livraisonApi.getByCommande(commandeId);
      const livraison = livraisonRes.data;
      if (livraison) {
        await livraisonApi.assigner(livraison.id, livreurId);
      }
    } catch (error: any) {
      toast.error(error?.response?.data?.message || "Impossible d'assigner le livreur choisi.");
    }
  };

  if (etape === "en_attente") {
    return (
      <div className="max-w-md mx-auto px-4 py-20 text-center">
        <Loader2 className="w-12 h-12 text-primary-600 animate-spin mx-auto mb-4" />
        <h2 className="text-xl font-semibold text-earth-900 mb-2">Confirmation du paiement</h2>
        <p className="text-earth-500">Confirmation du paiement en cours... Vous serez redirigé automatiquement.</p>
      </div>
    );
  }

  if (etape === "valide") {
    return (
      <div className="max-w-md mx-auto px-4 py-20 text-center">
        <CheckCircle2 className="w-12 h-12 text-green-600 mx-auto mb-4" />
        <h2 className="text-xl font-semibold text-earth-900 mb-2">Paiement confirmé !</h2>
        <p className="text-earth-500">Redirection vers votre commande...</p>
      </div>
    );
  }

  if (etape === "echoue") {
    return (
      <div className="max-w-md mx-auto px-4 py-20 text-center">
        <XCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
        <h2 className="text-xl font-semibold text-earth-900 mb-2">Le paiement a échoué</h2>
        <p className="text-earth-500 mb-6">La transaction Mobile Money n'a pas abouti. Vous pouvez réessayer.</p>
        <button onClick={() => setEtape("form")} className="btn-primary">Réessayer</button>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-earth-900 mb-6">Finaliser la commande</h1>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <div className="card">
            <h3 className="font-semibold text-earth-900 mb-4">Mode de récupération</h3>
            <div className="p-4 rounded-xl border-2 border-primary-600 bg-primary-50 flex items-center gap-3">
              <Truck className="w-6 h-6 text-primary-600" /><div><p className="font-medium text-earth-900">Livraison à domicile</p><p className="text-sm text-earth-500 mt-1">+ {estimationEnCours ? "Calcul en cours..." : livraisonImpossible ? "Livraison impossible" : `${formatPrice(fraisLivraison)}${estimation?.total_km ? ` · ${estimation.total_km} km` : ""}`}</p></div>
            </div>
          </div>

          <div className="card">
            <h3 className="font-semibold text-earth-900 mb-4">Choix du livreur</h3>
              <div className="space-y-3">
                <button
                  onClick={() => { setLivreurChoice("open"); setSelectedLivreurId(null); }}
                  className={`w-full p-4 rounded-xl border-2 text-left transition-all flex items-start gap-3 ${livreurChoice === "open" ? "border-primary-600 bg-primary-50" : "border-earth-200 hover:border-earth-300"}`}
                >
                  <Users className="w-5 h-5 text-primary-600 mt-0.5" />
                  <div>
                    <p className="font-medium text-earth-900">N'importe quel livreur disponible</p>
                    <p className="text-sm text-earth-500">Le premier livreur éligible acceptera votre livraison.</p>
                  </div>
                </button>
                <button
                  onClick={() => setLivreurChoice("specific")}
                  className={`w-full p-4 rounded-xl border-2 text-left transition-all flex items-start gap-3 ${livreurChoice === "specific" ? "border-primary-600 bg-primary-50" : "border-earth-200 hover:border-earth-300"}`}
                >
                  <UserCheck className="w-5 h-5 text-primary-600 mt-0.5" />
                  <div className="flex-1">
                    <p className="font-medium text-earth-900">Choisir mon livreur</p>
                    <p className="text-sm text-earth-500">Sélectionnez un livreur compatible ({requiredTransport ? transportLabel(requiredTransport) : "tous types"}).</p>
                  </div>
                </button>

                {livreurChoice === "specific" && (
                  <div className="pt-2">
                    {loadingLivreurs ? (
                      <div className="flex items-center gap-2 text-sm text-earth-500 py-2">
                        <Loader2 className="w-4 h-4 animate-spin" /> Chargement des livreurs...
                      </div>
                    ) : !availableLivreurs || availableLivreurs.length === 0 ? (
                      <p className="text-sm text-earth-500 py-2">Aucun livreur disponible pour ce type de transport. Laissez le choix ouvert.</p>
                    ) : (
                      <div className="space-y-2 max-h-64 overflow-y-auto">
                        {availableLivreurs.map((livreur) => (
                          <label
                            key={livreur.id}
                            className={`flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all ${selectedLivreurId === livreur.id ? "border-primary-600 bg-primary-50" : "border-earth-200 hover:border-earth-300"}`}
                          >
                            <input
                              type="radio"
                              name="livreur"
                              value={livreur.id}
                              checked={selectedLivreurId === livreur.id}
                              onChange={() => setSelectedLivreurId(livreur.id)}
                              className="accent-primary-600"
                            />
                            <div className="flex-1">
                              <p className="font-medium text-earth-900">{livreur.prenom} {livreur.nom}</p>
                              <p className="text-sm text-earth-500">{transportLabel(livreur.type_transport)} · {livreur.telephone_service || livreur.telephone}</p>
                            </div>
                          </label>
                        ))}
                      </div>
                    )}
                  </div>
                )}
              </div>
          </div>
          <div className="card">
            <h3 className="font-semibold text-earth-900 mb-4">Méthode de paiement</h3>
            <div className="space-y-3">
              <button
                type="button"
                onClick={() => setPaymentMethod("mobile_money")}
                className={`w-full p-4 rounded-xl border-2 text-left transition-all ${paymentMethod === "mobile_money" ? "border-primary-600 bg-primary-50" : "border-earth-200 hover:border-earth-300"}`}
              >
                <div className="flex items-center gap-3"><Smartphone className="w-5 h-5 text-primary-600" /><div><p className="font-medium text-earth-900">Mobile Money (Orange / MTN MoMo)</p><p className="text-sm text-earth-500">Paiement par téléphone</p></div></div>
              </button>
              <button
                type="button"
                onClick={() => setPaymentMethod("solde")}
                disabled={!soldeSuffisant || estimationEnCours}
                className={`w-full p-4 rounded-xl border-2 text-left transition-all ${paymentMethod === "solde" ? "border-primary-600 bg-primary-50" : "border-earth-200 hover:border-earth-300"} ${(!soldeSuffisant || estimationEnCours) ? "opacity-60 cursor-not-allowed" : ""}`}
              >
                <div className="flex items-center gap-3"><CheckCircle2 className="w-5 h-5 text-primary-600" /><div><p className="font-medium text-earth-900">Payer avec mon solde</p><p className="text-sm text-earth-500">Débit immédiat depuis votre portefeuille</p></div></div>
              </button>
            </div>
            {loadingSolde ? (
              <p className="text-sm text-earth-500 mt-3">Chargement du solde...</p>
            ) : (
              <>
                {paymentMethod === "solde" && !soldeSuffisant && (
                  <p className="mt-3 text-sm text-red-600">Solde insuffisant — {formatPrice(soldeDisponible)} disponible, {formatPrice(montantTotal)} requis.</p>
                )}
                {paymentMethod === "solde" && soldeSuffisant && (
                  <p className="mt-3 text-sm text-green-600">Solde disponible : {formatPrice(soldeDisponible)}.</p>
                )}
              </>
            )}
            {paymentMethod === "mobile_money" && (
              <div className="mt-4">
                <label className="block text-sm font-medium text-earth-700 mb-1">Numéro Mobile Money</label>
                <input
                  type="tel"
                  inputMode="numeric"
                  placeholder="6XX XXX XXX"
                  value={numero}
                  onChange={(e) => setNumero(e.target.value)}
                  className="w-full px-4 py-3 rounded-xl border border-earth-200 focus:border-primary-500 focus:outline-none"
                />
                <p className="text-xs text-earth-400 mt-2">Vous recevrez une demande de confirmation sur ce numéro.</p>
              </div>
            )}
          </div>
        </div>
        <div className="lg:col-span-1">
          <div className="card sticky top-24">
            <h3 className="font-semibold text-earth-900 mb-4">Récapitulatif</h3>
            <div className="space-y-3 text-sm">
              <div className="flex justify-between text-earth-500"><span>Sous-total</span><span>{formatPrice(totalPrice)}</span></div>
              <div className="flex justify-between text-earth-500"><span>Commission</span><span>{formatPrice(commission)}</span></div>
              <div className="flex justify-between text-earth-500"><span>Livraison</span>{estimationEnCours ? <span className="flex items-center gap-1"><Loader2 className="w-3.5 h-3.5 animate-spin" /> Calcul...</span> : <span className={livraisonImpossible ? "text-red-600" : ""}>{livraisonImpossible ? "Impossible" : formatPrice(fraisLivraison)}</span>}</div>
              <div className="border-t border-earth-200 pt-3 flex justify-between text-lg font-bold"><span>Total</span><span className="text-primary-600">{formatPrice(montantTotal)}</span></div>
              {livraisonImpossible && (
                <p className="text-xs text-red-600 mt-1">
                  {estimationErreur
                    ? "Impossible de calculer les frais de livraison (position manquante ou service indisponible)."
                    : "La distance de livraison dépasse le plafond autorisé. Cette commande ne peut pas être livrée."}
                </p>
              )}
            </div>
            <button onClick={handleSubmit} disabled={loading || items.length === 0 || estimationEnCours || livraisonImpossible} className="w-full btn-primary mt-6 flex items-center justify-center gap-2">
              {loading
                ? <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                : estimationEnCours
                  ? "Calcul des frais..."
                  : livraisonImpossible
                    ? "Livraison impossible"
                    : "Confirmer et payer"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
