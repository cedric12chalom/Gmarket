import React, { useEffect, useState } from "react";
import { MapContainer, TileLayer, Marker, Popup, useMap } from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { useQuery } from "react-query";
import { carteApi } from "@/services/api";
import { getProductEmoji, getRoleEmoji, DEFAULT_EMOJI } from "@/constants/emojis";
import { formatPrice, transportLabel } from "@/utils/helpers";
import { Loader2, MapPin, Navigation } from "lucide-react";

const createEmojiIcon = (emoji: string, size: number = 36): L.DivIcon =>
  L.divIcon({
    className: "",
    html: `<div style="font-size:${size}px;text-align:center;line-height:${size + 4}px;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.3))">${emoji}</div>`,
    iconSize: [size + 8, size + 8],
    iconAnchor: [(size + 8) / 2, size + 8],
    popupAnchor: [0, -(size + 12)],
  });

interface MarqueurProducteur {
  id: number;
  nom: string;
  prenom: string;
  localisation: string | null;
  latitude: number;
  longitude: number;
}

interface MarqueurLot {
  id: number;
  produit: { id: number | null; nom: string | null; unite: string | null };
  producteur_id: number | null;
  prix_producteur: number;
  quantite_disponible: number;
  latitude: number | null;
  longitude: number | null;
}

interface MarqueurLivreur {
  id: number;
  nom: string;
  prenom: string;
  type_transport: string | null;
  latitude: number;
  longitude: number;
}

const RecenterButton: React.FC<{ onRecenter: () => void }> = ({ onRecenter }) => (
  <div className="leaflet-top leaflet-right" style={{ marginTop: "80px" }}>
    <div className="leaflet-bar leaflet-control">
      <a
        href="#"
        className="leaflet-control-zoom"
        onClick={(e) => { e.preventDefault(); onRecenter(); }}
        title="Centrer sur ma position"
        style={{ fontSize: "18px", lineHeight: "30px", cursor: "pointer" }}
      >
        <Navigation className="w-4 h-4 inline" />
      </a>
    </div>
  </div>
);

const LocationMarker: React.FC<{ position: [number, number] }> = ({ position }) => {
  const map = useMap();
  useEffect(() => {
    map.flyTo(position, map.getZoom() < 14 ? 14 : map.getZoom());
  }, [position]);
  return null;
};

export const CartePage: React.FC = () => {
  const [userPosition, setUserPosition] = useState<[number, number] | null>(null);
  const [positionLoading, setPositionLoading] = useState(true);

  useEffect(() => {
    if (!navigator.geolocation) {
      setPositionLoading(false);
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setUserPosition([pos.coords.latitude, pos.coords.longitude]);
        setPositionLoading(false);
      },
      () => {
        setUserPosition([3.848, 11.502]);
        setPositionLoading(false);
      },
      { timeout: 10000, enableHighAccuracy: false }
    );
  }, []);

  const { data, isLoading } = useQuery(
    ["carte-marqueurs"],
    () => carteApi.getMarqueurs().then((r) => r.data),
    { enabled: !positionLoading }
  );

  const producteurs: MarqueurProducteur[] = data?.producteurs ?? [];
  const lots: MarqueurLot[] = data?.lots ?? [];
  const livreurs: MarqueurLivreur[] = data?.livreurs ?? [];

  const handleRecenter = () => {
    if (userPosition) {
      setUserPosition([...userPosition]);
    }
  };

  if (positionLoading || isLoading) {
    return (
      <div className="flex justify-center items-center py-40">
        <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
      </div>
    );
  }

  const center = userPosition ?? [3.848, 11.502];

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-earth-900 mb-4 flex items-center gap-2">
        <MapPin className="w-6 h-6 text-primary-600" /> Carte interactive
      </h1>
      <div className="h-[600px] rounded-2xl overflow-hidden border border-earth-200 shadow-sm">
        <MapContainer key="terralink-map" center={center} zoom={userPosition ? 14 : 3} className="h-full w-full" scrollWheelZoom={true}>
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />

          {userPosition && <LocationMarker position={userPosition} />}

          {userPosition && (
            <Marker position={userPosition} icon={createEmojiIcon("📍", 32)} zIndexOffset={1000}>
              <Popup>
                <div className="text-sm">
                  <p className="font-semibold">📍 Ma position</p>
                </div>
              </Popup>
            </Marker>
          )}

          <RecenterButton onRecenter={handleRecenter} />

          {producteurs.map((p) => (
            <Marker
              key={`prod-${p.id}`}
              position={[p.latitude, p.longitude]}
              icon={createEmojiIcon(getRoleEmoji("producteur"))}
            >
              <Popup>
                <div className="text-sm">
                  <p className="font-semibold">
                    {getRoleEmoji("producteur")} {p.prenom} {p.nom}
                  </p>
                  {p.localisation && <p className="text-earth-500 text-xs mt-1">{p.localisation}</p>}
                  <p className="text-xs text-earth-400 mt-1">
                    {lots.filter((l) => l.producteur_id === p.id).length} lot(s) disponible(s)
                  </p>
                </div>
              </Popup>
            </Marker>
          ))}

          {lots.map((l) => {
            if (!l.latitude || !l.longitude) return null;
            const emoji = getProductEmoji(l.produit.nom ?? "");
            return (
              <Marker
                key={`lot-${l.id}`}
                position={[l.latitude, l.longitude]}
                icon={createEmojiIcon(emoji, 28)}
              >
                <Popup>
                  <div className="text-sm">
                    <p className="font-semibold">
                      {emoji} {l.produit.nom}
                    </p>
                    <p className="text-primary-600 font-medium mt-1">{formatPrice(l.prix_producteur)} / {l.produit.unite}</p>
                    <p className="text-xs text-earth-500 mt-1">{l.quantite_disponible} {l.produit.unite} disponible(s)</p>
                  </div>
                </Popup>
              </Marker>
            );
          })}

          {livreurs.map((l) => (
            <Marker
              key={`liv-${l.id}`}
              position={[l.latitude, l.longitude]}
              icon={createEmojiIcon(getRoleEmoji("livreur", l.type_transport))}
            >
              <Popup>
                <div className="text-sm">
                  <p className="font-semibold">
                    {getRoleEmoji("livreur", l.type_transport)} {l.prenom} {l.nom}
                  </p>
                  <p className="text-xs text-earth-500 mt-1">{transportLabel(l.type_transport ?? "")}</p>
                </div>
              </Popup>
            </Marker>
          ))}
        </MapContainer>
      </div>

      <div className="mt-4 flex flex-wrap gap-4 text-sm text-earth-600">
        <span>{getRoleEmoji("producteur")} Producteur</span>
        <span>{getRoleEmoji("livreur")} Livreur (standard)</span>
        <span>{getRoleEmoji("livreur", "rapide")} Livreur (rapide)</span>
        <span>{getRoleEmoji("livreur", "refrigere")} Livreur (réfrigéré)</span>
        <span>{DEFAULT_EMOJI} Produit</span>
        <span>📍 Ma position</span>
      </div>
    </div>
  );
};