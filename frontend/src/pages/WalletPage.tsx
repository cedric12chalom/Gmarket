import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "react-query";
import { walletApi } from "@/services/api";
import { Wallet, Plus, ArrowUpRight, ArrowDownLeft, Loader2, Smartphone, RefreshCw } from "lucide-react";
import { toast } from "react-toastify";

type Transaction = {
  id: number;
  type: string;
  montant: number;
  motif: string;
  reference: string | null;
  solde_apres: number;
  date_creation: string;
};

const TYPE_ICON: Record<string, React.FC<{ className?: string }>> = {
  debit: ArrowUpRight,
  credit: ArrowDownLeft,
  recharge: ArrowDownLeft,
};

const TYPE_COLOR: Record<string, string> = {
  debit: "text-red-600 bg-red-50",
  credit: "text-green-600 bg-green-50",
  recharge: "text-green-600 bg-green-50",
};

export const WalletPage: React.FC = () => {
  const queryClient = useQueryClient();
  const [showRecharge, setShowRecharge] = useState(false);
  const [montant, setMontant] = useState("");
  const [numero, setNumero] = useState("");
  const [pendingRef, setPendingRef] = useState<string | null>(null);

  const { data, isLoading } = useQuery(["wallet"], () =>
    walletApi.getSolde().then((r) => r.data),
    { refetchInterval: pendingRef ? 5000 : false }
  );

  const rechargeMutation = useMutation(
    () => walletApi.recharger(parseFloat(montant), numero),
    {
      onSuccess: (res) => {
        const data = res.data;
        if (data.reference) {
          setPendingRef(data.reference);
          toast.info("Paiement en attente de confirmation Mobile Money");
        } else if (data.solde !== undefined) {
          queryClient.invalidateQueries(["wallet"]);
          setShowRecharge(false);
          setMontant("");
          setNumero("");
          toast.success("Compte rechargé avec succès");
        }
      },
    }
  );

  const verifyMutation = useMutation(
    () => walletApi.verifierRecharge(pendingRef!),
    {
      onSuccess: (res) => {
        const data = res.data;
        if (data.statut === "succes" || data.statut === "deja_credite") {
          queryClient.invalidateQueries(["wallet"]);
          setPendingRef(null);
          setShowRecharge(false);
          setMontant("");
          setNumero("");
          toast.success("Compte rechargé avec succès");
        } else if (data.statut === "echec") {
          setPendingRef(null);
          toast.error("La recharge a échoué");
        }
      },
    }
  );

  const transactions: Transaction[] = data?.transactions ?? [];
  const solde = data?.solde ?? 0;

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-earth-900 flex items-center gap-2">
          <Wallet className="w-6 h-6 text-primary-600" /> Portefeuille
        </h1>
        <button
          onClick={() => setShowRecharge(!showRecharge)}
          className="btn-primary flex items-center gap-2"
        >
          <Plus className="w-4 h-4" /> Recharger
        </button>
      </div>

      {/* Solde card */}
      <div className="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl p-6 text-white mb-6">
        <p className="text-primary-100 text-sm font-medium">Solde disponible</p>
        <p className="text-4xl font-bold mt-2">{solde.toLocaleString()} FCFA</p>
        <div className="flex gap-2 mt-4">
          <span className="bg-white/20 text-white text-xs px-3 py-1 rounded-full">
            {transactions.length} transaction{(transactions.length > 1 || transactions.length === 0) ? "s" : ""}
          </span>
        </div>
      </div>

      {/* Pending verification banner */}
      {pendingRef && (
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center gap-3">
          <Loader2 className="w-5 h-5 text-amber-600 animate-spin" />
          <div className="flex-1">
            <p className="text-sm font-medium text-amber-800">Confirmation en attente</p>
            <p className="text-xs text-amber-600 mt-0.5">Veuillez confirmer le paiement sur votre téléphone</p>
          </div>
          <button
            onClick={() => verifyMutation.mutate()}
            disabled={verifyMutation.isLoading}
            className="btn-outline text-sm flex items-center gap-1.5"
          >
            {verifyMutation.isLoading ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <RefreshCw className="w-4 h-4" />
            )}
            Vérifier
          </button>
        </div>
      )}

      {/* Recharge form */}
      {showRecharge && !pendingRef && (
        <div className="card mb-6">
          <h2 className="font-semibold text-earth-900 mb-4 flex items-center gap-2">
            <Smartphone className="w-5 h-5 text-primary-600" /> Recharge Mobile Money
          </h2>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              rechargeMutation.mutate();
            }}
            className="space-y-4"
          >
            <div>
              <label className="label">Montant (FCFA)</label>
              <input
                type="number"
                className="input-field"
                placeholder="Ex: 5000"
                value={montant}
                onChange={(e) => setMontant(e.target.value)}
                min={100}
                required
              />
            </div>
            <div>
              <label className="label">Numéro Mobile Money</label>
              <input
                type="tel"
                className="input-field"
                placeholder="Ex: 691234567"
                value={numero}
                onChange={(e) => setNumero(e.target.value)}
                required
              />
            </div>
            <button
              type="submit"
              className="btn-primary w-full"
              disabled={rechargeMutation.isLoading}
            >
              {rechargeMutation.isLoading ? (
                <Loader2 className="w-4 h-4 animate-spin inline" />
              ) : null}{" "}
              Payer via Mobile Money
            </button>
          </form>
        </div>
      )}

      {/* Transactions */}
      <h2 className="text-lg font-semibold text-earth-900 mb-4">Historique des transactions</h2>

      {isLoading ? (
        <div className="flex justify-center py-16">
          <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
        </div>
      ) : transactions.length === 0 ? (
        <div className="card text-center py-16">
          <Wallet className="w-12 h-12 text-earth-300 mx-auto mb-4" />
          <p className="text-earth-500">Aucune transaction</p>
        </div>
      ) : (
        <div className="space-y-2">
          {transactions.map((t) => {
            const Icon = TYPE_ICON[t.type] || ArrowUpRight;
            const colorClass = TYPE_COLOR[t.type] || "text-earth-600 bg-earth-50";
            const isCredit = t.type === "credit" || t.type === "recharge";
            return (
              <div key={t.id} className="card flex items-center gap-4">
                <div className={`w-10 h-10 rounded-full flex items-center justify-center ${colorClass}`}>
                  <Icon className="w-5 h-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-earth-900 capitalize">{t.motif.replace(/_/g, " ")}</p>
                  <p className="text-xs text-earth-500 mt-0.5">
                    {new Date(t.date_creation).toLocaleDateString("fr-FR", {
                      day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit",
                    })}
                  </p>
                </div>
                <div className="text-right">
                  <p className={`font-semibold ${isCredit ? "text-green-600" : "text-red-600"}`}>
                    {isCredit ? "+" : "-"}{t.montant.toLocaleString()} F
                  </p>
                  <p className="text-xs text-earth-400">Solde: {t.solde_apres.toLocaleString()} F</p>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};