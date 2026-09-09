import React from 'react';
import { Search, RefreshCw } from 'lucide-react';

interface FiltersProps {
  search: string;
  onSearchChange: (v: string) => void;
  categorie: string;
  onCategorieChange: (v: string) => void;
  prixMin: string;
  onPrixMinChange: (v: string) => void;
  prixMax: string;
  onPrixMaxChange: (v: string) => void;
  fraicheur: string;
  onFraicheurChange: (v: string) => void;
  categories: { id: number; nom: string }[];
}

export const CatalogueFilters: React.FC<FiltersProps> = ({
  search, onSearchChange, categorie, onCategorieChange,
  prixMin, onPrixMinChange, prixMax, onPrixMaxChange,
  fraicheur, onFraicheurChange, categories
}) => {
  const hasActiveFilters = search || categorie || prixMin || prixMax || fraicheur;

  const handleReset = () => {
    onSearchChange('');
    onCategorieChange('');
    onPrixMinChange('');
    onPrixMaxChange('');
    onFraicheurChange('');
  };

  return (
    <div className="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm mb-8 space-y-4">
      {/* Category Pills Bar (Horizontal scrollable) */}
      <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <button
          onClick={() => onCategorieChange('')}
          className={`px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all ${
            categorie === '' 
              ? 'bg-[#0A4D3C] text-[#9FE870] shadow-sm' 
              : 'bg-[#F4F6F4] text-earth-700 hover:bg-earth-200'
          }`}
        >
          Tous les produits
        </button>
        {categories.map((c) => (
          <button
            key={c.id}
            onClick={() => onCategorieChange(categorie === String(c.id) ? '' : String(c.id))}
            className={`px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all ${
              categorie === String(c.id)
                ? 'bg-[#0A4D3C] text-[#9FE870] shadow-sm'
                : 'bg-[#F4F6F4] text-earth-700 hover:bg-earth-200'
            }`}
          >
            {c.nom}
          </button>
        ))}
      </div>

      {/* Inputs grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t border-gray-100">
        <div className="relative">
          <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-earth-400" />
          <input
            value={search}
            onChange={(e) => onSearchChange(e.target.value)}
            placeholder="Rechercher par nom..."
            className="w-full pl-10 pr-4 py-2.5 bg-[#F8FAF8] border border-gray-200 rounded-2xl text-xs font-medium focus:ring-2 focus:ring-[#9FE870] outline-none"
          />
        </div>

        <select
          value={categorie}
          onChange={(e) => onCategorieChange(e.target.value)}
          className="w-full px-4 py-2.5 bg-[#F8FAF8] border border-gray-200 rounded-2xl text-xs font-medium focus:ring-2 focus:ring-[#9FE870] outline-none"
        >
          <option value="">Toutes les catégories</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>{c.nom}</option>
          ))}
        </select>

        <input
          value={prixMin}
          onChange={(e) => onPrixMinChange(e.target.value)}
          placeholder="Prix min (FCFA)"
          type="number"
          className="w-full px-4 py-2.5 bg-[#F8FAF8] border border-gray-200 rounded-2xl text-xs font-medium focus:ring-2 focus:ring-[#9FE870] outline-none"
        />

        <input
          value={prixMax}
          onChange={(e) => onPrixMaxChange(e.target.value)}
          placeholder="Prix max (FCFA)"
          type="number"
          className="w-full px-4 py-2.5 bg-[#F8FAF8] border border-gray-200 rounded-2xl text-xs font-medium focus:ring-2 focus:ring-[#9FE870] outline-none"
        />

        <div className="flex items-center gap-2">
          <select
            value={fraicheur}
            onChange={(e) => onFraicheurChange(e.target.value)}
            className="flex-1 min-w-0 px-4 py-2.5 bg-[#F8FAF8] border border-gray-200 rounded-2xl text-xs font-medium focus:ring-2 focus:ring-[#9FE870] outline-none"
          >
            <option value="">Indice Fraîcheur</option>
            <option value="tres_frais">🌱 Très frais (90%+)</option>
            <option value="frais">🍏 Frais (70%-90%)</option>
            <option value="a_consommer">⚡ À consommer rapidement</option>
          </select>

          {hasActiveFilters && (
            <button
              onClick={handleReset}
              className="p-2.5 bg-rose-50 text-rose-600 rounded-2xl hover:bg-rose-100 transition-colors"
              title="Réinitialiser les filtres"
            >
              <RefreshCw className="w-4 h-4" />
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

