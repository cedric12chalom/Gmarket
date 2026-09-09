import { useState, useCallback, useEffect } from 'react';

interface GeoLocationState {
  latitude: number;
  longitude: number;
}

interface UseGeoLocationReturn {
  position: GeoLocationState | null;
  error: string | null;
  loading: boolean;
  getPosition: () => void;
}

export function useGeoLocation(watch = false): UseGeoLocationReturn {
  const [position, setPosition] = useState<GeoLocationState | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const getPosition = useCallback(() => {
    if (!navigator.geolocation) {
      setError('La géolocalisation n\'est pas supportée par ce navigateur.');
      return;
    }
    setLoading(true);
    setError(null);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setPosition({
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
        });
        setLoading(false);
      },
      (err) => {
        setError(err.message || 'Impossible d\'obtenir la position.');
        setLoading(false);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, []);

  useEffect(() => {
    if (!watch) {
      return;
    }

    if (!navigator.geolocation) {
      setError('La géolocalisation n\'est pas supportée par ce navigateur.');
      return;
    }

    setLoading(true);
    const watchId = navigator.geolocation.watchPosition(
      (pos) => {
        setPosition({
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
        });
        setLoading(false);
      },
      (err) => {
        setError(err.message || 'Impossible d\'obtenir la position.');
        setLoading(false);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );

    return () => {
      navigator.geolocation.clearWatch(watchId);
    };
  }, [watch, getPosition]);

  return { position, error, loading, getPosition };
}
