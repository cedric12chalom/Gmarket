import { FreshnessIndex } from '@/types';

export const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0,
  }).format(price);
};

export const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

export const formatDateTime = (date: string): string => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

export const calculateFreshness = (dateRecolte: string, dureeConservation: number): FreshnessIndex => {
  const recolte = new Date(dateRecolte);
  const today = new Date();
  const diffDays = Math.floor((today.getTime() - recolte.getTime()) / (1000 * 60 * 60 * 24));

  if (diffDays > dureeConservation || diffDays > 7) return 'expire';
  if (diffDays <= 2) return 'tres_frais';
  if (diffDays <= 5) return 'frais';
  return 'a_consommer';
};

export const freshnessLabel = (index: FreshnessIndex): string => {
  const labels: Record<FreshnessIndex, string> = {
    tres_frais: 'Très frais',
    frais: 'Frais',
    a_consommer: 'À consommer rapidement',
    expire: 'Périmé',
  };
  return labels[index];
};

export const freshnessColor = (index: FreshnessIndex): string => {
  const colors: Record<FreshnessIndex, string> = {
    tres_frais: 'bg-green-500',
    frais: 'bg-primary-500',
    a_consommer: 'bg-secondary-500',
    expire: 'bg-red-500',
  };
  return colors[index];
};

/**
 * Urgence de retrait automatique d'un lot, exprimée en jours restants.
 * Réutilise la même palette que le FreshnessBadge (dégradé olive -> rouge)
 * pour rester cohérent avec le langage visuel fraîcheur de TerraLink.
 */
export const removalUrgency = (jours: number | null | undefined): { label: string; color: string } => {
  if (jours === null || jours === undefined) return { label: 'Expiration inconnue', color: '#9f1d1d' };
  if (jours <= 0) return { label: 'Retiré du catalogue', color: '#9f1d1d' };
  if (jours === 1) return { label: 'Retrait automatique demain', color: '#c9622a' };
  if (jours <= 3) return { label: `Retrait automatique dans ${jours} j`, color: '#c9622a' };
  return { label: `Retrait automatique dans ${jours} j`, color: '#82a468' };
};

export const orderStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    en_attente: 'En attente',
    confirmee: 'Confirmée',
    payee: 'Payée',
    en_cours: 'En cours de livraison',
    livree: 'Livrée',
    annulee: 'Annulée',
  };
  return labels[status] || status;
};

export const orderStatusColor = (status: string): string => {
  const colors: Record<string, string> = {
    en_attente: 'badge-warning',
    confirmee: 'badge-info',
    payee: 'badge-success',
    en_cours: 'badge-info',
    livree: 'badge-success',
    annulee: 'badge-danger',
  };
  return colors[status] || 'badge-info';
};

export const paymentStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    en_attente: 'En attente',
    valide: 'Validé',
    echoue: 'Échoué',
    rembourse: 'Remboursé',
  };
  return labels[status] || status;
};

export const deliveryStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    assignee: 'Assignée',
    en_cours: 'En cours',
    livree: 'Livrée',
    echouee: 'Échouée',
  };
  return labels[status] || status;
};

export const roleLabel = (role: string): string => {
  const labels: Record<string, string> = {
    producteur: 'Producteur',
    acheteur: 'Acheteur',
    livreur: 'Livreur',
    admin: 'Administrateur',
  };
  return labels[role] || role;
};

export const transportLabel = (type: string): string => {
  const labels: Record<string, string> = {
    standard: 'Standard',
    rapide: 'Rapide',
    refrigere: 'Réfrigéré',
  };
  return labels[type] || type;
};

export const truncateText = (text: string, maxLength: number): string => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

// Alias pour compatibilité
export const getFreshnessLabel = freshnessLabel;
export const getFreshnessColor = freshnessColor;
export const getOrderStatusLabel = orderStatusLabel;
export const getOrderStatusColor = orderStatusColor;
export const getPaymentStatusLabel = paymentStatusLabel;
export const getDeliveryStatusLabel = deliveryStatusLabel;
export const getRoleLabel = roleLabel;
export const getTransportLabel = transportLabel;
