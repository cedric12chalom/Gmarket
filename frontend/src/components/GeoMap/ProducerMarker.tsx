import React from 'react';
import { Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import { ProductionLot, ProductionLocation } from '@/services/geoApi';
import { getProductEmoji } from '@/constants/emojis';

interface ProducerMarkerProps {
  producteur: ProductionLocation;
  lots: ProductionLot[];
}

const createEmojiIcon = (emoji: string, size = 32): L.DivIcon =>
  L.divIcon({
    className: 'emoji-marker',
    html: `<div style="font-size:${size}px;line-height:${size + 4}px;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3))">${emoji}</div>`,
    iconSize: [size + 8, size + 8],
    iconAnchor: [(size + 8) / 2, size + 8],
    popupAnchor: [0, -(size + 12)],
  });

const freshnessColor = (indice: string): string => {
  if (indice === 'tres_frais' || indice === 'très_frais' || indice === 'ultra_frais') return 'text-green-600';
  if (indice === 'frais') return 'text-yellow-600';
  return 'text-red-600';
};

const freshnessEmoji = (indice: string): string => {
  if (indice === 'tres_frais' || indice === 'très_frais' || indice === 'ultra_frais') return '🟢';
  if (indice === 'frais') return '🟡';
  return '🔴';
};

export const ProducerMarker: React.FC<ProducerMarkerProps> = ({ producteur, lots }) => {
  const producteurLots = lots.filter((l) => l.producteur_id === producteur.id);

  return (
    <Marker
      position={[producteur.latitude, producteur.longitude]}
      icon={createEmojiIcon(producteur.emoji, 36)}
    >
      <Popup>
        <div className="text-sm min-w-[200px]">
          <p className="font-semibold text-earth-900 mb-1">
            {producteur.emoji} {producteur.prenom} {producteur.nom}
          </p>
          {producteur.localisation && (
            <p className="text-xs text-earth-500 mb-2">{producteur.localisation}</p>
          )}
          {producteurLots.length > 0 && (
            <div className="border-t border-earth-100 pt-2 mt-1">
              {producteurLots.map((lot) => {
                const emoji = getProductEmoji(lot.produit.nom ?? '');
                return (
                  <div key={lot.id} className="mb-2 pb-2 border-b border-earth-50 last:border-0">
                    <p className="font-medium">
                      {emoji} {lot.produit.nom}
                    </p>
                    <div className="flex justify-between text-xs text-earth-600 mt-1">
                      <span>{lot.prix_producteur.toLocaleString()} FCFA / {lot.produit.unite}</span>
                      <span>{lot.quantite_disponible} {lot.produit.unite}</span>
                    </div>
                    {lot.date_recolte && (
                      <p className="text-xs text-earth-400">Récolte: {lot.date_recolte}</p>
                    )}
                    <p className={`text-xs font-medium ${freshnessColor(lot.indice_fraicheur)}`}>
                      {freshnessEmoji(lot.indice_fraicheur)} {lot.indice_fraicheur}
                    </p>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </Popup>
    </Marker>
  );
};