import { useState, useEffect, useRef, useCallback } from 'react';
import { geoApi, GeoPosition } from '@/services/geoApi';

interface UseRealtimeTrackingReturn {
  positions: GeoPosition[];
  loading: boolean;
  error: string | null;
  startTracking: () => void;
  stopTracking: () => void;
  refresh: () => void;
}

export function useRealtimeTracking(intervalMs = 10000): UseRealtimeTrackingReturn {
  const [positions, setPositions] = useState<GeoPosition[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const fetchPositions = useCallback(async () => {
    try {
      setLoading(true);
      const res = await geoApi.getPositions('delivery_person');
      setPositions(res.data);
      setError(null);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Erreur de chargement des positions');
    } finally {
      setLoading(false);
    }
  }, []);

  const startTracking = useCallback(() => {
    if (intervalRef.current) return;
    fetchPositions();
    intervalRef.current = setInterval(fetchPositions, intervalMs);
  }, [fetchPositions, intervalMs]);

  const stopTracking = useCallback(() => {
    if (intervalRef.current) {
      clearInterval(intervalRef.current);
      intervalRef.current = null;
    }
  }, []);

  const refresh = useCallback(() => {
    fetchPositions();
  }, [fetchPositions]);

  useEffect(() => {
    return () => {
      stopTracking();
    };
  }, [stopTracking]);

  return { positions, loading, error, startTracking, stopTracking, refresh };
}
