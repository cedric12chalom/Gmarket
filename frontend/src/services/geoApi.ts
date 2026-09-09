import { api } from './api';

export interface GeoTrackPayload {
  latitude: number;
  longitude: number;
  type: 'delivery_person' | 'producer' | 'lot';
  reference_id?: number;
  emoji?: string;
}

export interface GeoPosition {
  id: number;
  user_id: number;
  reference_id: number;
  latitude: number;
  longitude: number;
  type: string;
  updated_at: string;
  emoji?: string;
  estimated_distance?: number;
  estimated_duration?: number;
}

export interface ProductionLocation {
  id: number;
  nom: string;
  prenom: string;
  localisation?: string | null;
  latitude: number;
  longitude: number;
  emoji: string;
}

export interface ProductionLot {
  id: number;
  produit: {
    id?: number | null;
    nom?: string | null;
    unite?: string | null;
    categorie?: string | null;
  };
  producteur_id?: number | null;
  prix_producteur: number;
  quantite_disponible: number;
  date_recolte?: string;
  indice_fraicheur: string;
  latitude: number | null;
  longitude: number | null;
  emoji: string;
}

export interface MatchResult {
  livreur: {
    id: number;
    nom: string;
    prenom: string;
    type_transport?: string | null;
    disponible?: boolean;
  };
  distance: number;
  duration: number;
  geometry: GeoJSON.Geometry;
}

export interface RouteWaypoint {
  latitude: number;
  longitude: number;
}

export interface DeliveryRouteInfo {
  distance: number;
  duration: number;
  geometry: GeoJSON.Geometry;
  waypoints: RouteWaypoint[];
}

export interface ActiveDelivery {
  id: number;
  livraison: unknown;
  route_geojson: GeoJSON.Geometry | null;
  route_distance?: number | null;
  route_duration?: number | null;
  route_waypoints?: RouteWaypoint[] | null;
}

// Utilise l'instance api existante (passe par le proxy Vite -> pas de CORS)
export const geoApi = {
  trackPosition: (data: GeoTrackPayload) => api.post('/geo/track', data),
  getPositions: (type?: string) =>
    api.get<GeoPosition[]>('/geo/positions', { params: type ? { type } : undefined }),
  getProductions: () =>
    api.get<{ producteurs: ProductionLocation[]; lots: ProductionLot[] }>('/geo/productions'),
  getDeliveries: () => api.get<ActiveDelivery[]>('/geo/deliveries'),
  matchDelivery: (id: number) => api.post<MatchResult>(`/geo/match/${id}`),
  autoAssignDelivery: (id: number) =>
    api.post<MatchResult & { message: string }>(`/geo/auto-assign/${id}`),
  getRoute: (id: number) => api.get<DeliveryRouteInfo>(`/geo/route/${id}`),
  getDeliveryPersonPosition: (id: number) =>
    api.get<GeoPosition>(`/geo/delivery-person/${id}/position`),
};
