import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ShoppingCart, User, Menu, Bell, Leaf, Search, Sparkles } from 'lucide-react';
import { useAuthContext } from '@/contexts/AuthContext';
import { useCartContext } from '@/contexts/CartContext';

interface NavbarProps {
  onToggleSidebar: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({ onToggleSidebar }) => {
  const { user, isAuthenticated } = useAuthContext();
  const { totalItems } = useCartContext();
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = React.useState('');

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      navigate(`/catalogue?search=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  return (
    <header className="sticky top-0 z-50 bg-[#0A4D3C] text-white shadow-md">
      {/* Container principal */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div className="flex items-center justify-between gap-4">
          
          {/* Logo & Burger menu */}
          <div className="flex items-center gap-3">
            {isAuthenticated && (
              <button
                onClick={onToggleSidebar}
                className="p-2 text-emerald-100 hover:text-white hover:bg-white/10 rounded-xl transition-all"
                aria-label="Menu"
              >
                <Menu className="w-5 h-5" />
              </button>
            )}

            <Link to="/" className="flex items-center gap-2.5 group">
              <div className="w-10 h-10 bg-[#9FE870] rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                <Leaf className="w-6 h-6 text-[#07362A]" />
              </div>
              <div className="flex flex-col">
                <span className="font-sans text-xl font-extrabold tracking-tight text-white leading-none">
                  Terra<span className="text-[#9FE870]">Link</span>
                </span>
                <span className="text-[10px] text-emerald-200 font-medium tracking-wider uppercase">Direct Producteurs</span>
              </div>
            </Link>
          </div>

          {/* Search bar centrale style marketplace (Gromuse style) */}
          <form onSubmit={handleSearchSubmit} className="hidden md:flex flex-1 max-w-lg relative">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Rechercher épicerie, légumes, fruits, viandes..."
              className="w-full pl-10 pr-10 py-2.5 bg-white text-earth-900 placeholder-earth-400 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-[#9FE870] shadow-inner transition-all"
            />
            <Search className="w-4 h-4 text-earth-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <button type="submit" className="absolute right-1.5 top-1/2 -translate-y-1/2 w-7 h-7 bg-[#9FE870] rounded-full flex items-center justify-center text-[#07362A] hover:bg-[#8EE25B] transition-colors">
              <Search className="w-3.5 h-3.5" />
            </button>
          </form>

          {/* Delivery Promise Badge / Banner (comme sur l'image) */}
          <div className="hidden lg:flex items-center gap-2 text-xs bg-white/10 px-3 py-1.5 rounded-full border border-white/10 text-emerald-100">
            <Sparkles className="w-3.5 h-3.5 text-[#9FE870]" />
            <span>Livré chez vous en <strong>15-45 min</strong></span>
          </div>

          {/* Right Actions: Cart, User Profile, Notifications */}
          <div className="flex items-center gap-2 sm:gap-3">
            <Link 
              to="/panier" 
              className="relative p-2.5 text-emerald-100 hover:text-white bg-white/5 hover:bg-white/10 rounded-full transition-all flex items-center gap-2"
              aria-label="Panier"
            >
              <ShoppingCart className="w-5 h-5 text-white" />
              {totalItems > 0 && (
                <span className="bg-[#9FE870] text-[#07362A] text-xs font-black px-2 py-0.5 rounded-full shadow-sm">
                  {totalItems}
                </span>
              )}
            </Link>

            {isAuthenticated ? (
              <>
                <button 
                  className="p-2.5 text-emerald-100 hover:text-white bg-white/5 hover:bg-white/10 rounded-full transition-all"
                  aria-label="Notifications"
                >
                  <Bell className="w-5 h-5" />
                </button>

                <Link 
                  to="/profil" 
                  className="flex items-center gap-2 pl-2 pr-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-full transition-all border border-white/10"
                >
                  <div className="w-7 h-7 bg-[#9FE870] rounded-full flex items-center justify-center text-[#07362A] font-bold text-xs">
                    {user?.prenom?.[0] || <User className="w-4 h-4" />}
                  </div>
                  <span className="text-xs font-semibold text-white hidden sm:inline">{user?.prenom}</span>
                </Link>
              </>
            ) : (
              <div className="flex items-center gap-2">
                <Link to="/login" className="px-4 py-2 text-xs font-semibold text-white hover:text-[#9FE870] transition-colors">
                  Connexion
                </Link>
                <Link 
                  to="/register" 
                  className="px-4 py-2 bg-[#9FE870] text-[#07362A] text-xs font-bold rounded-full hover:bg-[#8EE25B] transition-all shadow-sm"
                >
                  S'inscrire
                </Link>
              </div>
            )}
          </div>
        </div>

        {/* Mobile Search Bar */}
        <form onSubmit={handleSearchSubmit} className="mt-3 md:hidden relative">
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Rechercher des produits frais..."
            className="w-full pl-10 pr-4 py-2 bg-white text-earth-900 placeholder-earth-400 rounded-full text-xs focus:outline-none"
          />
          <Search className="w-3.5 h-3.5 text-earth-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
        </form>
      </div>
    </header>
  );
};