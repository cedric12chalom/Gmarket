import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from 'react-query';
import { Leaf, Calendar, MapPin, Thermometer, Minus, Plus, ShoppingCart, QrCode } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { lotApi } from '@/services/api';
import { useCartContext } from '@/contexts/CartContext';
import { formatPrice, formatDate } from '@/utils/helpers';
import { FreshnessBadge } from './FreshnessBadge';
import { toast } from 'react-toastify';

export const ProductDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { addItem } = useCartContext();
  const [quantite, setQuantite] = useState(1);
  const [showQR, setShowQR] = useState(false);

  const { data: lot, isLoading } = useQuery(['lot', id], () => lotApi.getById(Number(id)).then(r => r.data), {
    enabled: !!id,
  });

  if (isLoading) return <div className="flex justify-center py-20"><div className="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin" /></div>;
  if (!lot) return <div className="text-center py-20 text-earth-500">Produit non trouvé</div>;

  const disponible = lot.quantite_disponible - lot.quantite_reservee;
  const commission = lot.produit.categorie.commission_fixe + (lot.prix_producteur * lot.produit.categorie.commission_pourcent / 100);
  const prixTotal = lot.prix_producteur + commission;

  const handleAddToCart = () => {
    if (quantite > disponible) {
      toast.error('Quantité demandée supérieure au stock disponible');
      return;
    }
    addItem(lot, quantite);
    toast.success(`${quantite} ${lot.produit.unite} ajouté(s) au panier`);
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Image */}
        <div className="relative rounded-2xl overflow-hidden bg-earth-100 h-96 lg:h-auto">
          {lot.produit.photo ? (
            <img src={lot.produit.photo} alt={lot.produit.nom} className="w-full h-full object-cover" />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <Leaf className="w-32 h-32 text-earth-300" />
            </div>
          )}
          <div className="absolute top-4 left-4">
            <FreshnessBadge index={lot.indice_fraicheur} />
          </div>
        </div>

        {/* Info */}
        <div className="space-y-6">
          <div>
            <span className="text-sm font-medium text-primary-600">{lot.produit.categorie.nom}</span>
            <h1 className="text-3xl font-bold text-earth-900 mt-1">{lot.produit.nom}</h1>
            {lot.variete && (
              <p className="text-sm font-semibold text-primary-700 mt-1">Variete {lot.variete.nom}</p>
            )}
            <p className="text-earth-500 mt-2">{lot.produit.description}</p>
          </div>

          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
              <span className="text-sm font-bold text-primary-700">{lot.producteur.prenom[0]}{lot.producteur.nom[0]}</span>
            </div>
            <div>
              <p className="font-medium text-earth-900">{lot.producteur.prenom} {lot.producteur.nom}</p>
              <p className="text-sm text-earth-500 flex items-center gap-1">
                <MapPin className="w-3.5 h-3.5" /> {lot.producteur.localisation}
              </p>
            </div>
          </div>

          <div className="card bg-earth-50 border-earth-200">
            <div className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-earth-500">Prix producteur</span>
                <span className="font-display font-semibold text-primary-700">{formatPrice(lot.prix_producteur)} / {lot.produit.unite}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-earth-500">Commission plateforme</span>
                <span className="font-medium">{formatPrice(commission)}</span>
              </div>
              <div className="border-t border-earth-200 pt-3 flex justify-between">
                <span className="font-semibold text-earth-900">Prix final</span>
                <span className="text-xl font-bold text-primary-600">{formatPrice(prixTotal)} / {lot.produit.unite}</span>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4 text-sm">
            <div className="flex items-center gap-2 text-earth-600">
              <Calendar className="w-4 h-4 text-primary-500" />
              Récolte: {formatDate(lot.date_recolte)}
            </div>
            <div className="flex items-center gap-2 text-earth-600">
              <Thermometer className="w-4 h-4 text-primary-500" />
              Conservation: {lot.duree_conservation} jours
            </div>
            <div className="flex items-center gap-2 text-earth-600">
              <Leaf className="w-4 h-4 text-primary-500" />
              Stock: {disponible} {lot.produit.unite}
            </div>
            <div className="flex items-center gap-2 text-earth-600">
              <MapPin className="w-4 h-4 text-primary-500" />
              Transport: {lot.produit.categorie.type_transport}
            </div>
          </div>

          {/* Quantité */}
          <div className="flex items-center gap-4">
            <span className="font-medium text-earth-700">Quantité:</span>
            <div className="flex items-center border border-earth-300 rounded-lg">
              <button
                onClick={() => setQuantite(Math.max(1, quantite - 1))}
                className="p-2 hover:bg-earth-100 rounded-l-lg"
              >
                <Minus className="w-4 h-4" />
              </button>
              <span className="w-12 text-center font-medium">{quantite}</span>
              <button
                onClick={() => setQuantite(Math.min(disponible, quantite + 1))}
                className="p-2 hover:bg-earth-100 rounded-r-lg"
              >
                <Plus className="w-4 h-4" />
              </button>
            </div>
            <span className="text-sm text-earth-500">{lot.produit.unite}</span>
          </div>

          <div className="flex gap-3">
            <button onClick={handleAddToCart} className="flex-1 btn-primary flex items-center justify-center gap-2">
              <ShoppingCart className="w-5 h-5" />
              Ajouter au panier
            </button>
            <button onClick={() => setShowQR(!showQR)} className="btn-outline p-2.5">
              <QrCode className="w-5 h-5" />
            </button>
          </div>

          {showQR && (
            <div className="card flex flex-col items-center">
              <p className="text-sm font-medium text-earth-700 mb-3">QR Code du lot</p>
              <QRCodeSVG value={`${window.location.origin}/scanner?q=${lot.qr_code}`} size={180} />
              <p className="text-xs text-earth-400 mt-2">{lot.qr_code}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
