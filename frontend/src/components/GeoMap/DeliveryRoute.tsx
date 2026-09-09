import React from 'react';
import { Polyline, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';

interface DeliveryRouteProps {
  geometry: GeoJSON.Geometry | null;
  pickupLat: number;
  pickupLng: number;
  deliveryLat: number;
  deliveryLng: number;
  driverLat?: number;
  driverLng?: number;
  driverName?: string;
  estimatedDistance?: number;
  estimatedDuration?: number;
  products?: string[];
  address?: string;
  deliveryId: number;
  onAssign?: (id: number) => void;
}

const createPointIcon = (emoji: string): L.DivIcon =>
  L.divIcon({
    className: 'route-point',
    html: `<div style="font-size:28px;line-height:32px;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3))">${emoji}</div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 28],
    popupAnchor: [0, -30],
  });

const createDriverIcon = (): L.DivIcon =>
  L.divIcon({
    className: 'route-point',
    html: `<div style="font-size:32px;line-height:36px;animation:pulse 2s infinite;">🚚</div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 30],
    popupAnchor: [0, -34],
  });

const formatDistance = (meters?: number): string => {
  if (!meters) return '';
  if (meters >= 1000) return `${(meters / 1000).toFixed(1)} km`;
  return `${Math.round(meters)} m`;
};

const formatDuration = (seconds?: number): string => {
  if (!seconds) return '';
  const min = Math.round(seconds / 60);
  if (min >= 60) return `${Math.floor(min / 60)}h ${min % 60}min`;
  return `${min} min`;
};

export const DeliveryRoute: React.FC<DeliveryRouteProps> = ({
  geometry,
  pickupLat,
  pickupLng,
  deliveryLat,
  deliveryLng,
  driverLat,
  driverLng,
  driverName,
  estimatedDistance,
  estimatedDuration,
  products,
  address,
  deliveryId,
  onAssign,
}) => {
  const coordinates: [number, number][] = [];
  if (geometry?.type === 'LineString') {
    const coords = (geometry as GeoJSON.LineString).coordinates;
    coords.forEach((c) => coordinates.push([c[1], c[0]]));
  }

  return (
    <>
      {coordinates.length > 0 && (
        <Polyline positions={coordinates} pathOptions={{ color: '#3B82F6', weight: 4, opacity: 0.8 }} />
      )}

      <Marker position={[pickupLat, pickupLng]} icon={createPointIcon('📦')}>
        <Popup>
          <div className="text-sm">
            <p className="font-semibold text-green-700">📦 Point de retrait</p>
            {products && <p className="text-xs text-earth-600 mt-1">{products.join(', ')}</p>}
          </div>
        </Popup>
      </Marker>

      <Marker position={[deliveryLat, deliveryLng]} icon={createPointIcon('🏠')}>
        <Popup>
          <div className="text-sm min-w-[180px]">
            <p className="font-semibold text-red-600">🏠 Livraison</p>
            {address && <p className="text-xs text-earth-500 mt-1">{address}</p>}
            {estimatedDistance && (
              <p className="text-xs text-earth-600 mt-1">
                Distance: {formatDistance(estimatedDistance)} - Durée: {formatDuration(estimatedDuration)}
              </p>
            )}
            {onAssign && (
              <button
                onClick={() => onAssign(deliveryId)}
                className="mt-2 w-full text-xs bg-primary-600 text-white py-1.5 rounded-md hover:bg-primary-700"
              >
                Gérer l&apos;attribution
              </button>
            )}
          </div>
        </Popup>
      </Marker>

      {driverLat !== undefined && driverLng !== undefined && (
        <Marker position={[driverLat, driverLng]} icon={createDriverIcon()}>
          <Popup>
            <div className="text-sm">
              <p className="font-semibold">🚚 Livreur en transit{driverName ? ` (${driverName})` : ''}</p>
              {estimatedDistance && <p className="text-xs text-earth-500">Restant: {formatDistance(estimatedDistance)}</p>}
            </div>
          </Popup>
        </Marker>
      )}
    </>
  );
};