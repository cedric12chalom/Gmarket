import React, { useEffect, useRef, useState } from 'react';
import { MapContainer, TileLayer, Marker, useMap, useMapEvents } from 'react-leaflet';
import L from 'leaflet';
import { Loader2, MapPin, Navigation, X } from 'lucide-react';
import { useGeoLocation } from '@/hooks/useGeoLocation';
import 'leaflet/dist/leaflet.css';

export interface PositionValue {
  latitude: number | null;
  longitude: number | null;
}

interface PositionPickerProps {
  value: PositionValue;
  onChange: (value: PositionValue) => void;
  description: string;
  onDescriptionChange: (description: string) => void;
  descriptionLabel?: string;
  descriptionPlaceholder?: string;
  height?: number;
}

const DEFAULT_CENTER: [number, number] = [3.848, 11.502];

const pinIcon = (): L.DivIcon =>
  L.divIcon({
    className: 'position-picker-pin',
    html: '<div style="font-size:32px;line-height:36px;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3))">📌</div>',
    iconSize: [36, 36],
    iconAnchor: [18, 34],
    popupAnchor: [0, -34],
  });

const MapClickCatcher: React.FC<{ onPick: (lat: number, lng: number) => void }> = ({ onPick }) => {
  useMapEvents({
    click(e) {
      onPick(e.latlng.lat, e.latlng.lng);
    },
  });
  return null;
};

const FlyToPosition: React.FC<{ center: [number, number] | null }> = ({ center }) => {
  const map = useMap();
  useEffect(() => {
    if (center) {
      map.flyTo(center, Math.max(map.getZoom(), 14));
    }
  }, [center, map]);
  return null;
};

export const PositionPicker: React.FC<PositionPickerProps> = ({
  value,
  onChange,
  description,
  onDescriptionChange,
  descriptionLabel = 'Description du lieu',
  descriptionPlaceholder = 'Ex. Marché central, près de l\'école…',
  height = 260,
}) => {
  const { position: geoPosition, error: geoError, loading: geoLoading, getPosition } = useGeoLocation(false);
  const [flyCenter, setFlyCenter] = useState<[number, number] | null>(null);
  const pendingLocateRef = useRef(false);

  const hasPosition = value.latitude != null && value.longitude != null;
  const mapCenter: [number, number] = hasPosition ? [value.latitude!, value.longitude!] : DEFAULT_CENTER;

  useEffect(() => {
    if (!geoPosition || !pendingLocateRef.current) return;
    pendingLocateRef.current = false;
    onChange({ latitude: geoPosition.latitude, longitude: geoPosition.longitude });
    setFlyCenter([geoPosition.latitude, geoPosition.longitude]);
  }, [geoPosition, onChange]);

  const handleLocate = () => {
    pendingLocateRef.current = true;
    getPosition();
  };

  return (
    <div className="space-y-3">
      <div>
        <label className="label">{descriptionLabel}</label>
        <div className="relative">
          <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
          <input
            value={description}
            onChange={(e) => onDescriptionChange(e.target.value)}
            className="input-field pl-10"
            placeholder={descriptionPlaceholder}
          />
        </div>
      </div>

      <div className="relative rounded-xl overflow-hidden border border-earth-200">
        <MapContainer
          key="position-picker-map"
          center={mapCenter}
          zoom={hasPosition ? 14 : 4}
          style={{ height, width: '100%' }}
          scrollWheelZoom={true}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <MapClickCatcher onPick={(lat, lng) => onChange({ latitude: lat, longitude: lng })} />
          {hasPosition && (
            <Marker
              position={[value.latitude!, value.longitude!]}
              icon={pinIcon()}
              draggable
              eventHandlers={{
                dragend: (e) => {
                  const { lat, lng } = (e.target as L.Marker).getLatLng();
                  onChange({ latitude: lat, longitude: lng });
                },
              }}
            />
          )}
          <FlyToPosition center={flyCenter} />
        </MapContainer>

        <button
          type="button"
          onClick={handleLocate}
          disabled={geoLoading}
          className="absolute bottom-3 left-3 z-[1000] flex items-center gap-2 rounded-lg bg-primary-600 px-3 py-2 text-xs font-medium text-white shadow-md hover:bg-primary-700 disabled:opacity-60"
        >
          {geoLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Navigation className="w-4 h-4" />}
          Me localiser
        </button>
      </div>

      {geoError && <p className="text-red-500 text-xs">{geoError}</p>}

      <div className="flex items-center justify-between gap-2">
        <p className="text-xs text-earth-500">
          {hasPosition
            ? `Position : ${value.latitude!.toFixed(5)}, ${value.longitude!.toFixed(5)}`
            : 'Cliquez sur la carte, déplacez le marqueur ou utilisez « Me localiser » pour fixer la position.'}
        </p>
        {hasPosition && (
          <button
            type="button"
            onClick={() => onChange({ latitude: null, longitude: null })}
            className="flex items-center gap-1 text-xs text-earth-400 hover:text-red-500"
          >
            <X className="w-3 h-3" /> Effacer
          </button>
        )}
      </div>
    </div>
  );
};
