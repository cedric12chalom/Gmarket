import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from 'react-query';
import {
  Leaf, ArrowRight,
  CheckCircle2,
  Sparkles, ShoppingBag,
  Apple, Carrot, Fish, Milk, Coffee
} from 'lucide-react';
import { lotApi } from '@/services/api';
import { ProductCard } from '@/components/Catalogue/ProductCard';
import { Lot } from '@/types';

// Statistique animée / Counter
const AnimatedCounter: React.FC<{ end: number; suffix?: string; label: string }> = ({ end, suffix = '', label }) => {
  return (
    <div className="text-center">
      <div className="text-3xl lg:text-4xl font-black text-[#0A4D3C]">
        {end.toLocaleString()}{suffix}
      </div>
      <div className="text-earth-500 text-xs font-semibold mt-1">{label}</div>
    </div>
  );
};

// Fallback mock items pour démonstration immédiate si la DB est vide
const MOCK_LOTS: Lot[] = [
  {
    id: 101,
    quantite_disponible: 85,
    quantite_reservee: 5,
    prix_producteur: 1500,
    date_recolte: new Date().toISOString(),
    date_expiration: null,
    duree_conservation: 7,
    jours_avant_retrait: 7,
    indice_fraicheur: 'tres_frais',
    qr_code: 'QR101',
    statut: 'disponible',
    variete: null,
    latitude: null,
    longitude: null,
    produit: {
      id: 1,
      nom: 'Tomates Bio Fraîches',
      description: 'Récoltées à maturité à Obala',
      unite: '500g',
      photo: 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=400&q=80',
      prix_min: 1000,
      prix_max: 2000,
      varietes: [],
      categorie: { id: 1, nom: 'Légumes', type_transport: 'standard', commission_fixe: 0, commission_pourcent: 5 }
    },
    producteur: {
      id: 1, nom: 'Kamga', prenom: 'Paul', email: 'paul@terralink.cm', telephone: '690000001',
      role: 'producteur', statut: 'actif', date_inscription: '2025-01-01',
      localisation: 'Ferme Obala', description_exploitation: 'Maraîchage bio'
    }
  },
  {
    id: 102,
    quantite_disponible: 40,
    quantite_reservee: 2,
    prix_producteur: 2500,
    date_recolte: new Date().toISOString(),
    date_expiration: null,
    duree_conservation: 10,
    jours_avant_retrait: 10,
    indice_fraicheur: 'tres_frais',
    qr_code: 'QR102',
    statut: 'disponible',
    variete: null,
    latitude: null,
    longitude: null,
    produit: {
      id: 2,
      nom: 'Avocats Pur Beurre',
      description: 'Avocats crémeux du Haut-Nkam',
      unite: 'Kg',
      photo: 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=400&q=80',
      prix_min: 2000,
      prix_max: 3500,
      varietes: [],
      categorie: { id: 2, nom: 'Fruits', type_transport: 'standard', commission_fixe: 0, commission_pourcent: 5 }
    },
    producteur: {
      id: 2, nom: 'Ngo', prenom: 'Marie', email: 'marie@terralink.cm', telephone: '690000002',
      role: 'producteur', statut: 'actif', date_inscription: '2025-01-01',
      localisation: 'Bafang', description_exploitation: 'Verger naturel'
    }
  },
  {
    id: 103,
    quantite_disponible: 180,
    quantite_reservee: 10,
    prix_producteur: 1200,
    date_recolte: new Date().toISOString(),
    date_expiration: null,
    duree_conservation: 14,
    jours_avant_retrait: 14,
    indice_fraicheur: 'frais',
    qr_code: 'QR103',
    statut: 'disponible',
    variete: null,
    latitude: null,
    longitude: null,
    produit: {
      id: 3,
      nom: 'Carottes Croquantes',
      description: 'Carottes fraîches de Dschang',
      unite: 'Bottillon',
      photo: 'https://images.unsplash.com/photo-1598170845058-12ef4a45753b?w=400&q=80',
      prix_min: 800,
      prix_max: 1800,
      varietes: [],
      categorie: { id: 1, nom: 'Légumes', type_transport: 'standard', commission_fixe: 0, commission_pourcent: 5 }
    },
    producteur: {
      id: 3, nom: 'Fosso', prenom: 'Jean', email: 'jean@terralink.cm', telephone: '690000003',
      role: 'producteur', statut: 'actif', date_inscription: '2025-01-01',
      localisation: 'Dschang', description_exploitation: 'Culture de légumes'
    }
  },
  {
    id: 104,
    quantite_disponible: 25,
    quantite_reservee: 0,
    prix_producteur: 3200,
    date_recolte: new Date().toISOString(),
    date_expiration: null,
    duree_conservation: 8,
    jours_avant_retrait: 8,
    indice_fraicheur: 'frais',
    qr_code: 'QR104',
    statut: 'disponible',
    variete: null,
    latitude: null,
    longitude: null,
    produit: {
      id: 4,
      nom: 'Ananas Doux de Penja',
      description: 'Ananas ultra sucrés gorgés de soleil',
      unite: 'Pièce',
      photo: 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?w=400&q=80',
      prix_min: 2500,
      prix_max: 4000,
      varietes: [],
      categorie: { id: 2, nom: 'Fruits', type_transport: 'standard', commission_fixe: 0, commission_pourcent: 5 }
    },
    producteur: {
      id: 4, nom: 'Eboue', prenom: 'Samuel', email: 'samuel@terralink.cm', telephone: '690000004',
      role: 'producteur', statut: 'actif', date_inscription: '2025-01-01',
      localisation: 'Penja', description_exploitation: 'Plantation d ananas'
    }
  },
];

