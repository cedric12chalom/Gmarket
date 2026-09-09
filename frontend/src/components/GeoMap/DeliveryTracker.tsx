import React, { useEffect, useState, useCallback } from 'react';
import { MapContainer, TileLayer, Marker, Polyline, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import { geoApi, RouteWaypoint, DeliveryRouteInfo } from '@/services/geoApi';
import { useGeoLocation } from '@/hooks/useGeoLocation';
import { Navigation, Loader2 } from 'lucide-react';

interface DeliveryTrackerProps {
  deliveryId: number;
}

const createEmojiIcon = (emoji: string, size = 32): L.DivIcon =>
  L.divIcon({
    className: 'emoji-marker',
    html: `<div style="font-size:${size}px;line-height:${size + 4}px;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3))">${emoji}</div>`,
    iconSize: [size + 8, size + 8],
    iconAnchor: [(size + 8) / 2, size + 8],
    popupAnchor: [0, -(size + 12)],
  });

const FitBounds: React.FC<{ points: [number, number][] }> = ({ points }) => {
  const map = useMap();
  useEffect(() => {
    if (points.length > 0) {
      const bounds = L.latLngBounds(points.map((p) => L.latLng(p[0], p[1])));
      map.fitBounds(bounds, { padding: [50, 50] });
    }
  }, [points, map]);
  return null;
};

const formatDistance = (d: number): string =>
  d >= 1000 ? `${(d / 1000).toFixed(1)} km` : `${Math.round(d)} m`;

const formatDuration = (d: number): string => {
  const mins = Math.round(d / 60);
  if (mins < 60) return `${mins} min`;
  return `${Math.floor(mins / 60)} h ${mins % 60} min`;
};

export const DeliveryTracker: React.FC<DeliveryTrackerProps> = ({ deliveryId }) => {
  const { position, error: geoError, loading: geoLoading } = useGeoLocation(true);
  const [livraison, setLivraison] = useState<any>(null);
  const [route, setRoute] = useState<DeliveryRouteInfo | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDelivery = async () => {
      try {
        const [routeRes, deliveriesRes] = await Promise.all([
          geoApi.getRoute(deliveryId),
          geoApi.getDeliveries(),
        ]);
        setRoute(routeRes.data);
        const delivery = deliveriesRes.data.find((d: any) => d.id === deliveryId);
        if (delivery) {
          setLivraison(delivery.livraison);
        }
      } catch {
        // silence
      } finally {
        setLoading(false);
      }
    };
    fetchDelivery();
  }, [deliveryId]);

  useEffect(() => {
    if (!position) return;
    const interval = setInterval(() => {
      geoApi.trackPosition({
        latitude: position.latitude,
        longitude: position.longitude,
        type: 'delivery_person',
      }).catch(() => {});
    }, 15000);
    return () => clearInterval(interval);
  }, [position]);

  const routeCoords: [number, number][] = [];
  if (route?.geometry?.type === 'LineString') {
    (route.geometry as GeoJSON.LineString).coordinates.forEach((c) =>
      routeCoords.push([c[1], c[0]])
    );
  }

  const waypoints: RouteWaypoint[] = route?.waypoints ?? [];
  const pickup: [number, number] | null =
    waypoints.length >= 2
      ? [waypoints[1].latitude, waypoints[1].longitude]
      : routeCoords.length > 0
        ? routeCoords[0]
        : null;
  const destination: [number, number] | null =
    waypoints.length > 0
      ? [waypoints[waypoints.length - 1].latitude, waypoints[waypoints.length - 1].longitude]
      : routeCoords.length > 0
        ? routeCoords[routeCoords.length - 1]
        : null;

  const center: [number, number] = position
    ? [position.latitude, position.longitude]
    : [3.848, 11.502];

  const calcDistance = useCallback((lat1: number, lng1: number, lat2: number, lng2: number): number => {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon/2) * Math.sin(dLon/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }, []);

  const remainingDist = (() => {
    if (!position || !destination) return null;
    return calcDistance(position.latitude, position.longitude, destination[0], destination[1]);
  })();

  const remainingTime = remainingDist ? Math.round((remainingDist / 1000) / 15 * 60) : null;

  if (loading || geoLoading) {
    return (
      <div className="flex justify-center items-center h-screen">
        <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
      </div>
    );
  }

  return (
    <div className="h-screen w-full flex flex-col">
      <div className="bg-primary-600 text-white px-4 py-3 flex items-center justify-between">
        <div>
          <p className="font-semibold text-sm">🚚 Livraison en cours</p>
          {route && (
            <p className="text-xs text-primary-100">
              Trajet total : {formatDistance(route.distance)} · {formatDuration(route.duration)}
            </p>
          )}
          {remainingDist !== null && (
            <p className="text-xs text-primary-100">
              Reste {formatDistance(remainingDist)}
              {remainingTime !== null && ` · ${remainingTime} min`}
            </p>
          )}
        </div>
        <Navigation className="w-5 h-5" />
      </div>

      <div className="flex-1 z-10">
        <MapContainer center={center} zoom={14} className="h-full w-full" scrollWheelZoom={true}>
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />

          <FitBounds points={routeCoords} />

          {routeCoords.length > 0 && (
            <Polyline positions={routeCoords} pathOptions={{ color: '#3B82F6', weight: 4, opacity: 0.8 }} />
          )}

          {pickup && (
            <Marker position={pickup} icon={createEmojiIcon('📦', 28)}>
              <Popup>📦 Point de retrait (producteur)</Popup>
            </Marker>
          )}

          {destination && (
            <Marker position={destination} icon={createEmojiIcon('🏠', 28)}>
              <Popup>🏠 Destination</Popup>
            </Marker>
          )}

          {position && (
            <Marker position={[position.latitude, position.longitude]} icon={createEmojiIcon('📍', 28)}>
              <Popup>
                <div className="text-sm">
                  <p className="font-semibold">📍 Ma position</p>
                  <p className="text-xs text-earth-500">
                    {position.latitude.toFixed(5)}, {position.longitude.toFixed(5)}
                  </p>
                </div>
              </Popup>
            </Marker>
          )}
        </MapContainer>
      </div>

      {livraison && (
        <div className="bg-white border-t border-earth-200 px-4 py-3">
          <p className="text-sm font-medium text-earth-800">
            📦 {livraison.commande?.lignes?.length || 0} article(s) à livrer
          </p>
          {livraison.adresse_livraison && (
            <p className="text-xs text-earth-500 mt-0.5">📍 {livraison.adresse_livraison}</p>
          )}
        </div>
      )}

      {geoError && (
        <div className="bg-red-50 text-red-700 text-xs px-4 py-2 text-center">
          {geoError}
        </div>
      )}
    </div>
  );
};
