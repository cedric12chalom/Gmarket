import React, { useState, useCallback } from "react";
import { useQuery } from "react-query";
import { useSearchParams } from "react-router-dom";
import { lotApi, categorieApi, walletApi } from "@/services/api";
import { ProductCard } from "@/components/Catalogue/ProductCard";
import { CatalogueFilters } from "@/components/Catalogue/CatalogueFilters";
import { Navigation, Sparkles } from "lucide-react";
import { requestLocation } from "@/utils/location";
import { useAuthContext } from "@/contexts/AuthContext";
import { toast } from "react-toastify";

export const CataloguePage: React.FC = () => {
  const { user, isAuthenticated } = useAuthContext();
  const [searchParams] = useSearchParams();
  const initialSearch = searchParams.get("search") || "";

  const [search, setSearch] = useState(initialSearch);
  const [categorie, setCategorie] = useState("");
  const [prixMin, setPrixMin] = useState("");
  const [prixMax, setPrixMax] = useState("");
  const [fraicheur, setFraicheur] = useState("");
  const [nearMe, setNearMe] = useState(false);
  const [userPosition, setUserPosition] = useState<{ lat: number; lng: number } | null>(null);

  const handleNearMe = useCallback(async () => {
    if (nearMe) {
      setNearMe(false);
      setUserPosition(null);
      return;
    }
    const pos = await requestLocation();
    if (pos) {
      setUserPosition(pos);
      setNearMe(true);
      if (isAuthenticated && user) {
        walletApi.updatePosition(pos.lat, pos.lng).catch(() => {});
      }
    } else {
      toast.info("Activez la géolocalisation pour utiliser le filtre de proximité.");
    }
  }, [nearMe, isAuthenticated, user]);

  const queryParams: any = { search, categorie, prix_min: prixMin, prix_max: prixMax, fraicheur };
  if (nearMe && userPosition) {
    queryParams.lat = userPosition.lat;
    queryParams.lng = userPosition.lng;
    queryParams.rayon_km = 50;
  }

  const { data: lots, isLoading } = useQuery(
    ["lots", search, categorie, prixMin, prixMax, fraicheur, nearMe, userPosition?.lat, userPosition?.lng],
    () => lotApi.getAll(queryParams).then(r => r.data),
    { enabled: !nearMe || !!userPosition }
  );
  const { data: categories } = useQuery("categories", () => categorieApi.getAll().then(r => r.data));

  return (
    <div className="bg-[#F4F6F4] min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Header section */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div>
            <div className="inline-flex items-center gap-1.5 text-xs font-extrabold text-[#0A4D3C] uppercase tracking-wider mb-1">
              <Sparkles className="w-3.5 h-3.5 text-[#84CC16]" /> Épicerie Agricole Directe
            </div>
            <h1 className="text-3xl font-black text-earth-900 tracking-tight">Catalogue de la Ferme</h1>
            <p className="text-xs text-earth-500 font-medium">Produits récoltés localement avec indice de fraîcheur garanti</p>
          </div>

          <button
            onClick={handleNearMe}
            className={`flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-extrabold transition-all duration-200 border-2 shadow-xs ${
              nearMe 
                ? "bg-[#0A4D3C] text-[#9FE870] border-[#0A4D3C]" 
                : "bg-white text-earth-800 border-gray-200 hover:border-[#0A4D3C]"
            }`}
          >
            <Navigation className={`w-4 h-4 ${nearMe ? "animate-pulse" : ""}`} />
            {nearMe ? "Près de moi (Actif)" : "Producteurs à proximité"}
          </button>
        </div>

        {/* Filters bar */}
        <CatalogueFilters
          search={search} onSearchChange={setSearch}
          categorie={categorie} onCategorieChange={setCategorie}
          prixMin={prixMin} onPrixMinChange={setPrixMin}
          prixMax={prixMax} onPrixMaxChange={setPrixMax}
          fraicheur={fraicheur} onFraicheurChange={setFraicheur}
          categories={categories || []}
        />

        {/* Products Grid */}
        {isLoading ? (
          <div className="flex justify-center py-24">
            <div className="w-10 h-10 border-4 border-[#0A4D3C] border-t-transparent rounded-full animate-spin" />
          </div>
        ) : (lots || []).length === 0 ? (
          <div className="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm max-w-lg mx-auto my-8">
            <div className="w-16 h-16 bg-emerald-50 text-[#0A4D3C] rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
              🍃
            </div>
            <h3 className="font-bold text-lg text-earth-900 mb-1">Aucun produit trouvé</h3>
            <p className="text-xs text-earth-500">Essayez de modifier vos critères de recherche ou de réinitialiser vos filtres.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {(lots || []).map((lot: any) => (
              <ProductCard key={lot.id} lot={lot} />
            ))}
          </div>
        )}

      </div>
    </div>
  );
};