export const LandingPage: React.FC = () => {
  const [selectedBestCategory, setSelectedBestCategory] = useState<string>('all');

  // Query real data from API
  const { data: realLots } = useQuery('landing-lots', () => lotApi.getAll().then(r => r.data).catch(() => []));

  const displayLots: Lot[] = (realLots && realLots.length > 0) ? realLots : MOCK_LOTS;

  const filteredBestSelling = selectedBestCategory === 'all' 
    ? displayLots 
    : displayLots.filter(l => l.produit?.categorie?.nom?.toLowerCase().includes(selectedBestCategory.toLowerCase()));

  const categoryPills = [
    { name: 'Légumes', icon: Carrot, tag: 'Frais' },
    { name: 'Fruits', icon: Apple, tag: 'Saison' },
    { name: 'Viandes & Poulet', icon: Fish, tag: 'Bio' },
    { name: 'Laiterie & Œufs', icon: Milk, tag: 'Local' },
    { name: 'Jus & Boissons', icon: Coffee, tag: 'Naturel' },
  ];

  return (
    <div className="bg-[#F4F6F4] min-h-screen text-earth-900 overflow-x-hidden font-sans">
      
      {/* 1. HERO SECTION */}
      <section className="bg-[#0A4D3C] text-white hero-curve-bottom relative pt-8 pb-16 lg:pb-24 overflow-hidden">
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-[#9FE870]/10 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute bottom-0 left-0 w-[350px] h-[350px] bg-emerald-600/10 rounded-full blur-2xl pointer-events-none" />

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div className="grid lg:grid-cols-12 gap-8 items-center">
            <div className="lg:col-span-7 space-y-6">
              <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/15 text-[#9FE870] text-xs font-bold tracking-wide uppercase">
                <Sparkles className="w-4 h-4" /> Direct des plantations du Cameroun
              </div>

              <h1 className="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.1]">
                Nous apportons le <span className="text-[#9FE870]">marché frais</span> à votre porte
              </h1>

              <p className="text-emerald-100 text-sm sm:text-base max-w-xl leading-relaxed font-normal">
                Découvrez des produits agricoles 100% locaux, bio et tracés par QR Code. Des fruits, légumes et tubercules directement récoltés par nos producteurs.
              </p>

              <div className="flex flex-wrap items-center gap-4 pt-2">
                <Link 
                  to="/catalogue" 
                  className="px-8 py-4 bg-[#9FE870] text-[#07362A] font-extrabold text-sm rounded-full hover:bg-[#8EE25B] transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                >
                  Commander maintenant <ArrowRight className="w-4 h-4" />
                </Link>

                <Link 
                  to="/register" 
                  className="px-7 py-4 border-2 border-white/30 text-white font-bold text-sm rounded-full hover:bg-white/10 hover:border-white transition-all backdrop-blur-sm"
                >
                  Espace Producteur
                </Link>
              </div>

              <div className="pt-4 flex items-center gap-6 text-xs text-emerald-200">
                <div className="flex items-center gap-2">
                  <CheckCircle2 className="w-4 h-4 text-[#9FE870]" />
                  <span>Traçabilité QR Code</span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle2 className="w-4 h-4 text-[#9FE870]" />
                  <span>Livraison en 15-45 min</span>
                </div>
              </div>
            </div>

            <div className="lg:col-span-5 relative mt-6 lg:mt-0">
              <div className="relative bg-white/10 backdrop-blur-md rounded-3xl p-6 border border-white/20 shadow-2xl">
                <div className="absolute -top-4 -left-4 bg-[#9FE870] text-[#07362A] px-4 py-2 rounded-2xl font-black text-xs shadow-lg flex items-center gap-2 animate-bounce">
                  <Leaf className="w-4 h-4" /> 98% Indice Fraîcheur
                </div>

                <div className="bg-white rounded-2xl p-4 text-earth-900 shadow-md space-y-3">
                  <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center font-black text-[#0A4D3C]">
                        🌾
                      </div>
                      <div>
                        <h4 className="font-bold text-sm text-earth-900">Panier du Fermier Bio</h4>
                        <p className="text-xs text-earth-500">Récolte du matin • Obala</p>
                      </div>
                    </div>
                    <span className="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                      Dispo
                    </span>
                  </div>

                  <div className="grid grid-cols-3 gap-2 text-center text-xs">
                    <div className="bg-[#F8FAF8] p-2 rounded-xl border border-gray-100">
                      <span className="text-xl">🍅</span>
                      <p className="font-bold text-earth-800 text-[11px] mt-1">Tomates</p>
                      <p className="text-emerald-700 font-extrabold text-[10px]">1,200F/kg</p>
                    </div>
                    <div className="bg-[#F8FAF8] p-2 rounded-xl border border-gray-100">
                      <span className="text-xl">🥑</span>
                      <p className="font-bold text-earth-800 text-[11px] mt-1">Avocats</p>
                      <p className="text-emerald-700 font-extrabold text-[10px]">2,500F/kg</p>
                    </div>
                    <div className="bg-[#F8FAF8] p-2 rounded-xl border border-gray-100">
                      <span className="text-xl">🥕</span>
                      <p className="font-bold text-earth-800 text-[11px] mt-1">Carottes</p>
                      <p className="text-emerald-700 font-extrabold text-[10px]">1,000F/bte</p>
                    </div>
                  </div>

                  <Link 
                    to="/catalogue" 
                    className="w-full py-2.5 bg-[#0A4D3C] text-[#9FE870] font-bold text-xs rounded-xl flex items-center justify-center gap-2 hover:bg-[#07362A] transition-colors"
                  >
                    Voir tous les lots disponibles <ArrowRight className="w-3.5 h-3.5" />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. CATEGORY PILLS BAR */}
      <section className="-mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20">
        <div className="bg-white rounded-3xl p-3 shadow-lg border border-gray-100 flex items-center gap-3 overflow-x-auto scrollbar-none">
          <Link
            to="/catalogue"
            className="flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#0A4D3C] text-white font-bold text-xs whitespace-nowrap shadow-sm hover:opacity-95 transition-all"
          >
            <ShoppingBag className="w-4 h-4 text-[#9FE870]" />
            <span>Tous les rayons</span>
          </Link>

          {categoryPills.map((cat) => (
            <Link
              key={cat.name}
              to={`/catalogue?search=${encodeURIComponent(cat.name)}`}
              className="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-[#F4F6F4] hover:bg-[#EAF3EA] text-earth-800 font-semibold text-xs whitespace-nowrap transition-all border border-gray-100 group"
            >
              <div className="w-7 h-7 bg-white rounded-xl flex items-center justify-center text-[#0A4D3C] group-hover:scale-110 transition-transform shadow-2xs">
                <cat.icon className="w-4 h-4" />
              </div>
              <span>{cat.name}</span>
              <span className="text-[10px] text-emerald-700 bg-emerald-100/60 px-1.5 py-0.5 rounded-md font-bold">
                {cat.tag}
              </span>
            </Link>
          ))}
        </div>
      </section>

      {/* 3. RECOMMENDED SECTION */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h2 className="text-2xl font-black text-earth-900 tracking-tight">Recommandés pour vous</h2>
            <p className="text-xs text-earth-500 font-medium">Sélection fraîche du jour issue des fermes locales</p>
          </div>
          <Link to="/catalogue" className="text-xs font-bold text-[#0A4D3C] hover:text-emerald-700 flex items-center gap-1">
            Voir plus <ArrowRight className="w-4 h-4" />
          </Link>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {displayLots.slice(0, 4).map((lot) => (
            <ProductCard key={lot.id} lot={lot} />
          ))}
        </div>
      </section>

      {/* 4. PROMO CARDS */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div className="bg-[#FDE8E8] rounded-3xl p-6 border border-pink-100 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group">
            <div>
              <span className="text-xs font-extrabold text-rose-700 tracking-wider uppercase">Économisez</span>
              <h3 className="text-3xl font-black text-rose-900 mt-1">2,000 FCFA</h3>
              <p className="text-xs text-rose-800/80 mt-2 font-medium">Sur vos commandes de fruits & légumes frais.</p>
            </div>
            <Link to="/catalogue" className="mt-6 inline-flex items-center gap-1 text-xs font-bold text-rose-900 group-hover:translate-x-1 transition-transform">
              Profiter de l'offre <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>
          <div className="bg-[#FEEFDD] rounded-3xl p-6 border border-amber-100 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group">
            <div>
              <span className="text-xs font-extrabold text-amber-800 tracking-wider uppercase">Réduction</span>
              <h3 className="text-3xl font-black text-amber-950 mt-1">30% OFF</h3>
              <p className="text-xs text-amber-900/80 mt-2 font-medium">Directement auprès des producteurs.</p>
            </div>
            <Link to="/catalogue" className="mt-6 inline-flex items-center gap-1 text-xs font-bold text-amber-950 group-hover:translate-x-1 transition-transform">
              Voir la sélection <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>
          <div className="bg-[#E0F2FE] rounded-3xl p-6 border border-sky-100 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group">
            <div>
              <span className="text-xs font-extrabold text-sky-800 tracking-wider uppercase">Vente Flash</span>
              <h3 className="text-3xl font-black text-sky-950 mt-1">Jusqu'à 50%</h3>
              <p className="text-xs text-sky-900/80 mt-2 font-medium">Sur les produits à consommer rapidement.</p>
            </div>
            <Link to="/catalogue" className="mt-6 inline-flex items-center gap-1 text-xs font-bold text-sky-950 group-hover:translate-x-1 transition-transform">
              Saisir l'occasion <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>
          <div className="bg-[#F3E8FF] rounded-3xl p-6 border border-purple-100 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group">
            <div>
              <span className="text-xs font-extrabold text-purple-800 tracking-wider uppercase">Livraison</span>
              <h3 className="text-3xl font-black text-purple-950 mt-1">OFFERTE</h3>
              <p className="text-xs text-purple-900/80 mt-2 font-medium">Dès 15,000 FCFA de panier.</p>
            </div>
            <Link to="/catalogue" className="mt-6 inline-flex items-center gap-1 text-xs font-bold text-purple-950 group-hover:translate-x-1 transition-transform">
              Commander <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </section>

      {/* 5. BEST SELLERS */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <h2 className="text-2xl font-black text-earth-900 tracking-tight">Meilleures ventes de la semaine</h2>
            <p className="text-xs text-earth-500 font-medium">Les articles les plus plébiscités</p>
          </div>
          <div className="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
            {['all', 'Légumes', 'Fruits', 'Viandes'].map((cat) => (
              <button
                key={cat}
                onClick={() => setSelectedBestCategory(cat)}
                className={`px-4 py-1.5 rounded-full text-xs font-bold transition-all whitespace-nowrap ${
                  selectedBestCategory === cat
                    ? 'bg-[#0A4D3C] text-[#9FE870]'
                    : 'bg-white text-earth-700 hover:bg-earth-100 border border-gray-200'
                }`}
              >
                {cat === 'all' ? 'Tous les articles' : cat}
              </button>
            ))}
          </div>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {filteredBestSelling.slice(0, 4).map((lot) => (
            <ProductCard key={`best-${lot.id}`} lot={lot} />
          ))}
        </div>
      </section>

      {/* 6. APP BANNER */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-[#2D0B5A] text-white rounded-4xl p-8 lg:p-12 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-8 shadow-xl">
          <div className="space-y-4 max-w-xl text-center md:text-left z-10">
            <span className="bg-[#9FE870] text-[#07362A] text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">
              Épicerie en ligne Directe
            </span>
            <h2 className="text-3xl lg:text-4xl font-black leading-tight">
              Restez chez vous & recevez vos essentiels du marché !
            </h2>
            <p className="text-purple-200 text-xs sm:text-sm font-medium">
              Commandez facilement sur ordinateur ou mobile. Suivez la livraison en temps réel.
            </p>
            <div className="pt-2 flex flex-wrap justify-center md:justify-start gap-3">
              <Link to="/catalogue" className="px-6 py-3 bg-[#9FE870] text-[#07362A] font-extrabold text-xs rounded-full hover:bg-[#8EE25B] transition-all shadow-md">
                Découvrir le catalogue
              </Link>
            </div>
          </div>
          <div className="relative z-10 flex items-center justify-center">
            <div className="w-48 h-48 sm:w-56 sm:h-56 bg-white/10 rounded-full flex items-center justify-center border-4 border-white/20 backdrop-blur-sm">
              <span className="text-7xl sm:text-8xl">🧺</span>
            </div>
          </div>
        </div>
      </section>

      {/* 7. IMPACT */}
      <section className="py-16 bg-white my-8 border-y border-gray-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-8">
            <AnimatedCounter end={500} suffix="+" label="Producteurs Partenaires" />
            <AnimatedCounter end={1200} suffix="+" label="Lots Agricoles Récoltés" />
            <AnimatedCounter end={3500} suffix="+" label="Commandes Livrées" />
            <AnimatedCounter end={98} suffix="%" label="Taux d'Indice Fraîcheur" />
          </div>
        </div>
      </section>

      {/* 8. ACTORS */}
      <section className="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-10">
          <span className="bg-emerald-100 text-[#0A4D3C] text-xs font-bold px-3 py-1 rounded-full uppercase">Écosystème Agricole</span>
          <h2 className="text-3xl font-black text-earth-900 mt-2">Trois acteurs, une chaîne de valeur optimisée</h2>
        </div>
        <div className="grid md:grid-cols-3 gap-6">
          <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div className="w-12 h-12 bg-emerald-100 text-[#0A4D3C] rounded-2xl flex items-center justify-center font-bold mb-4 text-xl">🌱</div>
            <h3 className="font-bold text-lg text-earth-900 mb-2">Producteur Agricole</h3>
            <p className="text-xs text-earth-500 leading-relaxed mb-4">Publiez vos lots avec prix encadrés et indice de fraîcheur.</p>
            <Link to="/register" className="text-xs font-bold text-[#0A4D3C] hover:underline flex items-center gap-1">Devenir Producteur <ArrowRight className="w-3.5 h-3.5" /></Link>
          </div>
          <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div className="w-12 h-12 bg-sky-100 text-sky-800 rounded-2xl flex items-center justify-center font-bold mb-4 text-xl">🛒</div>
            <h3 className="font-bold text-lg text-earth-900 mb-2">Acheteur / Épicier</h3>
            <p className="text-xs text-earth-500 leading-relaxed mb-4">Achetez frais au meilleur prix. Vérifiez l'origine par QR Code.</p>
            <Link to="/catalogue" className="text-xs font-bold text-[#0A4D3C] hover:underline flex items-center gap-1">Explorer les produits <ArrowRight className="w-3.5 h-3.5" /></Link>
          </div>
          <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div className="w-12 h-12 bg-amber-100 text-amber-800 rounded-2xl flex items-center justify-center font-bold mb-4 text-xl">🚚</div>
            <h3 className="font-bold text-lg text-earth-900 mb-2">Livreur Dédié</h3>
            <p className="text-xs text-earth-500 leading-relaxed mb-4">Acceptez des missions de livraison géolocalisées.</p>
            <Link to="/register" className="text-xs font-bold text-[#0A4D3C] hover:underline flex items-center gap-1">Rejoindre l'équipe <ArrowRight className="w-3.5 h-3.5" /></Link>
          </div>
        </div>
      </section>
    </div>
  );
};
