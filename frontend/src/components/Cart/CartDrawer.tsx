import React from "react";
import { Link } from "react-router-dom";
import { X, Minus, Plus, Trash2, ShoppingBag, Leaf } from "lucide-react";
import { useCartContext } from "@/contexts/CartContext";
import { formatPrice } from "@/utils/helpers";

interface CartDrawerProps {
  isOpen: boolean;
  onClose: () => void;
}

export const CartDrawer: React.FC<CartDrawerProps> = ({ isOpen, onClose }) => {
  const { items, removeItem, updateQuantity, totalPrice, totalItems } = useCartContext();
  if (!isOpen) return null;
  return (
    <div className="fixed inset-0 z-50">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <div className="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl flex flex-col">
        <div className="flex items-center justify-between p-4 border-b border-earth-200">
          <h2 className="text-lg font-semibold text-earth-900 flex items-center gap-2">
            <ShoppingBag className="w-5 h-5 text-primary-600" />
            Mon panier ({totalItems})
          </h2>
          <button onClick={onClose} className="p-2 hover:bg-earth-100 rounded-lg"><X className="w-5 h-5 text-earth-500" /></button>
        </div>
        <div className="flex-1 overflow-y-auto p-4 space-y-4">
          {items.length === 0 ? (
            <div className="text-center py-12">
              <ShoppingBag className="w-16 h-16 text-earth-300 mx-auto mb-4" />
              <p className="text-earth-500">Votre panier est vide</p>
            </div>
          ) : (
            items.map((item) => (
              <div key={item.lot.id} className="flex gap-4 p-3 bg-earth-50 rounded-xl">
                {item.lot.produit?.photo ? (
                  <img src={item.lot.produit.photo} alt={item.lot.produit.nom} className="w-20 h-20 rounded-lg object-cover flex-shrink-0" />
                ) : (
                  <div className="w-20 h-20 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                    <Leaf className="w-8 h-8 text-earth-300" />
                  </div>
                )}
                <div className="flex-1 min-w-0">
                  <h4 className="font-medium text-earth-900 text-sm truncate">{item.lot.produit?.nom || "Produit"}</h4>
                  <p className="text-sm font-semibold text-primary-600 mt-1">{formatPrice(item.lot.prix_producteur)}</p>
                  <div className="flex items-center gap-2 mt-2">
                    <button onClick={() => updateQuantity(item.lot.id, Math.max(1, item.quantite - 1))} className="p-1 hover:bg-earth-200 rounded"><Minus className="w-3.5 h-3.5" /></button>
                    <span className="w-8 text-center text-sm font-medium">{item.quantite}</span>
                    <button onClick={() => updateQuantity(item.lot.id, Math.min(item.lot.quantite_disponible, item.quantite + 1))} className="p-1 hover:bg-earth-200 rounded"><Plus className="w-3.5 h-3.5" /></button>
                    <button onClick={() => removeItem(item.lot.id)} className="ml-auto p-1.5 text-red-500 hover:bg-red-50 rounded-lg"><Trash2 className="w-4 h-4" /></button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
        {items.length > 0 && (
          <div className="border-t border-earth-200 p-4 space-y-4">
            <div className="flex justify-between text-lg font-semibold"><span>Total</span><span className="text-primary-600">{formatPrice(totalPrice)}</span></div>
            <Link to="/commander" onClick={onClose} className="block w-full btn-primary text-center">Passer la commande</Link>
          </div>
        )}
      </div>
    </div>
  );
};
