import axios from 'axios';
import { toast } from 'react-toastify';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    const message = error.response?.data?.message || 'Une erreur est survenue';
    toast.error(message);
    return Promise.reject(error);
  }
);

// Auth
export const authApi = {
  login: (email: string, password: string) =>
    api.post('/auth/login', { email, mot_de_passe: password }),
  register: (data: any) => api.post('/auth/register', data),
  verifyEmail: (email: string, code: string) =>
    api.post('/auth/verify-email', { email, code }),
  resendVerification: (email: string) =>
    api.post('/auth/resend-verification', { email }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
};

// Produits
export const produitApi = {
  getAll: (params?: any) => api.get('/produits', { params }),
  getById: (id: number) => api.get(`/produits/${id}`),
  getLots: (id: number) => api.get(`/produits/${id}/lots`),
};

// Lots
export const lotApi = {
  getAll: (params?: any) => api.get('/lots', { params }),
  getById: (id: number) => api.get(`/lots/${id}`),
  getByProducteur: (producteurId: number) => api.get(`/lots/producteur/${producteurId}`),
  create: (data: any) => api.post('/lots', data),
  update: (id: number, data: any) => api.put(`/lots/${id}`, data),
  delete: (id: number) => api.delete(`/lots/${id}`),
  scanQR: (qrCode: string) => api.get(`/lots/qr/${qrCode}`),
};
export interface RegisterData{

    nom:string;

    prenom:string;

    email:string;

    telephone:string;

    mot_de_passe:string;

    role:string;

    localisation?:string;

    adresse_livraison?:string;

    type_transport?:string;

    ville?:string;

    quartier?:string;

    cni_numero?:string;

    cni_photo?:string;

    photo_profil?:string;

    moyen_deplacement?:string;

    vehicule_plaque?:string;

    vehicule_marque_modele?:string;

    mobile_money_numero?:string;

    contact_urgence_nom?:string;

    contact_urgence_telephone?:string;

    conditions_livraison_accepte?:boolean;

}

// Commandes
export const commandeApi = {
  getAll: () => api.get('/commandes'),
  getById: (id: number) => api.get(`/commandes/${id}`),
  getByAcheteur: (acheteurId: number) => api.get(`/commandes/acheteur/${acheteurId}`),
  getByProducteur: (producteurId: number) => api.get(`/commandes/producteur/${producteurId}`),
  create: (data: any) => api.post('/commandes', data),
  estimerFrais: (data: { lignes: { lot_id: number; quantite: number }[]; livreur_id?: number }) => api.post('/commandes/estimation-frais', data),
  confirm: (id: number) => api.put(`/commandes/${id}/confirmer`),
  annuler: (id: number) => api.put(`/commandes/${id}/annuler`),
  confirmerReception: (id: number) => api.put(`/commandes/${id}/confirmer-reception`),
};

// Paiements
export const paiementApi = {
  create: (data: any) => api.post('/paiements', data),
  verifier: (id: number) => api.get(`/paiements/${id}/verifier`),
  valider: (reference: string) => api.post('/paiements/valider', { reference }),
  getByCommande: (commandeId: number) => api.get(`/paiements/commande/${commandeId}`),
};

// Livraisons
export const livraisonApi = {
  getAll: () => api.get('/livraisons'),
  getById: (id: number) => api.get(`/livraisons/${id}`),
  getByLivreur: (livreurId: number) => api.get(`/livraisons/livreur/${livreurId}`),
  getByCommande: (commandeId: number) => api.get(`/livraisons/commande/${commandeId}`),
  getDisponibles: (params?: any) => api.get('/livraisons/disponibles', { params }),
  accepter: (id: number) => api.put(`/livraisons/${id}/accepter`),
  assigner: (id: number, livreurId: number) => api.put(`/livraisons/${id}/assigner`, { livreur_id: livreurId }),
  confirmerLivraison: (id: number, data: any) => api.put(`/livraisons/${id}/livrer`, data),
};

// Utilisateurs
export const utilisateurApi = {
  getAll: () => api.get('/utilisateurs'),
  getById: (id: number) => api.get(`/utilisateurs/${id}`),
  update: (id: number, data: any) => api.put(`/utilisateurs/${id}`, data),
  updateProfile: (data: any) => api.put('/utilisateurs/profile', data),
};

// Catégories
export const categorieApi = {
  getAll: () => api.get('/categories'),
  getById: (id: number) => api.get(`/categories/${id}`),
};

// Notifications
export const notificationApi = {
  getAll: () => api.get('/notifications'),
  markAsRead: (id: number) => api.put(`/notifications/${id}/lu`),
  markAllAsRead: () => api.put('/notifications/lu'),
};


// Carte (Geo markers)
export const carteApi = {
  getMarqueurs: (params?: any) => api.get('/carte/marqueurs', { params }),
};

// Demandes produit (producteur)
export const demandeProduitApi = {
  getAll: () => api.get('/demandes-produit'),
  create: (data: { nom_produit: string; categorie_suggeree?: string; description?: string; unite?: string }) =>
    api.post('/demandes-produit', data),
  traiter: (id: number, statut: string, motif_refus?: string) =>
    api.put(`/demandes-produit/${id}/traiter`, { statut, motif_refus }),
};

// Livreurs
export const livreurApi = {
  getDisponibles: (params?: any) => api.get('/livreurs/disponibles', { params }),
};

// Messages (routes sous /api/livraisons/{id}/messages)
export const messageApi = {
  getByLivraison: (livraisonId: number) => api.get(`/livraisons/${livraisonId}/messages`),
  send: (livraisonId: number, contenu: string) => api.post(`/livraisons/${livraisonId}/messages`, { contenu }),
  markAsRead: (livraisonId: number) => api.put(`/livraisons/${livraisonId}/messages/lu`),
};

// Wallet
export const walletApi = {
  getSolde: () => api.get('/utilisateurs/moi/solde'),
  recharger: (montant: number, numero: string) => api.post('/utilisateurs/moi/solde/recharger', { montant, numero }),
  verifierRecharge: (reference: string) => api.put(`/utilisateurs/moi/solde/recharger/${reference}/verifier`),
  updatePosition: (lat: number, lng: number) => api.put('/utilisateurs/moi/position', { latitude: lat, longitude: lng }),
};

// Litiges (admin)
export const litigeApi = {
  getAll: () => api.get('/admin/litiges'),
  getById: (id: number) => api.get(`/admin/litiges/${id}`),
  create: (data: { commande_id: number; motif: string; description?: string }) => api.post(`/commandes/${data.commande_id}/litige`, data),
  update: (id: number, data: { statut?: string; resolution?: string }) => api.put(`/admin/litiges/${id}`, data),
};

export default api;
