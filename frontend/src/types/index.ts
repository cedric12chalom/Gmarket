export type UserRole = 'producteur' | 'acheteur' | 'livreur' | 'admin';
export type UserStatus = 'actif' | 'suspendu';
export type OrderStatus = 'en_attente' | 'confirmee' | 'payee' | 'en_cours' | 'livree' | 'annulee';
export type PaymentStatus = 'en_attente' | 'valide' | 'echoue' | 'rembourse';
export type DeliveryMode = 'livraison';
export type DeliveryStatus = 'assignee' | 'en_cours' | 'livree' | 'echouee';
export type TransportType = 'standard' | 'rapide' | 'refrigere';
export type MoyenDeplacement = 'pied' | 'velo' | 'moto' | 'voiture' | 'camionnette';
export type StatutVerificationLivreur = 'en_attente' | 'verifie' | 'valide' | 'refuse';
export type LotStatus = 'disponible' | 'epuise' | 'expire';
export type FreshnessIndex = 'tres_frais' | 'frais' | 'a_consommer' | 'expire';
export type PaymentMethod = 'mobile_money' | 'carte' | 'especes';

export interface User {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  telephone: string;
  role: UserRole;
  statut: UserStatus;
  date_inscription: string;
  latitude?: number | null;
  longitude?: number | null;
  ville?: string | null;
  quartier?: string | null;
}

export interface Producteur extends User {
  localisation: string;
  description_exploitation: string;
}

export interface Acheteur extends User {
  adresse_livraison: string;
}

export interface Livreur extends User {
  type_transport: TransportType;
  disponible: boolean;
  telephone_service: string | null;
  chat_actif: boolean;
  cni_numero: string | null;
  cni_photo: string | null;
  photo_profil: string | null;
  moyen_deplacement: MoyenDeplacement | null;
  vehicule_plaque: string | null;
  vehicule_marque_modele: string | null;
  mobile_money_numero: string | null;
  contact_urgence_nom: string | null;
  contact_urgence_telephone: string | null;
  conditions_livraison_accepte_le: string | null;
  statut_verification: StatutVerificationLivreur;
  motif_refus: string | null;
  verifie_le: string | null;
  valide_le: string | null;
}

export interface Categorie {
  id: number;
  nom: string;
  type_transport: TransportType;
  commission_fixe: number;
  commission_pourcent: number;
}

export interface Produit {
  id: number;
  nom: string;
  description: string;
  unite: string;
  prix_min: number;
  prix_max: number;
  photo: string;
  categorie: Categorie;
  varietes: Variete[];
}

export interface Variete {
  id: number;
  nom: string;
  produit_id: number;
}

export interface Lot {
  id: number;
  produit: Produit;
  producteur: Producteur;
  quantite_disponible: number;
  quantite_reservee: number;
  prix_producteur: number;
  date_recolte: string;
  date_expiration: string | null;
  duree_conservation: number;
  jours_avant_retrait: number | null;
  variete: Variete | null;
  qr_code: string;
  statut: LotStatus;
  indice_fraicheur: FreshnessIndex;
  latitude: number | null;
  longitude: number | null;
}

export interface LigneCommande {
  id: number;
  lot: Lot;
  quantite: number;
  prix_unitaire: number;
  sous_total: number;
}

export interface Commande {
  id: number;
  acheteur: Acheteur;
  statut: OrderStatus;
  mode_recuperation: DeliveryMode;
  date_commande: string;
  montant_produits: number;
  commission: number;
  frais_livraison: number;
  montant_total: number;
  lignes: LigneCommande[];
}

export interface Paiement {
  id: number;
  commande: Commande;
  montant: number;
  methode: PaymentMethod;
  statut: PaymentStatus;
  reference: string;
  date_paiement: string;
}

export interface Livraison {
  id: number;
  commande: Commande;
  livreur: Livreur;
  statut: DeliveryStatus;
  adresse_livraison: string;
  date_retrait: string;
  date_livraison: string;
  date_reception_confirmee: string | null;
  photo_retrait: string;
  photo_livraison: string;
  frais: number;
}

export interface Litige {
  id: number;
  commande: Commande;
  motif: string;
  description: string;
  statut: 'ouvert' | 'en_traitement' | 'resolu' | 'rejete';
  date_creation: string;
  resolution: string;
  livraison?: {
    id: number;
    livreur?: User;
    statut: string;
    adresse_livraison?: string;
    date_retrait?: string;
    date_livraison?: string;
    frais?: number;
  } | null;
  admin_traiteur?: User | null;
}

export interface Notification {
  id: number;
  titre: string;
  message: string;
  lu: boolean;
  date_envoi: string;
}

export interface Message {
  id: number;
  livraison_id: number;
  expediteur: User;
  contenu: string;
  date_envoi: string;
  lu: boolean;
}

export interface Avis {
  id: number;
  note: number;
  commentaire: string;
  date_avis: string;
}

export interface CartItem {
  lot: Lot;
  quantite: number;
}

export interface LoginCredentials {
  email: string;
  mot_de_passe: string;
}

export interface RegisterData {
  nom: string;
  prenom: string;
  email: string;
  telephone: string;
  mot_de_passe: string;
  role: UserRole;
  ville?: string;
  quartier?: string;
  localisation?: string;
  adresse_livraison?: string;
  type_transport?: string;
  cni_numero?: string;
  cni_photo?: string;
  photo_profil?: string;
  moyen_deplacement?: string;
  vehicule_plaque?: string;
  vehicule_marque_modele?: string;
  mobile_money_numero?: string;
  contact_urgence_nom?: string;
  contact_urgence_telephone?: string;
  conditions_livraison_accepte?: boolean;
}


export interface LoginData {
  email: string;
  mot_de_passe: string;
}

export interface ApiResponse<T = any> {
  data: T;
  message?: string;
  status: number;
}

export interface PaymentData {
  commande_id: number;
  montant: number;
  methode: PaymentMethod;
}

export interface Order {
  id: number;
  acheteur_id: number;
  statut: OrderStatus;
  mode_recuperation: DeliveryMode;
  date_commande: string;
  montant_total: number;
  lignes: LigneCommande[];
}
