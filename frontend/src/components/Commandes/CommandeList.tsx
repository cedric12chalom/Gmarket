import React from "react";
import { Link } from "react-router-dom";
import { Package, ChevronRight } from "lucide-react";
import { Commande } from "@/types";
import { formatPrice, formatDate, orderStatusLabel, orderStatusColor } from "@/utils/helpers";

interface CommandeListProps {
  commandes: Commande[];
  title?: string;
}

export const CommandeList: React.FC<CommandeListProps> = ({ commandes, title = "Mes commandes" }) => {
  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-earth-900 mb-6">{title}</h1>
      {commandes.length === 0 ? (
        <div className="card text-center py-16">
          <Package className="w-16 h-16 text-earth-300 mx-auto mb-4" />
          <p className="text-earth-500">Aucune commande trouvée</p>
        </div>
      ) : (
        <div className="space-y-4">
          {commandes.map((commande) => (
            <Link key={commande.id} to={`/commandes/${commande.id}`} className="block card hover:shadow-md transition-shadow">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
                    <Package className="w-6 h-6 text-primary-600" />
                  </div>
                  <div>
                    <p className="font-semibold text-earth-900">Commande #{commande.id.toString().padStart(4, "0")}</p>
                    <p className="text-sm text-earth-500">{formatDate(commande.date_commande)}</p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <span className={orderStatusColor(commande.statut)}>{orderStatusLabel(commande.statut)}</span>
                  <span className="font-semibold text-earth-900">{formatPrice(commande.montant_total)}</span>
                  <ChevronRight className="w-5 h-5 text-earth-400" />
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
};
