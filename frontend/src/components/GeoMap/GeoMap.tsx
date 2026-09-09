import React, { useEffect, useState, useCallback, useRef } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import { MapPin, Navigation, Loader2 } from 'lucide-react';
import { useQuery } from 'react-query';
import { geoApi, ProductionLot, ProductionLocation } from '@/services/geoApi';
import { useRealtimeTracking } from '@/hooks/useRealtimeTracking';
import { useAuthContext } from '@/contexts/AuthContext';
import { ProducerMarker } from './ProducerMarker';
import { DeliveryRoute } from './DeliveryRoute';
import { MatchDelivery } from './MatchDelivery';

const createEmojiIcon = (emoji: string, size = 36): L.DivIcon =>
  L.divIcon({
    className: 'emoji-marker',
    html: `<div style="font-size:${size}px;line-height:${size + 4}px;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3))">${emoji}</div>`,
    iconSize: [size + 8, size + 8],
    iconAnchor: [(size + 8) / 2, size + 8],
    popupAnchor: [0, -(size + 12)],
  });

const pulseIcon = (): L.DivIcon =>
  L.divIcon({
    className: 'emoji-marker',
    html: `<div style="font-size:28px;line-height:32px;animation:pulse 2s infinite;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3))">🚚</div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 30],
    popupAnchor: [0, -32],
  });

const FitBoundsToMarkers: React.FC<{ points: [number, number][]; done: boolean }> = ({ points, done }) => {
  const map = useMap();
  useEffect(() => {
    if (!done || points.length === 0) return;
    const bounds = L.latLngBounds(points.map(p => L.latLng(p[0], p[1])));
    if (bounds.isValid()) {
      map.fitBounds(bounds, { padding: [60, 60], maxZoom: 14 });
    }
  }, [map, points , done]);
  return null;
};

const RecenterButton: React.FC<{ onRecenter: () => void }> = ({ onRecenter }) => (
  <div className="leaflet-top leaflet-right" style={{ marginTop: '80px' }}>
    <div className="leaflet-bar leaflet-control">
      <a
        href="#"
        className="leaflet-control-zoom"
        onClick={(e: React.MouseEvent) => { e.preventDefault(); onRecenter(); }}
        title="Centrer sur ma position"
        style={{ fontSize: '18px', lineHeight: '30px', cursor: 'pointer' }}
      >
        <Navigation className="w-4 h-4 inline" />
      </a>
    </div>
  </div>
);

interface LayerControlProps {
  visible: { producers: boolean; deliveryPersons: boolean; deliveries: boolean; lots: boolean };
  onChange: (key: keyof LayerControlProps['visible']) => void;
}

const LayerControl: React.FC<LayerControlProps> = ({ visible, onChange }) => {
  const layers = [
    { key: 'lots' as const, label: 'Productions (lots)', color: 'bg-green-100 text-green-700' },
    { key: 'producers' as const, label: 'Producteurs', color: 'bg-blue-100 text-blue-700' },
    { key: 'deliveryPersons' as const, label: 'Livreurs', color: 'bg-purple-100 text-purple-700' },
    { key: 'deliveries' as const, label: 'Livraisons', color: 'bg-amber-100 text-amber-700' },
  ];

  return (
    <div className="leaflet-top leaflet-left" style={{ marginLeft: '10px', marginTop: '10px' }}>
      <div className="leaflet-control bg-white rounded-lg shadow-md p-3 min-w-[160px]">
        <p className="text-xs font-semibold text-earth-700 mb-2">Couches</p>
        {layers.map((l) => (
          <label key={l.key} className="flex items-center gap-2 py-1 cursor-pointer">
            <input
              type="checkbox"
              checked={visible[l.key]}
              onChange={() => onChange(l.key)}
              className="rounded border-earth-300 text-primary-600 focus:ring-primary-500"
            />
            <span className={`text-xs px-1.5 py-0.5 rounded ${l.color}`}>{l.label}</span>
          </label>
        ))}
      </div>
    </div>
  );
};

interface DeliveryData {
  id: number;
  livraison: any;
  route_geojson: GeoJSON.Geometry | null;
  route_distance?: number | null;
  route_duration?: number | null;
  route_waypoints?: { latitude: number; longitude: number }[] | null;
}

export const GeoMap: React.FC = () => {
  const { isLivreur } = useAuthContext();
  const [userPosition, setUserPosition] = useState<[number, number] | null>(null);
  const [positionLoading, setPositionLoading] = useState(true);
  const [layerVisibility, setLayerVisibility] = useState({
    producers: true,
    deliveryPersons: true,
    deliveries: true,
    lots: true,
  });
  const [matchDeliveryId, setMatchDeliveryId] = useState<number | null>(null);
  const latestPositionRef = useRef<[number, number] | null>(null);
  useEffect(() => {
    latestPositionRef.current = userPosition;
  }, [userPosition]);

  // Un livreur qui ouvre la carte partage sa position en temps réel (les autres
  // utilisateurs voient son point 🚚 bouger et sa route se redessiner).
  useEffect(() => {
    if (!isLivreur) return;
    const push = () => {
      const pos = latestPositionRef.current;
      if (!pos) return;
      geoApi.trackPosition({ latitude: pos[0], longitude: pos[1], type: 'delivery_person' }).catch(() => {});
    };
    push();
    const interval = setInterval(push, 15000);
    return () => clearInterval(interval);
  }, [isLivreur]);

  const { positions, startTracking, stopTracking } = useRealtimeTracking(10000);

  useEffect(() => {
    if (!navigator.geolocation) {
      setUserPosition([3.848, 11.502]);
      setPositionLoading(false);
      return;
    }

    const fallback = () => {
      setUserPosition([3.848, 11.502]);
      setPositionLoading(false);
    };

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setUserPosition([pos.coords.latitude, pos.coords.longitude]);
        setPositionLoading(false);
      },
      fallback,
      { timeout: 10000, enableHighAccuracy: true }
    );

    const watchId = navigator.geolocation.watchPosition(
      (pos) => {
        setUserPosition([pos.coords.latitude, pos.coords.longitude]);
        if (positionLoading) setPositionLoading(false);
      },
      () => {},
      { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 }
    );

    return () => navigator.geolocation.clearWatch(watchId);
  }, [positionLoading]);

  useEffect(() => {
    startTracking();
    return () => stopTracking();
  }, [startTracking, stopTracking]);

  const { data: productions, isLoading: productionsLoading } = useQuery(
    ['geo-productions'],
    () => geoApi.getProductions().then((r) => r.data),
    { enabled: !positionLoading }
  );

  const { data: deliveries, isLoading: deliveriesLoading } = useQuery(
    ['geo-deliveries'],
    () => geoApi.getDeliveries().then((r) => r.data),
    { enabled: !positionLoading, refetchInterval: 10000 }
  );

  const producteurs: ProductionLocation[] = productions?.producteurs ?? [];
  const lots: ProductionLot[] = productions?.lots ?? [];
  const activeDeliveries: DeliveryData[] = deliveries ?? [];

  const handleRecenter = useCallback(() => {
    if (userPosition) {
      setUserPosition([...userPosition]);
    }
  }, [userPosition]);

  const toggleLayer = useCallback((key: keyof typeof layerVisibility) => {
    setLayerVisibility((prev) => ({ ...prev, [key]: !prev[key] }));
  }, []);

  const handleAssignClick = useCallback((id: number) => {
    setMatchDeliveryId(id);
  }, []);

  // Livreurs déjà affichés comme chauffeur sur leur livraison en cours : on ne
  // les duplique pas dans la couche "positions" globale (un seul point 🚚).
  const driverIds = new Set<number>();
  const deliveriesWithDriver: { delivery: DeliveryData; driver: { lat: number; lng: number } | null }[] = activeDeliveries.map((d) => {
    const livreur = (d.livraison as any)?.livreur;
    const livePos = positions.find((p) => p.reference_id === livreur?.id);
    let driver: { lat: number; lng: number } | null = null;
    if (livePos && livePos.latitude !== null && livePos.longitude !== null) {
      driver = { lat: livePos.latitude, lng: livePos.longitude };
    } else if (d.route_waypoints && d.route_waypoints.length >= 3) {
      driver = { lat: d.route_waypoints[0].latitude, lng: d.route_waypoints[0].longitude };
    } else if (livreur?.latitude != null && livreur?.longitude != null) {
      driver = { lat: livreur.latitude, lng: livreur.longitude };
    }
    if (livreur?.id && driver) {
      driverIds.add(livreur.id);
    }
    return { delivery: d, driver };
  });

  const center = userPosition ?? [3.848, 11.502];

  if (positionLoading || productionsLoading || deliveriesLoading) {
    return (
      <div className="flex justify-center items-center py-40">
        <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-earth-900 mb-4 flex items-center gap-2">
        <MapPin className="w-6 h-6 text-primary-600" /> Carte interactive
      </h1>
      <div className="h-[600px] rounded-2xl overflow-hidden border border-earth-200 shadow-sm relative">
        <MapContainer key="geo-map" center={center} zoom={userPosition ? 14 : 3} className="h-full w-full" scrollWheelZoom={true}>
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />

          {userPosition && (
            <>
              <Marker position={userPosition} icon={createEmojiIcon('📍', 32)}>
                <Popup>
                  <div className="text-sm">
                    <p className="font-semibold">📍 Ma position</p>
                    <p className="text-xs text-earth-500">
                      {userPosition[0].toFixed(5)}, {userPosition[1].toFixed(5)}
                    </p>
                  </div>
                </Popup>
              </Marker>
            </>
          )}

          <RecenterButton onRecenter={handleRecenter} />
          <LayerControl visible={layerVisibility} onChange={toggleLayer} />

          {layerVisibility.producers &&
            producteurs.map((p) => (
              <ProducerMarker key={`prod-${p.id}`} producteur={p} lots={lots} />
            ))}

          {layerVisibility.lots &&
            lots.map((l) => {
              if (!l.latitude || !l.longitude) return null;
              return (
                <Marker
                  key={`lot-${l.id}`}
                  position={[l.latitude, l.longitude]}
                  icon={createEmojiIcon(l.emoji, 28)}
                >
                  <Popup>
                    <div className="text-sm">
                      <p className="font-semibold">
                        {l.emoji} {l.produit.nom}
                      </p>
                      <p className="text-primary-600 font-medium mt-1">
                        {l.prix_producteur.toLocaleString()} FCFA / {l.produit.unite}
                      </p>
                      <p className="text-xs text-earth-500 mt-1">
                        {l.quantite_disponible} {l.produit.unite} disponible(s)
                      </p>
                    </div>
                  </Popup>
                </Marker>
              );
            })}

          {layerVisibility.deliveryPersons &&
            positions.filter((pos) => !driverIds.has(pos.reference_id)).map((pos) => (
              <Marker
                key={`pos-${pos.id}`}
                position={[pos.latitude, pos.longitude]}
                icon={pulseIcon()}
              >
                <Popup>
                  <div className="text-sm">
                    <p className="font-semibold">🚚 Livreur #{pos.reference_id}</p>
                    <p className="text-xs text-earth-500">Mis à jour: {new Date(pos.updated_at).toLocaleTimeString()}</p>
                  </div>
                </Popup>
              </Marker>
            ))}

          {layerVisibility.deliveries &&
            deliveriesWithDriver.map(({ delivery: d, driver }) => {
              const livraison = d.livraison as any;
              const commande = livraison?.commande;
              const pickupLat = commande?.lignes?.[0]?.lot?.latitude ?? commande?.lignes?.[0]?.lot?.producteur?.latitude;
              const pickupLng = commande?.lignes?.[0]?.lot?.longitude ?? commande?.lignes?.[0]?.lot?.producteur?.longitude;
              const deliveryLat = commande?.acheteur?.latitude;
              const deliveryLng = commande?.acheteur?.longitude;
              if (!pickupLat || !pickupLng || !deliveryLat || !deliveryLng) return null;

              return (
                <DeliveryRoute
                  key={`del-${d.id}`}
                  geometry={d.route_geojson}
                  pickupLat={pickupLat}
                  pickupLng={pickupLng}
                  deliveryLat={deliveryLat}
                  deliveryLng={deliveryLng}
                  driverLat={driver?.lat}
                  driverLng={driver?.lng}
                  driverName={livraison?.livreur ? `${livraison.livreur.prenom} ${livraison.livreur.nom}` : undefined}
                  estimatedDistance={d.route_distance ?? undefined}
                  address={livraison?.adresse_livraison}
                  deliveryId={d.id}
                  onAssign={handleAssignClick}
                />
              );
            })}
        </MapContainer>
      </div>

      <FitBoundsToMarkers
        points={[
          ...(userPosition ? [userPosition] : []),
          ...(layerVisibility.producers ? producteurs.map(p => [p.latitude, p.longitude] as [number, number]) : []),
          ...(layerVisibility.lots ? lots.filter(l => l.latitude && l.longitude).map(l => [l.latitude!, l.longitude!] as [number, number]) : []),
          ...(layerVisibility.deliveryPersons ? positions.map(pos => [pos.latitude, pos.longitude] as [number, number]) : []),
          ...(layerVisibility.deliveries
            ? activeDeliveries.flatMap((d): [number, number][] => {
                const commande = (d.livraison as any)?.commande;
                const pts: [number, number][] = [];
                const lotLat = commande?.lignes?.[0]?.lot?.latitude ?? commande?.lignes?.[0]?.lot?.producteur?.latitude;
                const lotLng = commande?.lignes?.[0]?.lot?.longitude ?? commande?.lignes?.[0]?.lot?.producteur?.longitude;
                if (lotLat && lotLng) pts.push([lotLat, lotLng]);
                if (commande?.acheteur?.latitude && commande?.acheteur?.longitude) {
                  pts.push([commande.acheteur.latitude, commande.acheteur.longitude]);
                }
                (d.route_waypoints ?? []).forEach((w) => pts.push([w.latitude, w.longitude]));
                return pts;
              })
            : []),
        ]}
        done={!productionsLoading && !deliveriesLoading}
      />

      {matchDeliveryId !== null && (
        <MatchDelivery
          deliveryId={matchDeliveryId}
          onClose={() => { setMatchDeliveryId(null); }}
          onAssigned={() => {
            setMatchDeliveryId(null);
          }}
        />
      )}

      <div className="mt-4 flex flex-wrap gap-4 text-sm text-earth-600">
        <span>📍 Moi</span>
        <span>🧑‍🌾 Producteur</span>
        <span>🚚 Livreur (temps réel)</span>
        <span>📦 Point de retrait</span>
        <span>🏠 Point de livraison</span>
      </div>
    </div>
  );
};

