import React from 'react';
import { Link } from 'react-router-dom';
import { Leaf, Plus, Minus } from 'lucide-react';
import { Lot } from '@/types';
import { formatPrice } from '@/utils/helpers';
import { FreshnessBadge } from './FreshnessBadge';
import { useCartContext } from '@/contexts/CartContext';

interface ProductCardProps {
  lot: Lot;
}

export const ProductCard: React.FC<ProductCardProps> = ({ lot }) => {
  const { items, addItem, updateQuantity, removeItem } = useCartContext();
  const cartItem = items.find((item) => item.lot.id === lot.id);
  const quantityInCart = cartItem ? cartItem.quantite : 0;
  const availableQty = lot.quantite_disponible - lot.quantite_reservee;

  const handleAdd = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (availableQty > 0) {
      addItem(lot, 1);
    }
  };

  const handleIncrement = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (quantityInCart < availableQty) {
      updateQuantity(lot.id, quantityInCart + 1);
    }
  };

  const handleDecrement = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (quantityInCart > 1) {
      updateQuantity(lot.id, quantityInCart - 1);
    } else {
      removeItem(lot.id);
    }
  };

  return (
    <div className="group bg-white rounded-3xl p-4 border border-gray-100/80 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between relative hover:-translate-y-1">
      {/* Upper image container */}
      <Link to={`/lot/${lot.id}`} className="block relative">
        <div className="relative h-44 w-full bg-[#F4F7F4] rounded-2xl overflow-hidden flex items-center justify-center p-3 mb-3 group-hover:bg-[#EBF3EB] transition-colors">
          {/* Freshness Badge Top-Left */}
          <div className="absolute top-2.5 left-2.5 z-10 scale-90 origin-top-left">
            <FreshnessBadge index={lot.indice_fraicheur} size="sm" />
          </div>

          {/* Availability Badge Top-Right */}
          <div className="absolute top-2.5 right-2.5 z-10 bg-white/90 backdrop-blur-md px-2 py-0.5 rounded-full text-[11px] font-bold text-earth-700 shadow-xs border border-white">
            {availableQty} {lot.produit.unite}
          </div>

          {lot.produit.photo ? (
            <img
              src={lot.produit.photo}
              alt={lot.produit.nom}
              className="h-full w-full object-contain group-hover:scale-110 transition-transform duration-500"
            />
          ) : (
            <div className="w-16 h-16 bg-[#9FE870]/30 rounded-full flex items-center justify-center text-[#07362A]">
              <Leaf className="w-8 h-8" />
            </div>
          )}
        </div>

        {/* Product Details */}
        <div className="mb-3 text-center sm:text-left">
          <h3 className="font-extrabold text-earth-900 text-base leading-snug group-hover:text-[#0A4D3C] transition-colors line-clamp-1">
            {lot.produit.nom}
          </h3>
          {lot.variete && (
            <p className="text-xs font-semibold text-primary-700 mt-0.5 line-clamp-1">
              Variete {lot.variete.nom}
            </p>
          )}
          <p className="text-xs text-earth-500 font-medium mt-0.5">
            ({lot.producteur.localisation || 'Producteur local'}) • {lot.produit.unite || 'Unité'}
          </p>

          <div className="mt-2 flex items-baseline justify-between">
            <div className="flex items-baseline gap-1">
              <span className="text-xl font-black text-[#0A4D3C] tracking-tight">
                {formatPrice(lot.prix_producteur)}
              </span>
            </div>
            <span className="text-[10px] uppercase font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
              Direct Ferme
            </span>
          </div>
        </div>
      </Link>

      {/* Interactive Cart Action Button (Exact layout from reference image) */}
      <div className="mt-1 pt-2 border-t border-gray-50">
        {quantityInCart === 0 ? (
          <button
            onClick={handleAdd}
            disabled={availableQty <= 0}
            className="w-full py-2 bg-[#F0F7EB] hover:bg-[#9FE870] text-[#07362A] rounded-full flex items-center justify-center gap-1.5 text-xs font-bold transition-all duration-200 group-hover:bg-[#9FE870] shadow-xs active:scale-95 disabled:opacity-50"
          >
            <Plus className="w-4 h-4 stroke-[3]" />
            <span>Ajouter au panier</span>
          </button>
        ) : (
          <div className="w-full py-1.5 bg-[#9FE870] rounded-full flex items-center justify-between px-3 text-[#07362A] font-extrabold text-xs shadow-sm">
            <button
              onClick={handleDecrement}
              className="w-6 h-6 rounded-full bg-white/60 hover:bg-white flex items-center justify-center text-[#07362A] transition-colors"
            >
              <Minus className="w-3.5 h-3.5 stroke-[3]" />
            </button>
            <span className="text-sm font-black px-2">{quantityInCart}</span>
            <button
              onClick={handleIncrement}
              className="w-6 h-6 rounded-full bg-white/60 hover:bg-white flex items-center justify-center text-[#07362A] transition-colors"
            >
              <Plus className="w-3.5 h-3.5 stroke-[3]" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
