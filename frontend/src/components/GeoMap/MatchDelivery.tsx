import React, { useState } from 'react';
import { geoApi, MatchResult } from '@/services/geoApi';
import { Loader2, MapPin } from 'lucide-react';

interface MatchDeliveryProps {
  deliveryId: number;
  onClose: () => void;
  onAssigned: (result: MatchResult) => void;
}

export const MatchDelivery: React.FC<MatchDeliveryProps> = ({ deliveryId, onClose, onAssigned }) => {
  const [matchResult, setMatchResult] = useState<MatchResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleFindNearest = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await geoApi.matchDelivery(deliveryId);
      setMatchResult(res.data);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Erreur lors de la recherche');
    } finally {
      setLoading(false);
    }
  };

  const handleAutoAssign = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await geoApi.autoAssignDelivery(deliveryId);
      setMatchResult(res.data);
      onAssigned(res.data);
      onClose();
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Erreur lors de l\'attribution');
    } finally {
      setLoading(false);
    }
  };

  const formatDistance = (meters: number): string => {
    if (meters >= 1000) return `${(meters / 1000).toFixed(1)} km`;
    return `${Math.round(meters)} m`;
  };

  const formatDuration = (seconds: number): string => {
    const min = Math.round(seconds / 60);
    if (min >= 60) return `${Math.floor(min / 60)}h ${min % 60}min`;
    return `${min} min`;
  };

  return (
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40" onClick={onClose}>
      <div className="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-bold text-earth-900 flex items-center gap-2">
            <MapPin className="w-5 h-5 text-primary-600" />
            Attribution livreur
          </h3>
          <button onClick={onClose} className="text-earth-400 hover:text-earth-600 text-xl leading-none">&times;</button>
        </div>

        {error && (
          <div className="bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4">{error}</div>
        )}

        {loading && (
          <div className="flex justify-center py-6">
            <Loader2 className="w-6 h-6 text-primary-600 animate-spin" />
          </div>
        )}

        {matchResult && !loading && (
          <div className="bg-earth-50 rounded-xl p-4 mb-4">
            <p className="font-semibold text-earth-900 mb-2">
              🚚 {matchResult.livreur.prenom} {matchResult.livreur.nom}
            </p>
            <div className="text-sm text-earth-600 space-y-1">
              <p>📏 Distance: {formatDistance(matchResult.distance)}</p>
              <p>⏱️ Durée: {formatDuration(matchResult.duration)}</p>
              {matchResult.livreur.type_transport && (
                <p>🚛 Transport: {matchResult.livreur.type_transport}</p>
              )}
            </div>
          </div>
        )}

        <div className="flex gap-3">
          <button
            onClick={handleFindNearest}
            disabled={loading}
            className="flex-1 py-2.5 border-2 border-primary-600 text-primary-600 font-medium rounded-lg hover:bg-primary-50 disabled:opacity-50"
          >
            Trouver le plus proche
          </button>
          <button
            onClick={handleAutoAssign}
            disabled={loading || !matchResult}
            className="flex-1 py-2.5 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 disabled:opacity-50"
          >
            Attribution auto
          </button>
        </div>
      </div>
    </div>
  );
};