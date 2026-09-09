import React from "react";
import { Link } from "react-router-dom";
import { Minus, Plus, Trash2, ShoppingBag, Leaf, ArrowLeft } from "lucide-react";
import { useCartContext } from "@/contexts/CartContext";
import { formatPrice } from "@/utils/helpers";

export const PanierPage: React.FC = () => {
  const { items, removeItem, updateQuantity, totalPrice, totalItems } = useCartContext();

  if (items.length === 0) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-16 text-center">
        <ShoppingBag className="w-16 h-16 text-earth-300 mx-auto mb-4" />
        <h1 className="text-xl font-semibold text-earth-900 mb-2">Votre panier est vide</h1>
        <p className="text-earth-500 mb-6">Parcourez le catalogue pour ajouter des produits.</p>
        <Link to="/catalogue" className="btn-primary inline-flex items-center gap-2">
          <ArrowLeft className="w-4 h-4" /> Voir le catalogue
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-earth-900 mb-6 flex items-center gap-2">
        <ShoppingBag className="w-6 h-6 text-primary-600" />
        Mon panier ({totalItems})
      </h1>

      <div className="space-y-4 mb-8">
        {items.map((item) => (
          <div key={item.lot.id} className="card flex gap-4">
            {item.lot.produit?.photo ? (
              <img src={item.lot.produit.photo} alt={item.lot.produit.nom} className="w-24 h-24 rounded-lg object-cover flex-shrink-0" />
            ) : (
              <div className="w-24 h-24 bg-earth-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <Leaf className="w-8 h-8 text-earth-300" />
              </div>
            )}
            <div className="flex-1 min-w-0 flex flex-col justify-between">
              <div>
                <h4 className="font-semibold text-earth-900">{item.lot.produit?.nom || "Produit"}</h4>
                <p className="text-sm font-semibold text-primary-600 mt-1">{formatPrice(item.lot.prix_producteur)} / unité</p>
              </div>
              <div className="flex items-center gap-3 mt-2">
                <button onClick={() => updateQuantity(item.lot.id, Math.max(1, item.quantite - 1))} className="p-1.5 hover:bg-earth-100 rounded-lg border border-earth-200"><Minus className="w-3.5 h-3.5" /></button>
                <span className="w-8 text-center text-sm font-medium">{item.quantite}</span>
                <button onClick={() => updateQuantity(item.lot.id, Math.min(item.lot.quantite_disponible, item.quantite + 1))} className="p-1.5 hover:bg-earth-100 rounded-lg border border-earth-200"><Plus className="w-3.5 h-3.5" /></button>
                <button onClick={() => removeItem(item.lot.id)} className="ml-auto flex items-center gap-1 text-sm text-red-500 hover:bg-red-50 px-2 py-1 rounded-lg">
                  <Trash2 className="w-4 h-4" /> Retirer
                </button>
              </div>
            </div>
            <div className="text-right font-semibold text-earth-900 whitespace-nowrap">
              {formatPrice(item.quantite * item.lot.prix_producteur)}
            </div>
          </div>
        ))}
      </div>

      <div className="card flex items-center justify-between">
        <div>
          <p className="text-sm text-earth-500">Total ({totalItems} article{totalItems > 1 ? "s" : ""})</p>
          <p className="text-2xl font-bold text-primary-600">{formatPrice(totalPrice)}</p>
        </div>
        <Link to="/commander" className="btn-primary">Passer la commande</Link>
      </div>
    </div>
  );
};
